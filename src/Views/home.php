<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Huyền thoại nhẫn giả</p>
        <h1>Ninja School Blue</h1>
        <p class="lead">Cánh cổng Ninja School đã mở. Nhập hội ngay để luyện thuật, săn boss, tham gia sự kiện và viết nên huyền thoại của riêng bạn.</p>
        <div class="actions">
            <a class="btn primary" href="/download">Tải game ngay</a>
            <a class="btn secondary" href="/register">Đăng ký</a>
        </div>
    </div>
    <div class="hero-art">
        <img src="/img/logo.png" alt="Ninja School Blue">
    </div>
</section>

<section class="quick-actions">
    <a href="/download">
        <strong>Tải game</strong>
        <span>Android, Windows, iOS</span>
    </a>
    <?php if (in_array(strtolower((string) env('PAYMENT_ENABLED', 'true')), ['1', 'true', 't', 'yes', 'y'], true)): ?>
        <a href="/payment">
            <strong>Nạp tiền</strong>
            <span>Tạo QR nạp nhanh</span>
        </a>
    <?php endif; ?>
</section>

<section class="home-news-section">
    <div class="section-title">
        <p class="eyebrow">Cập nhật</p>
        <h2>Tin nổi bật</h2>
    </div>

    <div class="home-news-cards">
        <?php foreach ($posts as $post): ?>
            <a class="card" href="/<?= e($post['slug']) ?>">
                <img src="<?= e($post['image_url'] ?: '/img/ninja-hero.webp') ?>" alt="<?= e($post['title']) ?>">
                <span class="pill"><?= e($post['category']) ?></span>
                <h3><?= e($post['title']) ?></h3>
                <p><?= e($post['summary']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
