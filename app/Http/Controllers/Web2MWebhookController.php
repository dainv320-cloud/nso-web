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
    private const ACCESS_TOKEN = '6AFBE818-C736-D46B-36A4-6A435A9C1887';

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->hasValidBearerToken($request)) {
            return response()->json([
                'status' => false,
                'msg' => 'Access Token khong hop le nhu cut.',
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
        $configuredToken = self::ACCESS_TOKEN;
        $receivedToken = $this->receivedToken($request);

        return $configuredToken !== ''
            && $configuredToken !== 'change-this-access-token'
            && hash_equals($configuredToken, $receivedToken);
    }

    private function receivedToken(Request $request): string
    {
        $authorization = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

        if (str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        $headerName = (string) config('web2m.token_header', 'X-Web2M-Token');
        $token = (string) $request->header($headerName, '');

        if ($token !== '') {
            return $token;
        }

        return (string) $request->query('token', '');
    }

    private function transactionItems(array $payload): array
    {
        if (isset($payload['data']['ChiTietGiaoDich']) && is_array($payload['data']['ChiTietGiaoDich'])) {
            return $payload['data']['ChiTietGiaoDich'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        // Ho tro payload don de tien test thu cong bang Postman/curl.
        return [$payload];
    }

    private function handleTransaction(array $item, array $rawPayload): void
    {
        $normalizedItem = $this->normalizeTransactionItem($item);
        $web2mTransactionId = $normalizedItem['transaction_id'];
        $amount = $normalizedItem['amount'];
        $description = $normalizedItem['description'];
        $type = $normalizedItem['type'];

        if ($web2mTransactionId === '' || $amount <= 0 || $description === '') {
            $this->logFailedWebhook($web2mTransactionId, $amount, $description, $rawPayload, 'Du lieu giao dich Web2M khong hop le.');

            return;
        }

        if ($type !== 'IN') {
            $this->logFailedWebhook($web2mTransactionId, $amount, $description, $rawPayload, 'Bo qua giao dich khong phai tien vao.');

            return;
        }

        try {
            DB::transaction(function () use ($normalizedItem, $rawPayload, $web2mTransactionId, $amount, $description, $type): void {
                $payment = $this->paymentFromDescription($description);

                if (!$payment) {
                    $this->logFailedWebhook($web2mTransactionId, $amount, $description, $rawPayload, 'Khong tim thay giao dich pending khop noi dung chuyen khoan.');

                    return;
                }

                if ($this->paymentIsSuccess($payment)) {
                    return;
                }

                if (!$this->paymentIsPending($payment)) {
                    return;
                }

                $expectedAmount = (float) $payment->amount;

                if (abs($expectedAmount - $amount) > 0.01) {
                    $this->markPaymentFailed($payment, $normalizedItem, $rawPayload, 'So tien chuyen khoan khong khop. Expected: ' . $expectedAmount . ', received: ' . $amount);

                    return;
                }

                $user = User::query()->whereKey((int) $payment->user_id)->lockForUpdate()->first();

                if (!$user) {
                    $this->markPaymentFailed($payment, $normalizedItem, $rawPayload, 'Khong tim thay user cua giao dich pending.');

                    return;
                }

                $coinAmount = $this->paymentCoinAmount($payment, $amount);
                $this->creditUser($user, $amount, $coinAmount);
                $this->markPaymentSuccess($payment, $normalizedItem, $rawPayload, $type);
            });
        } catch (QueryException $exception) {
            if (!$this->isDuplicateTransaction($exception)) {
                throw $exception;
            }
        }
    }

    private function normalizeTransactionItem(array $item): array
    {
        $creditAmount = $item['SoTienGhiCo'] ?? null;
        $debitAmount = $item['SoTienGhiNo'] ?? null;
        $amount = $creditAmount ?? $item['amount'] ?? 0;
        $description = $item['MoTa'] ?? $item['description'] ?? '';
        $direction = strtoupper(trim((string) ($item['CD'] ?? '')));
        $type = strtoupper(trim((string) ($item['type'] ?? '')));

        if ($type === '') {
            $type = ($direction === '-' || $debitAmount !== null) ? 'OUT' : 'IN';
        }

        return [
            'transaction_id' => trim((string) ($item['SoThamChieu'] ?? $item['id'] ?? $item['transaction_id'] ?? $item['transactionID'] ?? '')),
            'amount' => $this->moneyAmount($amount),
            'description' => trim((string) $description),
            'type' => $type,
            'bank' => $item['bank'] ?? $item['NganHang'] ?? null,
            'raw_item' => $item,
        ];
    }

    private function moneyAmount(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string) $value));

        return (float) ($normalized === '' ? 0 : $normalized);
    }

    private function paymentFromDescription(string $description): ?Payment
    {
        $codes = $this->paymentCodesFromDescription($description);

        if ($codes === []) {
            return null;
        }

        $codeColumn = $this->paymentCodeColumn();

        return Payment::query()
            ->whereIn($codeColumn, $codes)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();
    }

    private function paymentCodesFromDescription(string $description): array
    {
        $normalized = strtoupper(preg_replace('/\s+/', '', $description) ?? '');

        if (!preg_match_all('/[A-Z]{2,30}[0-9]{2,40}/', $normalized, $matches)) {
            return [];
        }

        return array_values(array_unique($matches[0]));
    }

    private function creditUser(User $user, float $amount, int $coinAmount): void
    {
        $balanceColumn = $this->firstUserColumn(['balance', 'money']);
        $totalDepositColumn = $this->firstUserColumn(['tongnap', 'totalmoney']);
        $monthlyDepositColumn = $this->firstUserColumn(['tongNapThang', 'tongnapthang']);

        $values = [
            $balanceColumn => max(0, (int) $user->{$balanceColumn}) + $coinAmount,
            $totalDepositColumn => max(0, (int) $user->{$totalDepositColumn}) + (int) $amount,
            $monthlyDepositColumn => max(0, (int) $user->{$monthlyDepositColumn}) + (int) $amount,
        ];

        if (Schema::hasColumn('users', 'tongNapTuan')) {
            $values['tongNapTuan'] = max(0, (int) $user->tongNapTuan) + (int) $amount;
        }

        $user->forceFill($values)->save();
    }

    private function markPaymentSuccess(Payment $payment, array $item, array $rawPayload, string $type): void
    {
        $values = [
            'description' => trim((string) ($item['description'] ?? $item['MoTa'] ?? '')),
        ];

        if (Schema::hasColumn('payments', 'status')) {
            $values['status'] = 'success';
        }

        if (Schema::hasColumn('payments', 'bank')) {
            $values['bank'] = isset($item['bank']) ? (string) $item['bank'] : null;
        }

        if (Schema::hasColumn('payments', 'type')) {
            $values['type'] = $this->paymentTypeValue($type);
        }

        if (Schema::hasColumn('payments', 'received')) {
            $values['received'] = 1;
        }

        $this->appendPaymentLog($values, $item, $rawPayload, 'success');
        $payment->forceFill($values)->save();
    }

    private function markPaymentFailed(Payment $payment, array $item, array $rawPayload, string $reason): void
    {
        $values = [
            'description' => trim((string) ($item['description'] ?? $item['MoTa'] ?? '') . ' | ' . $reason),
        ];

        if (Schema::hasColumn('payments', 'status')) {
            $values['status'] = 'failed';
        }

        if (Schema::hasColumn('payments', 'received')) {
            $values['received'] = 0;
        }

        $this->appendPaymentLog($values, $item, $rawPayload, 'failed', $reason);
        $payment->forceFill($values)->save();
    }

    private function logFailedWebhook(
        string $web2mTransactionId,
        float $amount,
        string $description,
        array $payload,
        string $reason,
    ): void {
        $codeColumn = $this->paymentCodeColumn();
        $transactionId = $web2mTransactionId !== ''
            ? 'web2m-' . $web2mTransactionId
            : 'web2m-invalid-' . sha1(json_encode($payload) . microtime(true));

        $values = [
            $codeColumn => $transactionId,
            'amount' => max($amount, 0),
            'description' => trim($description . ' | ' . $reason),
        ];

        if (Schema::hasColumn('payments', 'status')) {
            $values['status'] = 'failed';
        }

        if (Schema::hasColumn('payments', 'received')) {
            $values['received'] = 0;
        }

        if (Schema::hasColumn('payments', 'type')) {
            $values['type'] = $this->paymentTypeValue('IN');
        }

        if (Schema::hasColumn('payments', 'coin_amount')) {
            $values['coin_amount'] = 0;
        }

        if (Schema::hasColumn('payments', 'balance')) {
            $values['balance'] = 0;
        }

        $this->appendPaymentLog($values, [], $payload, 'failed', $reason);

        Payment::query()->updateOrCreate(
            [$codeColumn => $transactionId],
            $values,
        );
    }

    private function appendPaymentLog(array &$values, array $item, array $rawPayload, string $status, ?string $reason = null): void
    {
        $log = [
            'web2m_status' => $status,
            'web2m_reason' => $reason,
            'web2m_item' => $item,
            'web2m_payload' => $rawPayload,
        ];

        if (Schema::hasColumn('payments', 'raw_payload')) {
            $values['raw_payload'] = $log;
        }

        if (Schema::hasColumn('payments', 'extra')) {
            $values['extra'] = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    private function web2mTransactionId(array $item): string
    {
        // Web2M document: id la ma dinh danh duy nhat. transactionID la ma tu ngan hang.
        return trim((string) ($item['id'] ?? $item['transaction_id'] ?? $item['transactionID'] ?? ''));
    }

    private function paymentIsSuccess(Payment $payment): bool
    {
        return strtolower((string) ($payment->status ?? '')) === 'success'
            || (int) ($payment->received ?? 0) === 1;
    }

    private function paymentIsPending(Payment $payment): bool
    {
        if (Schema::hasColumn('payments', 'status')) {
            return in_array(strtolower((string) $payment->status), ['pending', 'pedding'], true);
        }

        return !Schema::hasColumn('payments', 'received') || (int) $payment->received === 0;
    }

    private function paymentTypeValue(string $type): string|int
    {
        return Schema::hasColumn('payments', 'transaction_id') ? $type : 1;
    }

    private function paymentCoinAmount(Payment $payment, float $amount): int
    {
        if (Schema::hasColumn('payments', 'coin_amount') && (int) $payment->coin_amount > 0) {
            return (int) $payment->coin_amount;
        }

        if (Schema::hasColumn('payments', 'balance') && (int) $payment->balance > 0) {
            return (int) $payment->balance;
        }

        return (int) floor($amount);
    }

    private function firstUserColumn(array $columns): string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn('users', $column)) {
                return $column;
            }
        }

        return $columns[0];
    }

    private function paymentCodeColumn(): string
    {
        return Schema::hasColumn('payments', 'transaction_id') ? 'transaction_id' : 'trans_id';
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
