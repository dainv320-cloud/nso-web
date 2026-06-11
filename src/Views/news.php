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

$formatDate = static function (?string $date): string {
    if (!$date) {
        return date('d/m/Y');
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y', $timestamp) : $date;
};
?>

<section class="news-page">
    <div class="news-shell">
        <header class="news-heading">
            <h1>Tin tức & Sự kiện</h1>
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
                                <span class="news-badge"><?= e($post['category']) ?></span>
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

        <?php if ($featuredPosts !== []): ?>
            <section class="feature-news-list">
                <?php foreach ($featuredPosts as $post): ?>
                    <article class="feature-news-card">
                        <a class="feature-news-image" href="/<?= e($post['slug']) ?>">
                            <img src="<?= e($post['image_url'] ?: '/img/post-default.webp') ?>" alt="<?= e($post['title']) ?>">
                            <span class="news-badge floating"><?= e($post['category']) ?></span>
                        </a>
                        <div class="feature-news-content">
                            <div class="announcement-meta">
                                <span><?= e($formatDate($post['published_at'] ?? null)) ?></span>
                            </div>
                            <h2><?= e($post['title']) ?></h2>
                            <p><?= e($post['summary']) ?></p>
                            <a class="news-readmore" href="/<?= e($post['slug']) ?>">Đọc thêm <span>&rarr;</span></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
