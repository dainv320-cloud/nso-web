<?php

$title = $title ?? 'Ninja School Blue';
$description = $description ?? 'Ninja School Blue - cổng thông tin game ninja với tin tức, tải game, nạp tiền và cộng đồng.';
$loggedUser = $_SESSION['user'] ?? null;
$authModal = $authModal ?? null;
$loginError = $loginError ?? null;
$registerError = $registerError ?? null;
$forgotError = $forgotError ?? null;
$registerSubmitted = $registerSubmitted ?? false;
$captchaQuestion = $captchaQuestion ?? null;
$toastSuccess = $toastSuccess ?? ($_SESSION['toast_success'] ?? null);
$toastError = $toastError ?? ($_SESSION['toast_error'] ?? null) ?? $loginError ?? $registerError ?? $forgotError;
unset($_SESSION['toast_success']);
unset($_SESSION['toast_error']);
$facebookUrl = env('SOCIAL_FACEBOOK_URL', 'https://www.facebook.com/');
$zaloUrl = env('SOCIAL_ZALO_URL', 'https://zalo.me/');
$paymentEnabled = in_array(strtolower((string) env('PAYMENT_ENABLED', 'true')), ['1', 'true', 't', 'yes', 'y'], true);
$accountName = (string) ($loggedUser['display_name'] ?? $loggedUser['username'] ?? '');
$accountInitial = strtoupper(substr($accountName !== '' ? $accountName : 'U', 0, 1));
$avatarColors = ['#0ea5c6', '#f97316', '#7c3aed', '#16a34a', '#dc2626', '#2563eb', '#db2777', '#0891b2'];
$avatarColor = $avatarColors[abs(crc32($accountName !== '' ? $accountName : 'user')) % count($avatarColors)];
$isAdminLayout = str_starts_with((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: ''), '/admin');
$bodyClasses = array_filter([
    $authModal ? 'auth-open' : '',
    $isAdminLayout ? 'admin-layout' : '',
]);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="/img/nso.webp">
    <link rel="icon" type="image/webp" href="/img/nso.webp">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/app.css?v=<?= filemtime(__DIR__ . '/../../public/assets/app.css') ?>">
</head>
<body class="<?= e(implode(' ', $bodyClasses)) ?>">
    <header class="site-header">
        <a class="brand" href="/" aria-label="Ninja School Blue trang chủ">
            <img class="brand-logo" src="/img/nso.webp" alt="Ninja School Blue logo">
            <span>
                <strong>Ninja School Blue</strong>
                <small>Huyền Thoại Nhẫn Giả</small>
            </span>
        </a>

        <nav class="nav" aria-label="Điều hướng chính">
            <a href="/">Trang chủ</a>
            <a href="/news">Tin tức</a>
            <?php if ($paymentEnabled): ?>
                <a href="/payment">Nạp tiền</a>
            <?php endif; ?>
            <a href="/download">Tải game</a>

            <?php if ($loggedUser): ?>
                <?php if (!empty($_SESSION['admin_login'])): ?>
                    <a href="/admin">Admin</a>
                <?php endif; ?>
                <div class="account-dropdown" data-account-menu>
                    <button class="account-trigger" type="button" data-account-toggle aria-expanded="false">
                        <span class="account-trigger-avatar" style="background: <?= e($avatarColor) ?>"><?= e($accountInitial) ?></span>
                        <span class="account-trigger-name"><?= e($accountName) ?></span>
                        <span class="account-trigger-caret" aria-hidden="true">⌃</span>
                    </button>
                    <div class="account-dropdown-menu" data-account-dropdown>
                        <a href="/profile">
                            <span class="account-menu-icon account-menu-icon-user" aria-hidden="true">●</span>
                            Tài khoản
                        </a>
                        <a href="/profile?tab=history">
                            <span class="account-menu-icon account-menu-icon-history" aria-hidden="true">↺</span>
                            Lịch sử giao dịch
                        </a>
                        <a href="/logout">
                            <span class="account-menu-icon account-menu-icon-logout" aria-hidden="true">↪</span>
                            Đăng xuất
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login" data-auth-open="login">Đăng nhập</a>
                <a class="nav-cta" href="/register" data-auth-open="register">Đăng ký</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (!empty($toastSuccess) || !empty($toastError)): ?>
        <div class="ns-toast-stack" aria-live="polite">
            <div class="ns-toast <?= !empty($toastError) ? 'ns-toast-error' : 'ns-toast-success' ?>" data-ns-toast>
                <span class="ns-toast-icon" aria-hidden="true"><?= !empty($toastError) ? '!' : '&#10003;' ?></span>
                <strong><?= e($toastError ?: $toastSuccess) ?></strong>
                <button type="button" class="ns-toast-close" data-ns-toast-close aria-label="Đóng">&times;</button>
                <span class="ns-toast-progress" aria-hidden="true"></span>
            </div>
        </div>
    <?php endif; ?>

    <main class="page-shell">
        <?= $content ?>
    </main>

    <aside class="social-tab" aria-label="Liên hệ nhanh">
        <a href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
            <img src="https://www.google.com/s2/favicons?domain=facebook.com&sz=64" alt="">
        </a>
        <a href="<?= e($zaloUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Zalo">
            <img src="https://www.google.com/s2/favicons?domain=zalo.me&sz=64" alt="">
        </a>
        <?php if ($loggedUser): ?>
            <a class="social-tab-feedback" href="/profile?tab=feedback" aria-label="Phản hồi">
                <img src="/img/icons/feedback.svg" alt="">
            </a>
        <?php endif; ?>
    </aside>

    <footer class="site-footer">
        <div class="footer-grid">
            <section class="footer-brand">
                <a class="footer-logo" href="/">
                    <img src="/img/logo-2.png" alt="Ninja School Blue logo">
                    <strong>Ninja School Blue</strong>
                </a>
                <p>Game mobile huyền thoại với lối chơi độc đáo, hệ thống đẹp mắt và cộng đồng sôi động.</p>
                <a class="footer-social" href="<?= e($facebookUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <img src="https://www.google.com/s2/favicons?domain=facebook.com&sz=64" alt="">
                </a>
            </section>

            <section>
                <h2>Liên kết nhanh</h2>
                <nav class="footer-links" aria-label="Liên kết nhanh">
                    <a href="/">Trang chủ</a>
                    <a href="/news">Tin tức</a>
                    <?php if ($paymentEnabled): ?>
                        <a href="/payment">Nạp tiền</a>
                    <?php endif; ?>
                    <a href="/download">Tải game</a>
                </nav>
            </section>

            <section>
                <h2>Hỗ trợ</h2>
                <nav class="footer-links" aria-label="Hỗ trợ">
                    <a href="/huong-dan">Hướng dẫn</a>
                    <a href="/canh-bao-lua-dao">Cảnh báo</a>
                    <a href="/canh-bao-lua-dao">Chính sách</a>
                    <a href="<?= e($zaloUrl) ?>" target="_blank" rel="noopener noreferrer">Liên hệ</a>
                </nav>
            </section>

            <section>
                <h2>Liên hệ</h2>
                <ul class="footer-contact">
                    <li><span>Email</span> support@ninjaschoolblue.vn</li>
                    <li><span>Tel</span> +84 386 360 519</li>
                    <li><span>Map</span> Ha Noi, Viet Nam</li>
                </ul>
            </section>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> Ninja School Blue. All rights reserved.</span>
            <span>Phát triển bởi Ninja School Blue</span>
        </div>
    </footer>

    <?php if (!$loggedUser): ?>
        <?php require __DIR__ . '/partials/auth-modals.php'; ?>
    <?php endif; ?>
    <script src="/assets/app.js?v=<?= filemtime(__DIR__ . '/../../public/assets/app.js') ?>" defer></script>
</body>
</html>
