<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Web2MWebhookProcessor
{
    private const MIN_TRANSACTION_DATE = '2026-07-11';
    private const TRANSACTION_DATE_FIELDS = [
        'NgayGiaoDich',
        'NgayGD',
        'Ngay',
        'NgayHieuLuc',
        'NgayGioGiaoDich',
        'ThoiGianGiaoDich',
        'ThoiGian',
        'transaction_date',
        'transactionDate',
        'created_at',
        'date',
        'time',
    ];
    private const TRANSACTION_DATE_FORMATS = [
        'd/m/Y H:i:s',
        'd/m/Y H:i',
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd/m/Y',
        'd-m-Y',
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        \DateTimeInterface::ATOM,
    ];

    public function process(array $item, array $rawPayload): void
    {
        $normalizedItem = $this->normalizeTransactionItem($item);

        if ($this->isBeforeMinimumTransactionDate($normalizedItem)) {
            return;
        }

        if (!$this->hasValidTransactionData($normalizedItem)) {
            $this->logFailedWebhook(
                $normalizedItem['transaction_id'],
                $normalizedItem['amount'],
                $normalizedItem['description'],
                $rawPayload,
                'Du lieu giao dich Web2M khong hop le.',
            );

            return;
        }

        if ($normalizedItem['type'] !== 'IN') {
            $this->logFailedWebhook(
                $normalizedItem['transaction_id'],
                $normalizedItem['amount'],
                $normalizedItem['description'],
                $rawPayload,
                'Bo qua giao dich khong phai tien vao.',
            );

            return;
        }

        try {
            DB::transaction(function () use ($normalizedItem, $rawPayload): void {
                $payment = $this->paymentFromDescription($normalizedItem['description']);

                if (!$payment) {
                    $this->logFailedWebhook(
                        $normalizedItem['transaction_id'],
                        $normalizedItem['amount'],
                        $normalizedItem['description'],
                        $rawPayload,
                        'Khong tim thay giao dich pending khop noi dung chuyen khoan.',
                    );

                    return;
                }

                if ($this->paymentIsSuccess($payment) || !$this->paymentIsPending($payment)) {
                    return;
                }

                $expectedAmount = (float) $payment->amount;

                if (abs($expectedAmount - $normalizedItem['amount']) > 0.01) {
                    $this->markPaymentFailed(
                        $payment,
                        $normalizedItem,
                        $rawPayload,
                        'So tien chuyen khoan khong khop. Expected: ' . $expectedAmount . ', received: ' . $normalizedItem['amount'],
                    );

                    return;
                }

                $user = User::query()->whereKey((int) $payment->user_id)->lockForUpdate()->first();

                if (!$user) {
                    $this->markPaymentFailed($payment, $normalizedItem, $rawPayload, 'Khong tim thay user cua giao dich pending.');

                    return;
                }

                $beforeSnapshot = $this->userBalanceSnapshot($user);
                $coinAmount = $this->paymentCoinAmount($payment, $normalizedItem['amount']);
                $this->creditUser($user, $normalizedItem['amount'], $coinAmount);
                $this->markPaymentSuccess($payment, $normalizedItem, $rawPayload, $normalizedItem['type'], $beforeSnapshot);
            });
        } catch (QueryException $exception) {
            if (!$this->isDuplicateTransaction($exception)) {
                throw $exception;
            }
        }
    }

    public function transactionItems(array $payload): array
    {
        if (isset($payload['data']['ChiTietGiaoDich']) && is_array($payload['data']['ChiTietGiaoDich'])) {
            return $payload['data']['ChiTietGiaoDich'];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return $payload['data'];
        }

        return [$payload];
    }

    private function hasValidTransactionData(array $normalizedItem): bool
    {
        return $normalizedItem['transaction_id'] !== ''
            && $normalizedItem['amount'] > 0
            && $normalizedItem['description'] !== '';
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
            'transaction_date' => $this->transactionDateFromItem($item),
        ];
    }

    private function moneyAmount(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string) $value));

        return (float) ($normalized === '' ? 0 : $normalized);
    }

    private function isBeforeMinimumTransactionDate(array $item): bool
    {
        $transactionDate = $item['transaction_date'] ?? null;

        if (!$transactionDate instanceof \DateTimeImmutable) {
            return false;
        }

        $minimumDate = new \DateTimeImmutable(self::MIN_TRANSACTION_DATE . ' 00:00:00');

        return $transactionDate < $minimumDate;
    }

    private function transactionDateFromItem(array $item): ?\DateTimeImmutable
    {
        foreach (self::TRANSACTION_DATE_FIELDS as $field) {
            if (!isset($item[$field])) {
                continue;
            }

            $date = $this->parseTransactionDate($item[$field]);

            if ($date) {
                return $date;
            }
        }

        return null;
    }

    private function parseTransactionDate(mixed $value): ?\DateTimeImmutable
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (self::TRANSACTION_DATE_FORMATS as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();

            if ($date instanceof \DateTimeImmutable && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : (new \DateTimeImmutable())->setTimestamp($timestamp);
    }

    private function paymentFromDescription(string $description): ?Payment
    {
        $codes = $this->paymentCodesFromDescription($description);

        if ($codes === []) {
            return null;
        }

        return Payment::query()
            ->whereIn('transaction_id', $codes)
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
        $amountInt = max(0, (int) round($amount));

        User::query()
            ->whereKey($user->getKey())
            ->update([
                'balance' => DB::raw('GREATEST(COALESCE(balance, 0), 0) + ' . $coinAmount),
                'tongnap' => DB::raw('GREATEST(COALESCE(tongnap, 0), 0) + ' . $amountInt),
                'tongNapThang' => DB::raw('GREATEST(COALESCE(tongNapThang, 0), 0) + ' . $amountInt),
                'tongNapTuan' => DB::raw('GREATEST(COALESCE(tongNapTuan, 0), 0) + ' . $amountInt),
            ]);
    }

    private function markPaymentSuccess(
        Payment $payment,
        array $item,
        array $rawPayload,
        string $type,
        array $beforeSnapshot,
    ): void
    {
        $values = $this->basePaymentMutationValues(
            description: (string) ($item['description'] ?? $item['MoTa'] ?? ''),
            item: $item,
            rawPayload: $rawPayload,
            status: 'success',
        );

        $values['type'] = $type;
        $values['received'] = 1;
        $values['user_balance_snapshot_before'] = $beforeSnapshot;

        $payment->forceFill($values)->save();
    }

    private function markPaymentFailed(Payment $payment, array $item, array $rawPayload, string $reason): void
    {
        $values = $this->basePaymentMutationValues(
            description: (string) ($item['description'] ?? $item['MoTa'] ?? '') . ' | ' . $reason,
            item: $item,
            rawPayload: $rawPayload,
            status: 'failed',
            reason: $reason,
        );

        $values['received'] = 0;

        $payment->forceFill($values)->save();
    }

    private function logFailedWebhook(
        string $web2mTransactionId,
        float $amount,
        string $description,
        array $payload,
        string $reason,
    ): void {
        $transactionId = $web2mTransactionId !== ''
            ? 'web2m-' . $web2mTransactionId
            : 'web2m-invalid-' . sha1(json_encode($payload) . microtime(true));

        $values = [
            'transaction_id' => $transactionId,
            'amount' => max($amount, 0),
        ];
        $values += $this->basePaymentMutationValues(
            description: $description . ' | ' . $reason,
            item: [],
            rawPayload: $payload,
            status: 'failed',
            reason: $reason,
        );

        $values['currency_id'] = 'VND';
        $values['player_name'] = '';
        $values['received'] = 0;
        $values['type'] = 'IN';
        $values['coin_amount'] = 0;
        $values['balance'] = 0;

        Payment::query()->updateOrCreate(
            ['transaction_id' => $transactionId],
            $values,
        );
    }

    private function basePaymentMutationValues(
        string $description,
        array $item,
        array $rawPayload,
        string $status,
        ?string $reason = null,
    ): array {
        $values = [
            'description' => $this->paymentDescription($description),
            'status' => $status,
            'bank' => isset($item['bank']) ? (string) $item['bank'] : null,
        ];

        $this->appendPaymentLog($values, $item, $rawPayload, $status, $reason);

        return $values;
    }

    private function paymentDescription(string $description): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');

        return substr($description, 0, 100);
    }

    private function appendPaymentLog(array &$values, array $item, array $rawPayload, string $status, ?string $reason = null): void
    {
        $log = [
            'web2m_status' => $status,
            'web2m_reason' => $reason,
            'web2m_item' => $item,
            'web2m_payload' => $rawPayload,
        ];

        $values['raw_payload'] = $log;
        $values['extra'] = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function paymentIsSuccess(Payment $payment): bool
    {
        return strtolower((string) $payment->status) === 'success'
            || (int) $payment->received === 1;
    }

    private function paymentIsPending(Payment $payment): bool
    {
        return in_array(strtolower((string) $payment->status), ['pending', 'pedding'], true);
    }

    private function paymentCoinAmount(Payment $payment, float $amount): int
    {
        if ((int) $payment->coin_amount > 0) {
            return (int) $payment->coin_amount;
        }

        if ((int) $payment->balance > 0) {
            return (int) $payment->balance;
        }

        return (int) floor($amount);
    }

    private function userBalanceSnapshot(User $user): array
    {
        return [
            'balance' => (int) $user->balance,
            'tongnap' => (int) $user->tongnap,
            'tongNapThang' => (int) $user->tongNapThang,
            'tongNapTuan' => (int) $user->tongNapTuan,
            'captured_at' => now()->toDateTimeString(),
        ];
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
