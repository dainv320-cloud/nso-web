<section class="admin-login">
    <form class="panel form admin-login-card" method="post" action="/admin/login">
        <p class="eyebrow">Admin</p>
        <h1>Đăng nhập quản trị</h1>
        <p>Chi tài khoản có quyền admin mới được vào khu vực này.</p>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <label>
            Tên đăng nhập
            <input name="username" autocomplete="username" required>
        </label>
        <label>
            Mật khẩu
            <input name="password" type="password" autocomplete="current-password" required>
        </label>
        <button class="btn primary" type="submit">Đăng nhập admin</button>
    </form>
</section>
