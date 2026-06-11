<?php
$bankAccount = $bankAccount ?? null;
$amounts = $amounts ?? [];
$account = $account ?? null;
$selectedAmount = (int) ($amount ?? ($amounts[0]['amount'] ?? 10000));
$paymentContent = (string) ($paymentCode ?? '');
$coinAmount = (int) ($coinAmount ?? 0);
$qrImageUrl = (string) ($qrImageUrl ?? '');
$effectiveRate = $effectiveRate ?? ($bankAccount['bank_rate'] ?? null);
$activeCampaign = $activeCampaign ?? null;
?>

<section class="page-head">
    <p class="eyebrow">ATM / VietQR</p>
    <h1>Nạp tiền </h1>
    <p>Chọn mệnh giá, quét mã QR và chuyển khoản đúng nội dung hệ thống hiển thị.</p>
</section>

<section class="form-layout">
    <form class="panel form" method="post" action="/payment">
        <?php if (!empty($error)): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if (!$bankAccount): ?>
            <div class="alert error">Chưa cấu hình tài khoản ngân hàng nhận tiền.</div>
        <?php endif; ?>

        <?php if ($bankAccount && $account): ?>
            <div class="alert success">
                Tài khoản nạp: <strong><?= e($account['username'] ?? '') ?></strong>
                <br>
                Nội dung chuyển khoản của bạn sẽ có dạng:
                <strong><?= e($paymentContent) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($bankAccount): ?>
            <input type="hidden" name="bank_account_id" value="<?= e((string) $bankAccount['id']) ?>">
        <?php endif; ?>

        <label>
            Số tiền nạp
            <input
                name="amount"
                type="number"
                min="1000"
                step="1000"
                inputmode="numeric"
                list="payment-amount-suggestions"
                value="<?= e((string) $selectedAmount) ?>"
                placeholder="Ví dụ: 100000"
                required
            >
            <datalist id="payment-amount-suggestions">
                <?php foreach ($amounts as $item): ?>
                    <?php $value = (int) $item['amount']; ?>
                    <option value="<?= $value ?>"><?= number_format($value, 0, ',', '.') ?> VND</option>
                <?php endforeach; ?>
            </datalist>
            <small class="form-hint">Nhập số tiền là bội của 1.000 VND.</small>
        </label>

        <?php if ($bankAccount): ?>
            <dl class="payment-qr-info inline">
                <div>
                    <dt>Ngân hàng</dt>
                    <dd><?= e((string) $bankAccount['bank_name']) ?></dd>
                </div>
                <div>
                    <dt>Chủ tài khoản</dt>
                    <dd><?= e((string) $bankAccount['acc_name']) ?></dd>
                </div>
                <div>
                    <dt>Số tài khoản</dt>
                    <dd><?= e((string) $bankAccount['acc_num']) ?></dd>
                </div>
                <div>
                    <dt>Tỷ lệ coin</dt>
                    <dd><?= e((string) $effectiveRate) ?></dd>
                </div>
                <?php if ($activeCampaign): ?>
                    <div>
                        <dt>Khuyến mãi</dt>
                        <dd><?= e($activeCampaign['name']) ?> +<?= e((string) $activeCampaign['bonus_percent']) ?>%</dd>
                    </div>
                <?php endif; ?>
            </dl>
        <?php endif; ?>

        <button class="btn primary" type="submit" <?= !$bankAccount ? 'disabled' : '' ?>>Tạo QR VietQR</button>
    </form>

    <aside class="panel">
        <img class="panel-image" src="/img/ns2d-ninja.webp" alt="Ninja Mobile">
        <h2>Lưu ý chuyển khoản</h2>
        <ul class="check-list">
            <li>Chuyển đúng số tiền và đúng nội dung hiển thị.</li>
            <li>Coin sẽ được cộng sau khi webhook ngân hàng xác nhận giao dịch.</li>
        </ul>
    </aside>
</section>

<?php if (!empty($submitted) && $bankAccount): ?>
    <div class="payment-qr-backdrop is-open" data-payment-qr-backdrop aria-hidden="false">
        <section class="payment-qr-modal" role="dialog" aria-modal="true" aria-labelledby="payment-qr-title">
            <button class="payment-qr-close" type="button" data-payment-qr-close aria-label="Đóng">&times;</button>
            <header>
                <h2 id="payment-qr-title">Quét mã QR VietQR</h2>
                <p>Hệ thống sẽ cộng tiền sau khi webhook ngân hàng báo giao dịch vào.</p>
            </header>

            <div class="payment-qr-image">
                <img src="<?= e($qrImageUrl) ?>" alt="QR VietQR <?= e($paymentContent) ?>">
                <strong>V</strong>
            </div>

            <dl class="payment-qr-info">
                <div>
                    <dt>Ngân hàng</dt>
                    <dd><?= e((string) $bankAccount['bank_name']) ?> <button type="button" data-copy-value="<?= e((string) $bankAccount['bank_name']) ?>">Copy</button></dd>
                </div>
                <div>
                    <dt>Chủ tài khoản</dt>
                    <dd><?= e((string) $bankAccount['acc_name']) ?> <button type="button" data-copy-value="<?= e((string) $bankAccount['acc_name']) ?>">Copy</button></dd>
                </div>
                <div>
                    <dt>Số tài khoản</dt>
                    <dd><?= e((string) $bankAccount['acc_num']) ?> <button type="button" data-copy-value="<?= e((string) $bankAccount['acc_num']) ?>">Copy</button></dd>
                </div>
                <div>
                    <dt>Số tiền</dt>
                    <dd><?= number_format($selectedAmount, 0, ',', '.') ?> VND <button type="button" data-copy-value="<?= e((string) $selectedAmount) ?>">Copy</button></dd>
                </div>
                <div>
                    <dt>Nội dung</dt>
                    <dd><?= e($paymentContent) ?> <button type="button" data-copy-value="<?= e($paymentContent) ?>">Copy</button></dd>
                </div>
                <div>
                    <dt>Coin nhận</dt>
                    <dd><?= number_format($coinAmount, 0, ',', '.') ?></dd>
                </div>
                <?php if ($activeCampaign): ?>
                    <div>
                        <dt>Khuyến mãi</dt>
                        <dd><?= e($activeCampaign['name']) ?> +<?= e((string) $activeCampaign['bonus_percent']) ?>%</dd>
                    </div>
                <?php endif; ?>
            </dl>

            <div class="payment-qr-note">
                <strong>Quan trọng:</strong>
                <ul>
                    <li>Nội dung chuyển khoản phải đúng chính xác: <b><?= e($paymentContent) ?></b></li>
                    <li>Website không tạo order trước; tiền chỉ được cộng khi webhook hợp lệ trả về transactionID mới.</li>
                </ul>
            </div>
        </section>
    </div>
<?php endif; ?>
