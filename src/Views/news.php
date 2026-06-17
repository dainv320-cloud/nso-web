<?php
$categories = [
    null => 'Tất cả',
    'tin-tuc' => 'Tin tức',
    'su-kien' => 'Sự kiện',
    'tinh-nang' => 'Tính năng',
    'huong-dan' => 'Hướng dẫn',
    'thong-bao' => 'Thông báo',
];

$announcements = array_values(array_filter($posts, fn (array $post): bool => ($post['category'] ?? '') === 'thong-bao'));
$featuredPosts = array_values(array_filter($posts, fn (array $post): bool => ($post['category'] ?? '') !== 'thong-bao'));

if ($announcements === [] && $posts !== []) {
    $announcements = array_slice($posts, 0, min(2, count($posts)));
    $featuredPosts = array_slice($posts, count($announcements));
}

$heroPost = $featuredPosts[0] ?? null;
$gridPosts = array_slice($featuredPosts, 1);

$formatDate = static function (?string $date): string {
    if (!$date) {
        return date('d/m/Y');
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y', $timestamp) : $date;
};

$categoryLabel = static function (?string $category) use ($categories): string {
    return $categories[$category] ?? ($category ?: 'Tin tức');
};

$viewCount = static function (array $post): int {
    $seed = abs(crc32((string) ($post['slug'] ?? $post['title'] ?? 'news')));

    return 50 + ($seed % 300);
};
?>

<section class="news-page">
    <div class="news-shell">
        <header class="news-heading">
            <h1>Tin tức &amp; Sự kiện</h1>
            <p>Cập nhật thông tin mới nhất về game</p>
        </header>

        <nav class="news-tabs" aria-label="Danh mục tin tức">
            <?php foreach ($categories as $key => $label): ?>
                <a class="<?= $activeCategory === $key ? 'active' : '' ?>" href="<?= $key ? '/' . e($key) : '/news' ?>">
                    <?= e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($announcements !== []): ?>
            <section class="news-block">
                <h2><span>!</span> Thông báo</h2>
                <div class="announcement-list">
                    <?php foreach (array_slice($announcements, 0, 3) as $post): ?>
                        <a class="announcement-item" href="/<?= e($post['slug']) ?>">
                            <div class="announcement-meta">
                                <span class="news-badge"><?= e($categoryLabel($post['category'] ?? null)) ?></span>
                                <span><?= e($formatDate($post['published_at'] ?? null)) ?></span>
                            </div>
                            <strong><?= e($post['title']) ?></strong>
                            <p><?= e($post['summary']) ?></p>
                            <span class="announcement-arrow">&rarr;</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($heroPost): ?>
            <section class="feature-news-list">
                <article class="feature-news-card">
                    <a class="feature-news-image" href="/<?= e($heroPost['slug']) ?>">
                        <img src="<?= e($heroPost['image_url'] ?: '/img/post-default.webp') ?>" alt="<?= e($heroPost['title']) ?>">
                        <span class="news-badge floating"><?= e($categoryLabel($heroPost['category'] ?? null)) ?></span>
                    </a>
                    <div class="feature-news-content">
                        <div class="announcement-meta">
                            <span><?= e($formatDate($heroPost['published_at'] ?? null)) ?></span>
                        </div>
                        <h2><?= e($heroPost['title']) ?></h2>
                        <p><?= e($heroPost['summary']) ?></p>
                        <a class="news-readmore" href="/<?= e($heroPost['slug']) ?>">Đọc thêm <span>&rarr;</span></a>
                    </div>
                </article>
            </section>
        <?php endif; ?>

        <?php if ($gridPosts !== []): ?>
            <section class="news-thumb-grid">
                <?php foreach ($gridPosts as $post): ?>
                    <article class="news-thumb-card">
                        <a class="news-thumb-image" href="/<?= e($post['slug']) ?>">
                            <img src="<?= e($post['image_url'] ?: '/img/post-default.webp') ?>" alt="<?= e($post['title']) ?>">
                            <span class="news-badge floating"><?= e($categoryLabel($post['category'] ?? null)) ?></span>
                        </a>
                        <div class="news-thumb-content">
                            <h3><?= e($post['title']) ?></h3>
                            <p class="news-thumb-subtitle"><?= e($categoryLabel($post['category'] ?? null)) ?></p>
                            <div class="news-thumb-footer">
                                <div class="news-thumb-meta">
                                    <span><?= e($formatDate($post['published_at'] ?? null)) ?></span>
                                    <span><?= e((string) $viewCount($post)) ?></span>
                                </div>
                                <a class="news-thumb-readmore" href="/<?= e($post['slug']) ?>">Đọc <span>&rarr;</span></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
