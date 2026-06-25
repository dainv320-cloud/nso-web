<?php
$homeCategoryLabels = [
    'tin-tuc' => 'Tin tức',
    'su-kien' => 'Sự kiện',
    'tinh-nang' => 'Tính năng',
    'huong-dan' => 'Hướng dẫn',
    'thong-bao' => 'Thông báo',
];

$homeViewCount = static function (array $post): int {
    $seed = abs(crc32((string) ($post['slug'] ?? $post['title'] ?? 'home-news')));

    return 50 + ($seed % 300);
};

$homeFormatDate = static function (?string $date): string {
    if (!$date) {
        return date('d/m/Y');
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y', $timestamp) : $date;
};
?>

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
        <img src="/img/logo-3.png" alt="Ninja School Blue">
    </div>
</section>

<?php
$launchPopupDeadline = date('c', strtotime('+14 days'));
$launchPopupZaloUrl = env('SOCIAL_ZALO_URL', 'https://zalo.me/');
$launchPopupDownloadUrl = '/download';
$launchPopupOpenLabel = date('H:i d/m/Y', strtotime($launchPopupDeadline));
?>
<div
    class="launch-popup-backdrop"
    data-launch-popup-backdrop
    data-launch-popup-deadline="<?= e($launchPopupDeadline) ?>"
    aria-hidden="true"
>
    <section class="launch-popup" role="dialog" aria-modal="true" aria-labelledby="launch-popup-title">
        <button class="launch-popup-close" type="button" data-launch-popup-close aria-label="Đóng">&times;</button>
        <div class="launch-popup-media">
            <img src="/img/media/banner-test.jpg" alt="Sự kiện Ninja School Blue">
        </div>
        <div class="launch-popup-body">
            <div class="launch-popup-meta">
                <span class="launch-pill launch-pill-live">Đang diễn ra</span>
                <span class="launch-pill">Quan trọng</span>
                <span class="launch-pill launch-pill-date">Mở: <?= e($launchPopupOpenLabel) ?></span>
            </div>
            <h3 id="launch-popup-title">CHƠI THỬ NINJA SCHOOL BLUE: BÁO DANH HÔM NAY - NHẬN NGAY QUÀ LỚN!</h3>
            <p>
                Hỡi các nhẫn giả, cơ hội ngàn năm có một đây rồi! Cổng Test Game Ninja School Blue đã chính thức mở cửa.
                Đăng nhập và trải nghiệm ngay để nhận gói Hành trang Tân thủ siêu hiếm và vô vàn vật phẩm VIP.
                Số lượng quà có hạn, vào game ngay!
            </p>

            <section class="launch-popup-timer">
                <strong>Kết thúc sau</strong>
                <div class="launch-popup-inline-time" data-launch-popup-inline>Đang cập nhật...</div>
                <div class="launch-popup-countdown">
                    <article>
                        <span data-launch-days>00</span>
                        <small>ngày</small>
                    </article>
                    <article>
                        <span data-launch-hours>00</span>
                        <small>giờ</small>
                    </article>
                    <article>
                        <span data-launch-minutes>00</span>
                        <small>phút</small>
                    </article>
                    <article>
                        <span data-launch-seconds>00</span>
                        <small>giây</small>
                    </article>
                </div>
            </section>

            <div class="launch-popup-actions">
                <a class="launch-popup-cta launch-popup-cta-secondary" href="<?= e($launchPopupDownloadUrl) ?>">
                    Tải game
                </a>
                <a class="launch-popup-cta" href="<?= e($launchPopupZaloUrl) ?>" target="_blank" rel="noopener noreferrer">
                    Tham gia cộng đồng Zalo
                </a>
            </div>
        </div>
    </section>
</div>

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
            <article class="home-news-card">
                <a class="home-news-image" href="/<?= e($post['slug']) ?>">
                    <img src="<?= e($post['image_url'] ?: '/img/ninja-hero.webp') ?>" alt="<?= e($post['title']) ?>">
                    <span class="pill"><?= e($homeCategoryLabels[$post['category'] ?? ''] ?? ($post['category'] ?? 'Tin tức')) ?></span>
                </a>
                <div class="home-news-content">
                    <h3><?= e($post['title']) ?></h3>
                    <p class="home-news-subtitle"><?= e($homeCategoryLabels[$post['category'] ?? ''] ?? ($post['category'] ?? 'Tin tức')) ?></p>
                    <div class="home-news-footer">
                        <div class="home-news-meta">
                            <span><?= e($homeFormatDate($post['published_at'] ?? null)) ?></span>
                            <span><?= e((string) $homeViewCount($post)) ?></span>
                        </div>
                        <a class="home-news-readmore" href="/<?= e($post['slug']) ?>">Đọc <span>&rarr;</span></a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
