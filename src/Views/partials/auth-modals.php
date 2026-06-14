<?php
$authIcons = [
    'user' => 'https://cdn-icons-png.flaticon.com/512/1077/1077114.png',
    'lock' => 'https://cdn-icons-png.flaticon.com/512/3064/3064155.png',
    'mail' => 'https://cdn-icons-png.flaticon.com/512/542/542689.png',
    'captcha' => 'https://cdn-icons-png.flaticon.com/512/565/565547.png',
    'eye' => 'https://cdn-icons-png.flaticon.com/512/709/709612.png',
];
$forgotStep = $forgotStep ?? 'request';
$registerValues = is_array($registerValues ?? null) ? $registerValues : [];
?>

<div class="auth-modal-backdrop <?= $authModal ? 'is-open' : '' ?>" data-auth-backdrop aria-hidden="<?= $authModal ? 'false' : 'true' ?>">
    <section class="auth-modal <?= $authModal === 'login' ? 'is-active' : '' ?>" data-auth-modal="login" role="dialog" aria-modal="true" aria-labelledby="login-title">
        <a class="auth-close" href="/" data-auth-close aria-label="Đóng">&times;</a>
        <header class="auth-modal-head">
            <h2 id="login-title">Đăng nhập</h2>
            <p>Chào mừng trở lại!</p>
        </header>
        <div class="auth-modal-body">
            <form class="form" method="post" action="/login">
                <label>
                    Tên đăng nhập
                    <span class="input-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['user']) ?>" alt=""></span>
                        <input name="username" autocomplete="username" placeholder="Nhập tên đăng nhập" required>
                    </span>
                </label>
                <label>
                    Mật khẩu
                    <span class="input-wrap password-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['lock']) ?>" alt=""></span>
                        <input name="password" type="password" autocomplete="current-password" placeholder="Nhập mật khẩu" required>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($authIcons['eye']) ?>" alt=""></button>
                    </span>
                </label>
                <a class="forgot-link" href="/forgot-password" data-auth-switch="forgot">Quên mật khẩu?</a>
                <button class="btn primary full" type="submit">Đăng nhập</button>
            </form>
            <p class="auth-switch">Chưa có tài khoản? <a href="/register" data-auth-switch="register">Đăng ký ngay</a></p>
        </div>
    </section>

    <section class="auth-modal forgot-modal <?= $authModal === 'forgot' ? 'is-active' : '' ?>" data-auth-modal="forgot" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
        <a class="auth-close" href="/" data-auth-close aria-label="Đóng">&times;</a>
        <header class="auth-modal-head">
            <h2 id="forgot-title">Quên mật khẩu</h2>
            <p>Khôi phục mật khẩu</p>
        </header>
        <div class="auth-modal-body">
            <?php if (!empty($forgotSuccess)): ?>
                <div class="alert success"><?= e($forgotSuccess) ?></div>
            <?php endif; ?>
            <?php if ($forgotStep === 'otp'): ?>
                <form class="form forgot-form" method="post" action="/forgot-password/verify-otp">
                    <label>
                        Mã OTP
                        <span class="input-wrap">
                            <span aria-hidden="true"><img src="<?= e($authIcons['captcha']) ?>" alt=""></span>
                            <input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="Nhập mã OTP 6 số" required>
                        </span>
                        <small class="form-hint">Mã OTP có hiệu lực trong 10 phút.</small>
                    </label>
                    <button class="btn primary full" type="submit">Xác thực OTP</button>
                </form>
                <form class="form forgot-form" method="post" action="/forgot-password">
                    <input type="hidden" name="username" value="<?= e($forgotUsername ?? '') ?>">
                    <input type="hidden" name="email" value="<?= e($forgotEmail ?? '') ?>">
                    <button class="btn secondary full" type="submit">Gửi lại mã OTP</button>
                </form>
            <?php elseif ($forgotStep === 'reset'): ?>
                <form class="form forgot-form" method="post" action="/forgot-password/reset">
                    <label>
                        Mật khẩu mới
                        <span class="input-wrap password-wrap">
                            <span aria-hidden="true"><img src="<?= e($authIcons['lock']) ?>" alt=""></span>
                            <input name="password" type="password" minlength="6" autocomplete="new-password" placeholder="Nhập mật khẩu mới" required>
                            <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($authIcons['eye']) ?>" alt=""></button>
                        </span>
                    </label>
                    <label>
                        Nhập lại mật khẩu mới
                        <span class="input-wrap password-wrap">
                            <span aria-hidden="true"><img src="<?= e($authIcons['lock']) ?>" alt=""></span>
                            <input name="confirm_password" type="password" minlength="6" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới" required>
                            <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($authIcons['eye']) ?>" alt=""></button>
                        </span>
                    </label>
                    <button class="btn primary full" type="submit">Đặt lại mật khẩu</button>
                </form>
            <?php else: ?>
                <form class="form forgot-form" method="post" action="/forgot-password">
                    <label>
                        Tên đăng nhập
                        <span class="input-wrap">
                            <span aria-hidden="true"><img src="<?= e($authIcons['user']) ?>" alt=""></span>
                            <input name="username" autocomplete="username" placeholder="Nhập tên đăng nhập" value="<?= e($forgotUsername ?? '') ?>" required>
                        </span>
                    </label>
                    <label>
                        Email đã đăng ký
                        <span class="input-wrap">
                            <span aria-hidden="true"><img src="<?= e($authIcons['mail']) ?>" alt=""></span>
                            <input name="email" type="email" autocomplete="email" placeholder="Nhập email đã đăng ký" value="<?= e($forgotEmail ?? '') ?>" required>
                        </span>
                        <small class="form-hint">Điền email đã thêm vào tài khoản để nhận mã OTP.</small>
                    </label>
                    <button class="btn primary full" type="submit">Gửi mã OTP</button>
                </form>
            <?php endif; ?>
            <p class="auth-switch"><a href="/login" data-auth-switch="login">Quay lại đăng nhập</a></p>
        </div>
    </section>

    <section class="auth-modal <?= $authModal === 'register' ? 'is-active' : '' ?>" data-auth-modal="register" role="dialog" aria-modal="true" aria-labelledby="register-title">
        <a class="auth-close" href="/" data-auth-close aria-label="Đóng">&times;</a>
        <header class="auth-modal-head">
            <h2 id="register-title">Đăng ký</h2>
            <p>Tạo tài khoản để bắt đầu hành trình ninja.</p>
        </header>
        <div class="auth-modal-body">
            <?php if (!empty($registerError)): ?>
                <div class="alert error"><?= e($registerError) ?></div>
            <?php endif; ?>
            <?php if (!empty($registerSubmitted)): ?>
                <div class="alert success">Đăng ký thành công. Bạn có thể đăng nhập ngay.</div>
            <?php endif; ?>
            <form class="form" method="post" action="/register" data-register-form>
                <label>
                    Tên đăng nhập
                    <span class="input-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['user']) ?>" alt=""></span>
                        <input name="username" minlength="3" maxlength="30" autocomplete="username" placeholder="Tên đăng nhập" value="<?= e((string) ($registerValues['username'] ?? '')) ?>" required>
                    </span>
                    <small class="form-hint">Chỉ gồm chữ, số hoặc dấu gạch dưới. Tối đa 30 ký tự.</small>
                </label>
                <label>
                    Email 
                    <span class="text-ninja-text-muted text-xs font-normal">(Không bắt buộc)</span>
                    <span class="input-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['mail']) ?>" alt=""></span>
                        <input name="email" type="email" maxlength="20" autocomplete="email" placeholder="Email nếu có" value="<?= e((string) ($registerValues['email'] ?? '')) ?>">
                    </span>
                    <small class="form-hint">Theo bảng user hiện tại, email tối đa 20 ký tự.</small>
                </label>
                <label>
                    Mật khẩu
                    <span class="input-wrap password-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['lock']) ?>" alt=""></span>
                        <input name="password" type="password" minlength="6" autocomplete="new-password" placeholder="Mật khẩu" required data-register-password>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($authIcons['eye']) ?>" alt=""></button>
                    </span>
                </label>
                <label>
                    Nhập lại mật khẩu
                    <span class="input-wrap password-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['lock']) ?>" alt=""></span>
                        <input name="confirm_password" type="password" minlength="6" autocomplete="new-password" placeholder="Nhập lại mật khẩu" required data-register-confirm-password>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><img src="<?= e($authIcons['eye']) ?>" alt=""></button>
                    </span>
                    <small class="form-error" data-register-password-error></small>
                </label>
                <label>
                    Captcha: <?= e($captchaQuestion ?? '3 + 4 = ?') ?>
                    <span class="input-wrap">
                        <span aria-hidden="true"><img src="<?= e($authIcons['captcha']) ?>" alt=""></span>
                        <input name="captcha" inputmode="numeric" autocomplete="off" placeholder="Nhập kết quả" required>
                    </span>
                </label>
                <button class="btn primary full" type="submit">Đăng ký</button>
            </form>
            <p class="auth-switch">Đã có tài khoản? <a href="/login" data-auth-switch="login">Đăng nhập</a></p>
        </div>
    </section>
</div>
