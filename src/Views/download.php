<?php
$downloadPlatforms = $downloadPlatforms ?? [];

$platformMeta = [
    'java' => [
        'iconClass' => 'download-platform-icon-download',
        'svg' => '<path fill="currentColor" d="M12 3a1 1 0 0 1 1 1v8.59l2.3-2.3a1 1 0 1 1 1.4 1.42l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 1 1 1.4-1.42l2.3 2.3V4a1 1 0 0 1 1-1ZM5 18a1 1 0 0 1 1 1h12a1 1 0 1 1 0 2H6a1 1 0 0 1-1-1Z"/>',
    ],
    'android' => [
        'iconClass' => 'download-platform-icon-android',
        'svg' => '<path fill="currentColor" d="M7.2 9.15h9.6c1.02 0 1.85.83 1.85 1.85v5.65c0 1.02-.83 1.85-1.85 1.85h-.55v1.75a1.25 1.25 0 1 1-2.5 0V18.5h-3.5v1.75a1.25 1.25 0 1 1-2.5 0V18.5H7.2a1.85 1.85 0 0 1-1.85-1.85V11c0-1.02.83-1.85 1.85-1.85Zm-3.1 1.2c.69 0 1.25.56 1.25 1.25v4.4a1.25 1.25 0 1 1-2.5 0v-4.4c0-.69.56-1.25 1.25-1.25Zm15.8 0c.69 0 1.25.56 1.25 1.25v4.4a1.25 1.25 0 1 1-2.5 0v-4.4c0-.69.56-1.25 1.25-1.25ZM8.17 5.1 6.95 3.88a.55.55 0 0 1 .78-.78L9.12 4.5A6.08 6.08 0 0 1 12 3.8c1.04 0 2.02.25 2.88.7l1.39-1.4a.55.55 0 0 1 .78.78L15.83 5.1a5.36 5.36 0 0 1 1.72 3H6.45a5.36 5.36 0 0 1 1.72-3Zm1.58 1.65a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm4.5 0a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z"/>',
    ],
    'windows' => [
        'iconClass' => 'download-platform-icon-windows',
        'svg' => '<path fill="currentColor" d="M3 5.1 10.8 4v7.35H3V5.1Zm9-1.28L21 2.5v8.85h-9V3.82ZM3 12.65h7.8V20L3 18.9v-6.25Zm9 0h9v8.85l-9-1.28v-7.57Z"/>',
    ],
    'ios' => [
        'iconClass' => 'download-platform-icon-apple',
        'svg' => '<path fill="currentColor" d="M16.46 13.03c-.02-2.13 1.74-3.15 1.82-3.2-1-1.46-2.55-1.66-3.09-1.68-1.3-.14-2.56.77-3.22.77-.67 0-1.69-.75-2.79-.73-1.43.02-2.76.84-3.49 2.13-1.5 2.6-.39 6.42 1.06 8.52.72 1.03 1.56 2.18 2.66 2.14 1.08-.04 1.48-.69 2.78-.69 1.29 0 1.65.69 2.79.67 1.16-.02 1.89-1.03 2.58-2.07.83-1.18 1.16-2.35 1.18-2.41-.03-.01-2.26-.87-2.29-3.45ZM14.34 6.77c.58-.73.98-1.72.87-2.72-.84.04-1.9.58-2.5 1.29-.54.63-1.03 1.66-.9 2.63.94.07 1.94-.48 2.53-1.2Z"/>',
    ],
];

$firstCurrentLabel = 'JAVA';

foreach ($downloadPlatforms as $platform) {
    if (!empty($platform['versions'])) {
        $firstCurrentLabel = (string) ($platform['current_label'] ?? $platform['name'] ?? $firstCurrentLabel);
        break;
    }
}
?>
<section class="download-page">
    <header class="download-heading">
        <p class="eyebrow">Cài đặt</p>
        <h1>Tải game</h1>
        <span class="download-current">Phiên bản đang có: <?= e($firstCurrentLabel) ?></span>
    </header>

    <div class="download-platforms">
        <?php foreach ($downloadPlatforms as $platform): ?>
            <?php
            $platformKey = (string) ($platform['key'] ?? 'java');
            $meta = $platformMeta[$platformKey] ?? $platformMeta['java'];
            $versions = is_array($platform['versions'] ?? null) ? $platform['versions'] : [];
            ?>
            <details class="download-platform">
                <summary>
                    <span class="download-platform-icon <?= e($meta['iconClass']) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $meta['svg'] ?></svg>
                    </span>
                    <span class="download-platform-copy">
                        <strong><?= e((string) ($platform['name'] ?? 'Download')) ?></strong>
                        <small><?= e((string) ($platform['subtitle'] ?? '')) ?></small>
                    </span>
                    <span class="download-platform-caret" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path fill="currentColor" d="M7.41 8.59 12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41Z"/></svg>
                    </span>
                </summary>

                <div class="download-version-grid">
                    <?php foreach ($versions as $version): ?>
                        <article class="download-version-card">
                            <span class="download-version-icon <?= e($meta['iconClass']) ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $meta['svg'] ?></svg>
                            </span>
                            <h2><?= e((string) ($version['title'] ?? 'Download')) ?></h2>
                            <dl>
                                <div>
                                    <dt>Phiên bản:</dt>
                                    <dd><?= e((string) ($version['version'] ?? '-')) ?></dd>
                                </div>
                                <div>
                                    <dt>Dung lượng:</dt>
                                    <dd><?= e((string) ($version['file_size'] ?? '-')) ?></dd>
                                </div>
                                <div>
                                    <dt>Yêu cầu:</dt>
                                    <dd><?= e((string) ($version['notes'] ?? '-')) ?></dd>
                                </div>
                            </dl>
                            <a
                                class="btn primary download-version-button"
                                href="<?= e((string) ($version['download_url'] ?? '#')) ?>"
                                <?= str_starts_with((string) ($version['download_url'] ?? ''), 'http') ? 'target="_blank" rel="noopener noreferrer"' : 'download' ?>
                            >Tải xuống</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>

    <div class="download-guide-link">
        <a class="btn secondary" href="/huong-dan">Xem hướng dẫn cài đặt</a>
    </div>
</section>
<script>
    document.querySelectorAll('.download-platform').forEach((platform) => {
        platform.addEventListener('toggle', () => {
            if (!platform.open) {
                return;
            }

            document.querySelectorAll('.download-platform').forEach((otherPlatform) => {
                if (otherPlatform !== platform) {
                    otherPlatform.open = false;
                }
            });
        });
    });
</script>
