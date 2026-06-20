<?php
$section = $section ?? 'dashboard';
$flash = $flash ?? null;
$isCollaborator = (int) ($admin['role'] ?? 0) === 1;
$adminMenu = $isCollaborator
    ? [
        'posts' => ['/admin/posts', 'Tin tức'],
    ]
    : [
        'dashboard' => ['/admin', 'Tổng quan'],
        'users' => ['/admin/users', 'Tài khoản'],
        'posts' => ['/admin/posts', 'Tin tức'],
        'payments' => ['/admin/payments', 'Payment'],
        'feedbacks' => ['/admin/feedbacks', 'Phản hồi'],
        'banks' => ['/admin/banks', 'Tài khoản ngân hàng'],
        'downloads' => ['/admin/downloads', 'Tải xuống'],
        'rates' => ['/admin/rates', 'Rate & KM'],
    ];
?>

<section class="admin-shell">
    <aside class="admin-sidebar panel">
        <div>
            <p class="eyebrow"><?= $isCollaborator ? 'Cộng tác viên' : 'Admin' ?></p>
            <h1>Quản trị</h1>
            <p><?= e($admin['username'] ?? '') ?></p>
        </div>
        <nav class="admin-menu" aria-label="Admin menu">
            <?php foreach ($adminMenu as $key => [$href, $label]): ?>
                <a class="<?= $section === $key ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <a class="btn secondary" href="/admin/logout">Thoát admin</a>
    </aside>

    <div class="admin-content">
        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type'] ?? 'success') ?>"><?= e($flash['message'] ?? '') ?></div>
        <?php endif; ?>

        <?= $adminContent ?? '' ?>
    </div>
</section>
