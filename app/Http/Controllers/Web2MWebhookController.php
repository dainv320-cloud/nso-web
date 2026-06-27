<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Web2MWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->hasValidBearerToken($request)) {
            return response()->json([
                'status' => false,
                'msg' => 'Access Token khong hop le.',
            ], 401);
        }

        $payload = $request->all();
        $items = $this->transactionItems($payload);

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $this->handleTransaction($item, $payload);
        }

        // Web2M can response nay de xac nhan da nhan webhook va khong gui lai.
        return response()->json([
            'status' => true,
            'msg' => 'Ok',
        ]);
    }

    private function hasValidBearerToken(Request $request): bool
    {
        $configuredToken = (string) config('web2m.access_token', '');
        $receivedToken = (string) $request->bearerToken();

        return $configuredToken !== ''
            && $configuredToken !== 'change-this-access-token'
            && hash_equals($configuredToken, $receivedToken);
    }

    private function transactionItems(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        // Ho tro payload don de tien test thu cong bang Postman/curl.
        return [$payload];
    }

    private function handleTransaction(array $item, array $rawPayload): void
    {
        $transactionId = $this->transactionId($item);
        $amount = (float) ($item['amount'] ?? 0);
        $coinAmount = (int) floor($amount);
        $description = trim((string) ($item['description'] ?? ''));
        $type = strtoupper((string) ($item['type'] ?? 'IN'));
        $bank = isset($item['bank']) ? (string) $item['bank'] : null;

        if ($transactionId === '' || $amount <= 0 || $description === '') {
            $this->logFailedPayment($transactionId, $amount, $coinAmount, $description, $rawPayload, 'Du lieu giao dich khong hop le.');

            return;
        }

        if ($type !== 'IN') {
            $this->logFailedPayment($transactionId, $amount, $coinAmount, $description, $rawPayload, 'Bo qua giao dich khong phai tien vao.');

            return;
        }

        $processedPayment = Payment::query()
            ->where('transaction_id', $transactionId)
            ->where('status', 'success')
            ->exists();

        if ($processedPayment) {
            return;
        }

        $userId = $this->parseUserId($description);

        if (!$userId) {
            $this->logFailedPayment($transactionId, $amount, $coinAmount, $description, $rawPayload, 'Khong tim thay ID_USER trong noi dung chuyen khoan.');

            return;
        }

        $coinColumn = $this->coinBalanceColumn();

        try {
            DB::transaction(function () use ($transactionId, $amount, $coinAmount, $description, $rawPayload, $item, $userId, $coinColumn, $bank, $type): void {
                // Khoa user trong transaction de tranh cong coin sai khi Web2M gui trung hoac request den dong thoi.
                $user = User::query()->whereKey($userId)->lockForUpdate()->first();

                if (!$user) {
                    $this->logFailedPayment($transactionId, $amount, $coinAmount, $description, $rawPayload, 'Khong tim thay user hop le.');

                    return;
                }

                $existingPayment = Payment::query()
                    ->where('transaction_id', $transactionId)
                    ->lockForUpdate()
                    ->first();

                if ($existingPayment && $existingPayment->status === 'success') {
                    return;
                }

                $user->forceFill([
                    $coinColumn => ((int) $user->{$coinColumn}) + $coinAmount,
                ])->save();

                Payment::query()->updateOrCreate(
                    ['transaction_id' => $transactionId],
                    [
                        'user_id' => $user->id,
                        'bank' => $bank,
                        'type' => $type,
                        'amount' => $amount,
                        'coin_amount' => $coinAmount,
                        'status' => 'success',
                        'description' => $description,
                        'raw_payload' => [
                            'web2m_item' => $item,
                            'web2m_payload' => $rawPayload,
                        ],
                    ],
                );
            });
        } catch (QueryException $exception) {
            if (!$this->isDuplicateTransaction($exception)) {
                throw $exception;
            }
        }
    }

    private function transactionId(array $item): string
    {
        // Web2M document: id la ma dinh danh duy nhat. transactionID la ma tu ngan hang.
        return trim((string) ($item['id'] ?? $item['transaction_id'] ?? $item['transactionID'] ?? ''));
    }

    private function parseUserId(string $description): ?int
    {
        // Chap nhan: "NAPCOIN 123", "NAPCOIN-123", "NAPCOIN: 123", "NAP123", "IB NAP123".
        if (!preg_match('/\bNAP(?:COIN)?[\s\-:]*(\d+)\b/i', $description, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function coinBalanceColumn(): string
    {
        // Schema hien tai dang co cot money. Neu sau nay them coin_balance, code se tu dung cot moi.
        return Schema::hasColumn('users', 'coin_balance') ? 'coin_balance' : 'money';
    }

    private function logFailedPayment(
        string $transactionId,
        float $amount,
        int $coinAmount,
        string $description,
        array $payload,
        string $reason,
    ): void {
        if ($transactionId === '') {
            $transactionId = 'web2m-invalid-' . sha1(json_encode($payload) . microtime(true));
        }

        Payment::query()->updateOrCreate(
            ['transaction_id' => $transactionId],
            [
                'type' => 'IN',
                'amount' => max($amount, 0),
                'coin_amount' => max($coinAmount, 0),
                'status' => 'failed',
                'description' => trim($description . ' | ' . $reason),
                'raw_payload' => $payload,
            ],
        );
    }

    private function isDuplicateTransaction(QueryException $exception): bool
    {
        $sqlState = (string) $exception->getCode();
        $message = $exception->getMessage();

        return $sqlState === '23505'
            || str_contains($message, 'payments_transaction_id_unique')
            || str_contains($message, 'Duplicate entry');
    }
}
