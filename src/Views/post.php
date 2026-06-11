<?php
$relatedPosts = $relatedPosts ?? [];
$formatDate = static function (?string $date): string {
    if (!$date) {
        return date('d/m/Y');
    }

    $timestamp = strtotime($date);

    return $timestamp ? date('d/m/Y', $timestamp) : $date;
};
?>

<article class="article ninja-card">
    <a class="back-link" href="/news">&larr; Quay lại tin tức</a>
    <img class="article-image" src="<?= e(($post['image_url'] ?? '') ?: '/img/ninja-hero.webp') ?>" alt="">
    <div class="article-meta">
        <span class="pill"><?= e($post['category'] ?? '') ?></span>
        <span class="muted"><?= e($post['published_at'] ?? '') ?></span>
    </div>
    <h3 class="article-title"><?= e($post['title'] ?? '') ?></h3>
    <div class="article-body">
        <?php
        $content = (string) ($post['content'] ?? '');

        if (trim($content) === '') {
            $content = (string) ($post['summary'] ?? '');
        }

        if (str_contains($content, '<')) {
            $content = preg_replace('/<\s*(script|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $content) ?? '';
            $content = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $content) ?? '';
            $content = preg_replace('/javascript\s*:/i', '', $content) ?? '';
            echo $content;
        } else {
            foreach (preg_split('/\n+/', $content) as $paragraph) {
                if (trim($paragraph) === '') {
                    continue;
                }

                echo '<p>' . e($paragraph) . '</p>';
            }
        }
        ?>
    </div>
</article>

<?php if ($relatedPosts !== []): ?>
    <section class="related-news">
        <h2>Bài viết cùng chuyên mục</h2>
        <div class="related-news-grid">
            <?php foreach (array_slice($relatedPosts, 0, 6) as $relatedPost): ?>
                <a class="related-news-card ninja-card" href="/<?= e($relatedPost['slug'] ?? '') ?>">
                    <img src="<?= e(($relatedPost['image_url'] ?? '') ?: '/img/post-default.webp') ?>" alt="<?= e($relatedPost['title'] ?? '') ?>">
                    <div class="related-news-content">
                        <span class="news-badge"><?= e($relatedPost['category'] ?? '') ?></span>
                        <h3><?= e($relatedPost['title'] ?? '') ?></h3>
                        <?php if (!empty($relatedPost['summary'])): ?>
                            <p><?= e($relatedPost['summary']) ?></p>
                        <?php endif; ?>
                        <span class="related-news-date"><?= e($formatDate($relatedPost['published_at'] ?? null)) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
