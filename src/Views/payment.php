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
$paymentStatusQuery = trim((string) ($_GET['payment'] ?? ''));
$showQrScreen = !empty($submitted) && $bankAccount && $paymentContent !== '' && $qrImageUrl !== '';
?>

<section class="page-head">
    <p class="eyebrow">ATM / VietQR</p>
    <h1>Nạp tiền</h1>
    <p>Chọn mệnh giá, quét mã QR và chuyển khoản đúng nội dung hệ thống hiển thị.</p>
</section>

<?php if ($paymentStatusQuery === 'success'): ?>
    <div class="alert success">Nạp tiền thành công. Bạn có thể tạo giao dịch mới nếu muốn.</div>
<?php elseif ($paymentStatusQuery === 'cancelled'): ?>
    <div class="alert error">Giao dịch đã được hủy.</div>
<?php endif; ?>

<?php if (!$showQrScreen): ?>
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
                </div>
            <?php endif; ?>

            <?php if ($bankAccount): ?>
                <input type="hidden" name="bank_account_id" value="<?= e((string) $bankAccount['id']) ?>">
            <?php endif; ?>

            <div
                data-payment-converter
                data-payment-rate="<?= e((string) ($effectiveRate ?? 1)) ?>"
                data-payment-base-rate="<?= e((string) ($bankAccount['bank_rate'] ?? 1)) ?>"
                data-payment-campaign-name="<?= e((string) ($activeCampaign['name'] ?? '')) ?>"
                data-payment-campaign-bonus="<?= e((string) ($activeCampaign['bonus_percent'] ?? 0)) ?>"
            >
                <label>
                    Số tiền nạp
                    <input
                        name="amount"
                        type="text"
                        min="1000"
                        step="1000"
                        inputmode="numeric"
                        list="payment-amount-suggestions"
                        data-money-input
                        value="<?= e(number_format($selectedAmount, 0, ',', '.')) ?>"
                        placeholder="Ví dụ: 100.000"
                        required
                    >
                    <datalist id="payment-amount-suggestions">
                        <?php foreach ($amounts as $item): ?>
                            <?php $value = (int) $item['amount']; ?>
                            <option value="<?= number_format($value, 0, ',', '.') ?>"><?= number_format($value, 0, ',', '.') ?> VND</option>
                        <?php endforeach; ?>
                    </datalist>
                    <small class="form-hint">Nhập số tiền là bội số của 1.000 VND.</small>
                </label>

                <label>
                    Coin nhận được
                    <input
                        type="text"
                        value="<?= e(number_format($coinAmount > 0 ? $coinAmount : (int) floor($selectedAmount * (float) ($effectiveRate ?? 1)), 0, ',', '.')) ?>"
                        data-payment-coin-output
                        readonly
                    >
                    <small class="form-hint" data-payment-rate-text>
                        <?php if ($activeCampaign): ?>
                            Khuyến mãi <?= e((string) $activeCampaign['name']) ?> đang áp dụng: <?= e((string) ($bankAccount['bank_rate'] ?? 1)) ?> x (1 + <?= e((string) $activeCampaign['bonus_percent']) ?>%) = <?= e((string) $effectiveRate) ?> coin / 1 VND.
                        <?php else: ?>
                            Tỷ lệ hiện tại: <?= e((string) $effectiveRate) ?> coin / 1 VND.
                        <?php endif; ?>
                    </small>
                </label>
            </div>

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

            <button class="btn primary" type="submit" <?= !$bankAccount ? 'disabled' : '' ?>>Tạo mã QR VietQR</button>
        </form>

        <aside class="panel">
            <img class="panel-image" src="/img/ns2d-ninja.webp" alt="Ninja Mobile">
            <h2>Lưu ý khi chuyển khoản</h2>
            <ul class="check-list">
                <li>Chuyển đúng số tiền và đúng nội dung hệ thống hiển thị.</li>
                <li>Chuyển tiền là hoàn toàn tự nguyện.</li>
                <li>Coin được cộng ngay sau khi chuyển tiền thành công.</li>
            </ul>
        </aside>
    </section>
<?php else: ?>
    <section
        class="panel payment-qr-modal"
        data-payment-qr-screen
        data-payment-status-url="/payment/status?code=<?= e(rawurlencode($paymentContent)) ?>"
        data-payment-cancel-url="/payment/cancel"
        data-payment-code="<?= e($paymentContent) ?>"
    >
        <header>
            <h2 id="payment-qr-title">Quét mã QR VietQR</h2>
            <p>Hệ thống sẽ cộng coin sau khi webhook ngân hàng xác nhận giao dịch.</p>
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
                <li>Website đã tạo giao dịch chờ, vui lòng không đổi nội dung hoặc số tiền.</li>
            </ul>
        </div>

        <div class="alert error" data-payment-error style="display:none;"></div>
        <p class="form-hint" data-payment-status-text>Đang chờ webhook xác nhận giao dịch...</p>

        <div class="account-actions">
            <a class="btn secondary" href="/payment" data-payment-back>Quay về</a>
            <button class="btn primary" type="button" data-payment-cancel>Hủy GD</button>
        </div>
    </section>

    <script>
    (function () {
        function ready(fn) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fn);
                return;
            }

            fn();
        }

        ready(function () {
            var screen = document.querySelector('[data-payment-qr-screen]');

            if (!screen || screen.getAttribute('data-payment-watcher-started') === '1') {
                return;
            }

            screen.setAttribute('data-payment-watcher-started', '1');

            var statusText = screen.querySelector('[data-payment-status-text]');
            var errorBox = screen.querySelector('[data-payment-error]');
            var statusUrl = screen.getAttribute('data-payment-status-url') || '';
            var cancelUrl = screen.getAttribute('data-payment-cancel-url') || '';
            var paymentCode = screen.getAttribute('data-payment-code') || '';
            var resolved = false;
            var cancelling = false;
            var pollTimer = null;

            function redirectToForm(query) {
                window.location.href = '/payment' + (query ? '?' + query : '');
            }

            function showError(message) {
                if (errorBox) {
                    errorBox.style.display = 'block';
                    errorBox.textContent = message;
                }

                if (statusText) {
                    statusText.textContent = 'Giao dịch chưa hoàn tất.';
                }
            }

            function poll() {
                if (!statusUrl || resolved) {
                    return;
                }

                var xhr = new XMLHttpRequest();
                xhr.open('GET', statusUrl, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.onreadystatechange = function () {
                    if (xhr.readyState !== 4 || resolved) {
                        return;
                    }

                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var payload = JSON.parse(xhr.responseText || '{}');

                            if (payload.paid) {
                                resolved = true;
                                redirectToForm('payment=success');
                                return;
                            }

                            if (payload.failed) {
                                resolved = true;
                                showError(payload.message || 'Giao dịch thất bại, vui lòng kiểm tra lại.');
                                return;
                            }
                        } catch (error) {
                            // Ignore and continue polling.
                        }
                    }

                    if (!resolved) {
                        pollTimer = window.setTimeout(poll, 3000);
                    }
                };

                xhr.send();
            }

            function cancelPaymentAndReturn() {
                if (cancelling) {
                    return;
                }

                cancelling = true;

                if (pollTimer) {
                    window.clearTimeout(pollTimer);
                    pollTimer = null;
                }

                if (!cancelUrl || !paymentCode) {
                    redirectToForm('payment=cancelled');
                    return;
                }

                var xhr = new XMLHttpRequest();
                xhr.open('POST', cancelUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.onreadystatechange = function () {
                    if (xhr.readyState !== 4) {
                        return;
                    }

                    redirectToForm('payment=cancelled');
                };
                xhr.send('code=' + encodeURIComponent(paymentCode));
            }

            screen.querySelectorAll('[data-payment-back]').forEach(function (link) {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    cancelPaymentAndReturn();
                });
            });

            screen.querySelectorAll('[data-payment-cancel]').forEach(function (button) {
                button.addEventListener('click', function () {
                    cancelPaymentAndReturn();
                });
            });

            poll();
        });
    }());
    </script>
<?php endif; ?>
