<?php
$account = $account ?? null;
$transactions = $transactions ?? [];
$feedbackHistory = $feedbackHistory ?? [];
$feedbackValues = $feedbackValues ?? [];
$activeTab = in_array($activeTab ?? 'info', ['info', 'history', 'settings', 'feedback'], true) ? $activeTab : 'info';
$showEmailModal = ($_GET['modal'] ?? '') === 'email' || !empty($emailError);
$showPasswordModal = ($_GET['modal'] ?? '') === 'password' || !empty($passwordError);
$profileIcons = [
    'user' => 'https://cdn-icons-png.flaticon.com/512/1077/1077114.png',
    'history' => 'https://cdn-icons-png.flaticon.com/512/3503/3503786.png',
    'settings' => 'https://cdn-icons-png.flaticon.com/512/3524/3524659.png',
    'feedback' => '/img/icons/feedback.svg',
    'feedback_bug' => '/img/icons/feedback-bug.svg',
    'feedback_feature' => '/img/icons/feedback-feature.svg',
    'email' => 'https://cdn-icons-png.flaticon.com/512/542/542689.png',
    'lock' => 'https://cdn-icons-png.flaticon.com/512/3064/3064155.png',
    'key' => 'https://cdn-icons-png.flaticon.com/512/2889/2889676.png',
    'eye' => 'https://cdn-icons-png.flaticon.com/512/709/709612.png',
];

$formatMoney = static fn (mixed $value): string => number_format((float) $value, 0, ',', '.') . ' xu';
$formatDate = static function (?string $date): string {
    if (!$date) {
        return date('H:i:s d/m/Y');
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('H:i:s d/m/Y', $timestamp) : $date;
};

$statusLabel = static fn (string $status): string => match ($status) {
    'success' => 'Thành công',
    'failed' => 'Thất bại',
    default => 'Đang xử lý',
};
$feedbackTypeLabel = static fn (string $type): string => $type === 'bug' ? 'Báo lỗi' : 'Đề xuất tính năng';
$feedbackStatusLabel = static fn (string $status): string => match ($status) {
    'done' => 'Đã xử lý',
    'reviewing' => 'Đang xem',
    default => 'Mới gửi',
};
?>

<?php if (!empty($user) && $account): ?>
    <section class="account-page">
        <aside class="account-sidebar">
            <div class="account-card">
                <div class="account-cover"></div>
                <div class="account-avatar"><?= e(strtoupper(substr((string) $account['username'], 0, 1))) ?></div>
                <h1><?= e($account['username']) ?></h1>
                <p>Tham gia: <?= e($formatDate($user['login_at'] ?? null)) ?></p>
            </div>

            <nav class="account-menu" aria-label="Menu tài khoản">
                <a class="<?= $activeTab === 'info' ? 'active' : '' ?>" href="/profile?tab=info">
                    <span><img src="<?= e($profileIcons['user']) ?>" alt=""></span> Thông tin
                </a>
                <a class="<?= $activeTab === 'history' ? 'active' : '' ?>" href="/profile?tab=history">
                    <span><img src="<?= e($profileIcons['history']) ?>" alt=""></span> Lịch sử giao dịch
                </a>
                <a class="<?= $activeTab === 'settings' ? 'active' : '' ?>" href="/profile?tab=settings">
                    <span><img src="<?= e($profileIcons['settings']) ?>" alt=""></span> Cài đặt
                </a>
                <a class="<?= $activeTab === 'feedback' ? 'active' : '' ?>" href="/profile?tab=feedback">
                    <span><img src="<?= e($profileIcons['feedback']) ?>" alt=""></span> Phản hồi
                </a>
            </nav>
        </aside>

        <section class="account-panel">
            <?php if ($activeTab === 'history'): ?>
                <header class="account-panel-head">
                    <h2>Lịch sử giao dịch</h2>
                    <p>Các giao dịch nạp tiền gần đây của tài khoản.</p>
                </header>

                <?php if ($transactions !== []): ?>
                    <div class="account-table-wrap">
                        <table class="account-table">
                            <thead>
                                <tr>
                                    <th>Mã GD</th>
                                    <th>Phương thức</th>
                                    <th>Số tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thời gian</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?= e($transaction['transaction_code'] ?: '#' . $transaction['id']) ?></td>
                                        <td><?= e($transaction['payment_method']) ?></td>
                                        <td><?= e($formatMoney($transaction['amount'])) ?></td>
                                        <td>
                                            <span class="tx-status <?= e($transaction['status']) ?>">
                                                <?= e($statusLabel((string) $transaction['status'])) ?>
                                            </span>
                                        </td>
                                        <td><?= e($formatDate($transaction['created_at'] ?? null)) ?></td>
                                    </tr>
                                    <?php if (!empty($transaction['description'])): ?>
                                        <tr class="tx-note">
                                            <td colspan="5"><?= e($transaction['description']) ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="account-empty">
                        <strong>Chưa có giao dịch</strong>
                        <p>Lịch sử nạp tiền sẽ hiển thị tại đây sau khi có giao dịch.</p>
                        <a class="btn primary" href="/payment">Nạp tiền</a>
                    </div>
                <?php endif; ?>
            <?php elseif ($activeTab === 'settings'): ?>
                <header class="account-panel-head">
                    <h2>Cài đặt tài khoản</h2>
                </header>

                <?php if (!empty($emailSuccess)): ?>
                    <div class="alert success"><?= e($emailSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($emailError)): ?>
                    <div class="alert error"><?= e($emailError) ?></div>
                <?php endif; ?>
                <?php if (!empty($passwordSuccess)): ?>
                    <div class="alert success"><?= e($passwordSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($passwordError)): ?>
                    <div class="alert error"><?= e($passwordError) ?></div>
                <?php endif; ?>

                <div class="settings-list">
                    <button type="button" data-profile-modal-open="email">
                        <span><img src="<?= e($profileIcons['email']) ?>" alt=""></span>
                        <strong><?= !empty($account['email']) ? e($account['email']) : 'Thêm email' ?></strong>
                        <em>&rsaquo;</em>
                    </button>
                    <button type="button" data-profile-modal-open="password">
                        <span><img src="<?= e($profileIcons['lock']) ?>" alt=""></span>
                        <strong>Đổi mật khẩu</strong>
                        <em>&rsaquo;</em>
                    </button>
                </div>
            <?php elseif ($activeTab === 'feedback'): ?>
                <header class="account-panel-head">
                    <h2>Gửi phản hồi</h2>
                    <p>Báo lỗi hoặc đề xuất tính năng mới cho hệ thống.</p>
                </header>

                <?php if (!empty($feedbackSuccess)): ?>
                    <div class="alert success"><?= e($feedbackSuccess) ?></div>
                <?php endif; ?>
                <?php if (!empty($feedbackError)): ?>
                    <div class="alert error"><?= e($feedbackError) ?></div>
                <?php endif; ?>

                <form class="panel form feedback-form" method="post" action="/profile/feedback">
                    <label>Loại phản hồi
                        <select name="type" required>
                            <option value="bug" <?= ($feedbackValues['type'] ?? '') === 'bug' ? 'selected' : '' ?>>Báo lỗi</option>
                            <option value="feature" <?= ($feedbackValues['type'] ?? '') === 'feature' ? 'selected' : '' ?>>Đề xuất tính năng</option>
                        </select>
                    </label>
                    <label>Tiêu đề
                        <input name="subject" type="text" maxlength="180" value="<?= e((string) ($feedbackValues['subject'] ?? '')) ?>" placeholder="Tóm tắt ngắn gọn vấn đề" required>
                    </label>
                    <label>Nội dung
                        <textarea name="content" rows="5" placeholder="Mô tả chi tiết lỗi hoặc đề xuất của bạn" required><?= e((string) ($feedbackValues['content'] ?? '')) ?></textarea>
                    </label>
                    <button class="btn primary" type="submit">Gửi phản hồi</button>
                </form>

                <div class="feedback-history">
                    <h3>Phản hồi đã gửi</h3>
                    <?php if ($feedbackHistory !== []): ?>
                        <?php foreach ($feedbackHistory as $item): ?>
                            <article class="feedback-card">
                                <div class="feedback-card-head">
                                    <strong>
                                        <img class="feedback-inline-icon" src="<?= e((string) (($item['type'] ?? '') === 'bug' ? $profileIcons['feedback_bug'] : $profileIcons['feedback_feature'])) ?>" alt="">
                                        <?= e((string) ($item['subject'] ?? '')) ?>
                                    </strong>
                                    <span><?= e($feedbackTypeLabel((string) ($item['type'] ?? 'feature'))) ?> | <?= e($feedbackStatusLabel((string) ($item['status'] ?? 'new'))) ?></span>
                                </div>
                                <p><?= e((string) ($item['content'] ?? '')) ?></p>
                                <small><?= e($formatDate($item['created_at'] ?? null)) ?></small>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="account-empty feedback-empty">
                            <strong>Chưa có phản hồi nào</strong>
                            <p>Khi bạn gửi phản hồi, lịch sử sẽ hiển thị tại đây.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <header class="account-panel-head">
                    <h2>Thông tin tài khoản</h2>
                    <p>Tổng quan trạng thái và số tiền hiện tại.</p>
                </header>

                <div class="account-stats-grid">
                    <article>
                        <span>Số tiền</span>
                        <strong><?= e($formatMoney($account['balance'] ?? 0)) ?></strong>
                    </article>
                    <article>
                        <span>Tổng nạp</span>
                        <strong><?= e($formatMoney($account['tongnap'] ?? 0)) ?></strong>
                    </article>
                    <article>
                        <span>T盻貧g n蘯｡p thﾃ｡ng</span>
                        <strong><?= e($formatMoney($account['tongNapThang'] ?? 0)) ?></strong>
                    </article>
                    <article>
                        <span>T盻貧g n蘯｡p tu盻ｧn</span>
                        <strong><?= e($formatMoney($account['tongNapTuan'] ?? 0)) ?></strong>
                    </article>
                    <article>
                        <span><img src="<?= e($profileIcons['email']) ?>" alt=""></span>
                        <strong><?= !empty($account['email']) ? e($account['email']) : 'Chưa thêm' ?></strong>
                    </article>
                    <article>
                        <span>Trạng thái</span>
                        <strong><?= !empty($account['ban']) ? 'Bị khóa' : 'Hoạt động' ?></strong>
                    </article>
                </div>

                <div class="account-actions">
                    <a class="btn primary" href="/payment">Nạp tiền</a>
                    <a class="btn secondary" href="/profile?tab=history">Xem lịch sử</a>
                    <a class="btn secondary" href="/logout">Đăng xuất</a>
                </div>
            <?php endif; ?>
        </section>
    </section>

    <div class="profile-modal-backdrop <?= $showEmailModal ? 'is-open' : '' ?>" data-profile-modal="email" aria-hidden="<?= $showEmailModal ? 'false' : 'true' ?>">
        <section class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="email-modal-title">
            <button class="profile-modal-close" type="button" data-profile-modal-close aria-label="Đóng">&times;</button>
            <h2 id="email-modal-title">Cập nhật email</h2>
            <form class="form" method="post" action="/profile/email">
                <label>
                    Mật khẩu xác thực
                    <span class="input-wrap">
                        <span aria-hidden="true"><img src="<?= e($profileIcons['lock']) ?>" alt=""></span>
                        <input name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu" required>
                    </span>
                </label>
                <label>
                    Email mới
                    <span class="input-wrap">
                        <span aria-hidden="true"><img src="<?= e($profileIcons['email']) ?>" alt=""></span>
                        <input name="email" type="email" autocomplete="email" placeholder="Nhập email" value="<?= e((string) ($account['email'] ?? '')) ?>" required>
                    </span>
                </label>
                <button class="btn primary full" type="submit">Cập nhật email</button>
            </form>
        </section>
    </div>

    <div class="profile-modal-backdrop <?= $showPasswordModal ? 'is-open' : '' ?>" data-profile-modal="password" aria-hidden="<?= $showPasswordModal ? 'false' : 'true' ?>">
        <section class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="password-modal-title">
            <button class="profile-modal-close" type="button" data-profile-modal-close aria-label="Đóng">&times;</button>
            <h2 id="password-modal-title">Đổi mật khẩu</h2>
            <form class="form" method="post" action="/profile/password">
                <label>
                    Mật khẩu hiện tại
                    <span class="input-wrap password-wrap">
                        <span aria-hidden="true"><img src="<?= e($profileIcons['lock']) ?>" alt=""></span>
                        <input name="current_password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu hiện tại" required>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($profileIcons['eye']) ?>" alt=""></button>
                    </span>
                </label>
                <label>
                    Mật khẩu mới
                    <span class="input-wrap password-wrap">
                        <span aria-hidden="true"><img src="<?= e($profileIcons['key']) ?>" alt=""></span>
                        <input name="new_password" type="password" autocomplete="new-password" minlength="6" placeholder="Nhập mật khẩu mới" required>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($profileIcons['eye']) ?>" alt=""></button>
                    </span>
                </label>
                <label>
                    Xác nhận mật khẩu mới
                    <span class="input-wrap password-wrap">
                        <span aria-hidden="true"><img src="<?= e($profileIcons['key']) ?>" alt=""></span>
                        <input name="confirm_password" type="password" autocomplete="new-password" minlength="6" placeholder="Nhập lại mật khẩu mới" required>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($profileIcons['eye']) ?>" alt=""></button>
                    </span>
                </label>
                <button class="btn primary full" type="submit">Đổi mật khẩu</button>
            </form>
        </section>
    </div>
<?php else: ?>
    <section class="panel empty-state">
        <h2>Chưa đăng nhập</h2>
        <p>Đăng nhập hoặc tạo tài khoản để xem thông tin tài khoản và lịch sử giao dịch.</p>
        <div class="actions center">
            <a class="btn primary" href="/login">Đăng nhập</a>
            <a class="btn secondary" href="/register">Đăng ký</a>
        </div>
    </section>
<?php endif; ?>
