<?php

declare(strict_types=1);

namespace App;

use PDO;
use Throwable;

final class ContentRepository
{
    private const FALLBACK_NEWS_IMAGES = [
        '/img/ninja-hero.webp',
        '/img/ns2d-ninja.webp',
        '/img/post-default.webp',
        '/img/bg.png',
        '/img/bg.png',
        '/img/media/banner-test.jpg',
        '/img/media/Char.png',
    ];
    private ?array $postsTableSchema = null;

    public function featuredPosts(): array
    {
        return $this->normalizePosts($this->fetch(
            'select * from posts
             where status = :status
             order by ' . $this->postsOrderBy(),
            ['status' => 'published'],
            $this->fallbackPosts()
        ));
    }

    public function posts(?string $category = null): array
    {
        $fallback = $this->fallbackPosts();

        if ($category === null) {
            return $this->normalizePosts($this->fetch(
                'select * from posts
                 where status = :status
                 order by ' . $this->postsOrderBy(),
                ['status' => 'published'],
                $fallback
            ));
        }

        return $this->normalizePosts($this->fetch(
            'select * from posts
             where status = :status and category = :category
             order by ' . $this->postsOrderBy(),
            ['status' => 'published', 'category' => $category],
            array_values(array_filter($fallback, fn (array $post): bool => $post['category'] === $category))
        ));
    }

    public function post(string $slug): ?array
    {
        $posts = $this->fetch(
            'select * from posts where slug = :slug and status = :status limit 1',
            ['slug' => $slug, 'status' => 'published'],
            []
        );

        if ($posts !== []) {
            return $this->normalizePost($posts[0]);
        }

        foreach ($this->fallbackPosts() as $post) {
            if ($post['slug'] === $slug) {
                return $this->normalizePost($post);
            }
        }

        return null;
    }

    public function relatedPosts(string $category, string $slug, int $limit = 6): array
    {
        $fallback = array_values(array_filter(
            $this->fallbackPosts(),
            fn (array $post): bool => ($post['category'] ?? '') === $category && ($post['slug'] ?? '') !== $slug
        ));

        return array_slice($this->normalizePosts($this->fetch(
            'select * from posts
             where status = :status and category = :category and slug <> :slug
             order by ' . $this->postsOrderBy() . '
             limit ' . max(1, $limit),
            ['status' => 'published', 'category' => $category, 'slug' => $slug],
            $fallback
        )), 0, $limit);
    }

    public function downloads(): array
    {
        return $this->fetch(
            'select * from downloads where is_active = true order by sort_order asc, id asc',
            [],
            [
                ['platform' => 'Android', 'version' => '1.0.0', 'file_size' => '95 MB', 'download_url' => '#', 'notes' => 'APK dảnh chờ Android 8 tro len'],
                ['platform' => 'Windows', 'version' => '1.0.0', 'file_size' => '120 MB', 'download_url' => '#', 'notes' => 'Ban chời tren PC'],
                ['platform' => 'iOS', 'version' => 'Coming soon', 'file_size' => '-', 'download_url' => '#', 'notes' => 'Đang chuẩn bị'],
            ]
        );
    }

    public function downloadPlatforms(): array
    {
        $downloads = $this->downloads();
        $platformDownloads = $this->groupDownloadsByPlatform($downloads);

        return [
            [
                'key' => 'java',
                'name' => 'JAVA',
                'subtitle' => 'Danh sách đầy đủ các bản JAR đã tải lên.',
                'current_label' => 'JAVA',
                'versions' => $this->javaDownloadVersions($platformDownloads['java'] ?? []),
            ],
            [
                'key' => 'android',
                'name' => 'Android',
                'subtitle' => 'Phiên bản APK dành cho điện thoại Android.',
                'current_label' => 'Android',
                'versions' => $this->androidDownloadVersions($platformDownloads['android'] ?? []),
            ],
            [
                'key' => 'windows',
                'name' => 'Windows',
                'subtitle' => 'Phiên bản PC và giả lập sẵn sàng tải xuống.',
                'current_label' => 'Windows',
                'versions' => $this->windowsDownloadVersions($platformDownloads['windows'] ?? []),
            ],
            [
                'key' => 'ios',
                'name' => 'iOS',
                'subtitle' => 'Phiên bản dành cho thiết bị Apple.',
                'current_label' => 'iOS',
                'versions' => $this->iosDownloadVersions($platformDownloads['ios'] ?? []),
            ],
        ];
    }

    public function stats(): array
    {
        return [
            ['label' => 'Nguoi chời', 'value' => '50K+'],
            ['label' => 'May chu', 'value' => '12'],
            ['label' => 'Su kien', 'value' => '24/7'],
        ];
    }

    private function fetch(string $sql, array $params, array $fallback): array
    {
        try {
            $statement = Database::connection()->prepare($sql);
            $statement->execute($params);

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: $fallback;
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function normalizePosts(array $posts): array
    {
        return array_map(fn (array $post): array => $this->normalizePost($post), $posts);
    }

    private function normalizePost(array $post): array
    {
        $imageUrl = trim((string) ($post['image_url'] ?? ''));

        if ($imageUrl === '') {
            $post['image_url'] = $this->fallbackImageForPost($post);
        }

        return $post;
    }

    private function fallbackImageForPost(array $post): string
    {
        $seedSource = (string) ($post['slug'] ?? $post['title'] ?? $post['category'] ?? 'ninja-school');
        $seed = abs(crc32($seedSource));

        return self::FALLBACK_NEWS_IMAGES[$seed % count(self::FALLBACK_NEWS_IMAGES)];
    }

    private function fallbackPosts(): array
    {
        return [
            [
                'title' => 'Hướng dẫn di hang dong 9x Ninja Schờol 2D',
                'slug' => 'huong-dan-di-hang-dong-9x-ninja-schờol-2d',
                'category' => 'huong-dan',
                'summary' => 'Cach chưan bi va di hang dong 9x hieu qua chờ nguoi chời Ninja Schờol 2D.',
                'content' => "Hang dong 9x la khu vuc phu hop chờ nhan vat cap cao muon luyen cap, san vat pham va phoi hop to doi.\nTruoc khi vao hang, hay chưan bi day mau, chakra, thuc an tang chi so va sửa lai trang bi de trảnh bi ngat giua duong.\nNen di theo nhom co sat thuong, không che va ho tro hoi phuc. Khi gap quai dong, uu tien dung ky nang dien rong va giu khoang cach an toan.\nTrong luc di hang dong 9x, hay tap trung clear quai theo tung cum, trảnh tach doi hinh qua xa va cảnh thoi gian hoi sinh cua quai de toi uu kinh nghiem.\nNeu muc tieu la san do, nen thong nhat cach chia vat pham truoc khi vao map va uu tien nhung tang co quai phu hop voi suc mảnh cua doi.",
                'image_url' => '/img/post-default.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'is_featured' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'Khai mo may chu Lang Gio',
                'slug' => 'khai-mo-may-chu-lang-gio',
                'category' => 'thong-bao',
                'summary' => 'May chu moi mo cung chuoi qua đăng nhập chờ tan thu.',
                'content' => 'May chu Lang Gio mo cua voi nhiem vu tan thu, phan thuong đăng nhập va dua top luc chien trong tuan dau.',
                'image_url' => '/img/post-default.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'is_featured' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'Su kien san boss cuoi tuan',
                'slug' => 'su-kien-san-boss-cuoi-tuan',
                'category' => 'su-kien',
                'summary' => 'Boss the gioi xuat hien theo khung gio co dinh voi vat pham hiem.',
                'content' => 'Nguoi chời tham gia san boss co co hoi nhan trang bi, da cuong hoa va dảnh hieu gioi han.',
                'image_url' => '/img/ns2d-ninja.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'is_featured' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'Hướng dẫn nạp an toàn',
                'slug' => 'huong-dan-nap-an-toan',
                'category' => 'huong-dan',
                'summary' => 'Chỉ nạp qua kênh chính thức và kiểm tra đúng tên nhân vật.',
                'content' => 'Không chia sẻ mật khẩu, ma OTP hoac thông tin tài khoản chờ nguoi tu xung la ho tro vien.',
                'image_url' => '/img/post-default.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'is_featured' => false,
                'sort_order' => 0,
            ],
        ];
    }

    private function postsOrderBy(): string
    {
        $parts = [];

        if ($this->postHasColumn('is_featured')) {
            $parts[] = 'is_featured desc';
        }

        if ($this->postHasColumn('sort_order')) {
            $parts[] = 'sort_order asc';
        }

        if ($this->postHasColumn('created_at')) {
            $parts[] = 'created_at desc';
        } elseif ($this->postHasColumn('published_at')) {
            $parts[] = 'published_at desc';
        }

        $parts[] = 'id desc';

        return implode(', ', $parts);
    }

    private function postHasColumn(string $column): bool
    {
        $schema = $this->postsTableSchema();

        return isset($schema[strtolower($column)]);
    }

    private function postsTableSchema(): array
    {
        if ($this->postsTableSchema !== null) {
            return $this->postsTableSchema;
        }

        try {
            $rows = Database::connection()->query('show columns from posts')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $schema = [];

            foreach ($rows as $row) {
                $field = strtolower((string) ($row['Field'] ?? ''));

                if ($field !== '') {
                    $schema[$field] = $row;
                }
            }

            $this->postsTableSchema = $schema;
        } catch (Throwable) {
            $this->postsTableSchema = [];
        }

        return $this->postsTableSchema;
    }

    private function javaDownloadVersions(array $downloads): array
    {
        $databaseVersions = $this->databaseDownloadVersions($downloads, 'H盻・tr盻｣ mﾃ｡y Java/J2ME.');

        if ($databaseVersions !== []) {
            return $databaseVersions;
        }

        $fallbackFiles = array_values(array_filter(
            $this->downloadFilesByExtensions(['jar', 'zip']),
            fn (array $file): bool => strcasecmp($file['name'], 'Ninjaschoolblue.zip') !== 0
        ));

        usort($fallbackFiles, fn (array $a, array $b): int => $this->compareDownloadFiles($a['name'], $b['name']));

        if ($fallbackFiles !== []) {
            return array_map(function (array $file): array {
                $extension = strtolower((string) pathinfo($file['name'], PATHINFO_EXTENSION));
                $version = $extension === 'jar'
                    ? $this->jarVersionLabel($file['name'])
                    : pathinfo($file['name'], PATHINFO_FILENAME);

                return [
                    'title' => $file['name'],
                    'version' => $version,
                    'file_name' => $file['name'],
                    'file_size' => $this->formatFileSize((int) $file['size']),
                    'notes' => 'Hỗ trợ máy Java/J2ME.',
                    'download_url' => '/file/' . rawurlencode($file['name']),
                ];
            }, $fallbackFiles);
        }

        $files = $this->downloadFilesByExtensions(['jar']);

        usort($files, fn (array $a, array $b): int => $this->compareDownloadFiles($a['name'], $b['name']));

        $versions = array_map(function (array $file): array {
            return [
                'title' => $this->jarTitle($file['name']),
                'version' => $this->jarVersionLabel($file['name']),
                'file_name' => $file['name'],
                'file_size' => $this->formatFileSize((int) $file['size']),
                'notes' => 'Hỗ trợ máy Java/J2ME.',
                'download_url' => '/file/' . rawurlencode($file['name']),
            ];
        }, $files);

        $emulator = $this->downloadFileByName('AngelChipEmulator.zip');

        if ($emulator !== null) {
            $versions[] = [
                'title' => 'Giả lập Java (AngelChip) cho PC',
                'version' => 'AngelChipEmulator',
                'file_name' => $emulator['name'],
                'file_size' => $this->formatFileSize((int) $emulator['size']),
                'notes' => 'Windows 7 trở lên.',
                'download_url' => '/file/' . rawurlencode($emulator['name']),
            ];
        }

        return $versions;
    }

    private function androidDownloadVersions(array $downloads): array
    {
        $databaseVersions = $this->databaseDownloadVersions($downloads, 'Android 8 tr盻・lﾃｪn.');

        if ($databaseVersions !== []) {
            return $databaseVersions;
        }

        $apkFile = $this->downloadFileByName('Ninjaschoolblue.apk');

        if ($apkFile !== null) {
            return [[
                'title' => 'Ninjaschoolblue',
                'version' => 'Ninjaschoolblue',
                'file_name' => $apkFile['name'],
                'file_size' => $this->formatFileSize((int) $apkFile['size']),
                'notes' => 'Android 8 trở lên.',
                'download_url' => '/file/Ninjaschoolblue.apk',
            ]];
        }

        $versions = [];

        foreach ($downloads as $download) {
            if (strtolower((string) ($download['platform'] ?? '')) !== 'android') {
                continue;
            }

            $versions[] = [
                'title' => 'Android ' . (string) ($download['version'] ?? ''),
                'version' => (string) ($download['version'] ?? ''),
                'file_name' => basename((string) ($download['download_url'] ?? '')) ?: 'Android',
                'file_size' => (string) ($download['file_size'] ?? '-'),
                'notes' => (string) ($download['notes'] ?? 'Android 8 trở lên.'),
                'download_url' => (string) ($download['download_url'] ?? '#'),
            ];
        }

        if ($versions !== []) {
            return $versions;
        }

        return [[
            'title' => 'Android 1.0.0',
            'version' => '1.0.0',
            'file_name' => 'Android',
            'file_size' => '95 MB',
            'notes' => 'Android 8 trở lên.',
            'download_url' => '/file/Ninjaschoolblue.apk',
        ]];
    }

    private function windowsDownloadVersions(array $downloads): array
    {
        $databaseVersions = $this->databaseDownloadVersions($downloads, 'B蘯｣n PC t蘯｣i v盻・vﾃ chﾆ｡i ngay.');

        if ($databaseVersions !== []) {
            return $databaseVersions;
        }

        $pcFile = $this->downloadFileByName('Ninjaschoolblue.zip');

        if ($pcFile !== null) {
            return [[
                'title' => $pcFile['name'],
                'version' => pathinfo($pcFile['name'], PATHINFO_FILENAME),
                'file_name' => $pcFile['name'],
                'file_size' => $this->formatFileSize((int) $pcFile['size']),
                'notes' => 'Bản PC tải về và chơi ngay.',
                'download_url' => '/file/' . rawurlencode($pcFile['name']),
            ]];
        }

        $files = $this->downloadFilesByExtensions(['zip']);
        $versions = [];

        usort($files, fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        foreach ($files as $file) {
            $name = $file['name'];

            if (strcasecmp($name, 'AngelChipEmulator.zip') === 0) {
                continue;
            }

            $base = pathinfo($name, PATHINFO_FILENAME);

            $versions[] = [
                'title' => $this->humanizeDownloadName($base),
                'version' => $base,
                'file_name' => $name,
                'file_size' => $this->formatFileSize((int) $file['size']),
                'notes' => 'Bản PC tải về và chơi ngay.',
                'download_url' => '/file/' . rawurlencode($name),
            ];
        }

        return $versions;
    }

    private function iosDownloadVersions(array $downloads): array
    {
        $databaseVersions = $this->databaseDownloadVersions($downloads, 'iOS 12.0 tr盻・lﾃｪn.');

        if ($databaseVersions !== []) {
            return $databaseVersions;
        }

        return [[
            'title' => 'iOS v1.0.0',
            'version' => 'v1.0.0',
            'file_name' => 'TestFlight',
            'file_size' => '-',
            'notes' => 'iOS 12.0 trở lên.',
            'download_url' => 'https://testflight.apple.com/join/s3Nk9yQg',
        ]];
    }

    private function downloadFilesByExtensions(array $extensions): array
    {
        $directory = dirname(__DIR__) . '/public/file';

        if (!is_dir($directory)) {
            return [];
        }

        $normalizedExtensions = array_map('strtolower', $extensions);
        $files = [];

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;

            if (!is_file($path)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));

            if (!in_array($extension, $normalizedExtensions, true)) {
                continue;
            }

            $files[] = [
                'name' => $entry,
                'size' => filesize($path) ?: 0,
            ];
        }

        return $files;
    }

    private function downloadFileByName(string $fileName): ?array
    {
        $directory = dirname(__DIR__) . '/public/file';
        $path = $directory . '/' . $fileName;

        if (!is_file($path)) {
            return null;
        }

        return [
            'name' => $fileName,
            'size' => filesize($path) ?: 0,
        ];
    }

    private function compareDownloadFiles(string $left, string $right): int
    {
        [$leftVersion, $leftMultiplier] = $this->downloadSortParts($left);
        [$rightVersion, $rightMultiplier] = $this->downloadSortParts($right);

        return [$rightVersion, $leftMultiplier, strtolower($left)] <=> [$leftVersion, $rightMultiplier, strtolower($right)];
    }

    private function downloadSortParts(string $fileName): array
    {
        preg_match('/(\d{2,4})/', $fileName, $versionMatch);
        preg_match('/_X(\d+)/i', $fileName, $multiplierMatch);

        return [
            (int) ($versionMatch[1] ?? 0),
            (int) ($multiplierMatch[1] ?? 0),
        ];
    }

    private function groupDownloadsByPlatform(array $downloads): array
    {
        $grouped = [];

        foreach ($downloads as $download) {
            $platform = $this->normalizeDownloadPlatform((string) ($download['platform'] ?? ''));

            if ($platform === '') {
                continue;
            }

            $grouped[$platform] ??= [];
            $grouped[$platform][] = $download;
        }

        return $grouped;
    }

    private function databaseDownloadVersions(array $downloads, string $defaultNotes): array
    {
        $versions = [];

        foreach ($downloads as $download) {
            $downloadUrl = trim((string) ($download['download_url'] ?? '#'));
            $fileName = $this->resolveDownloadFileName($download);
            $version = trim((string) ($download['version'] ?? ''));

            $versions[] = [
                'title' => $fileName !== '' ? $fileName : $this->fallbackDownloadTitle((string) ($download['platform'] ?? 'Download'), $version),
                'version' => $version !== '' ? $version : '-',
                'file_name' => $fileName !== '' ? $fileName : '-',
                'file_size' => trim((string) ($download['file_size'] ?? '-')) ?: '-',
                'notes' => trim((string) ($download['notes'] ?? '')) ?: $defaultNotes,
                'download_url' => $downloadUrl !== '' ? $downloadUrl : '#',
            ];
        }

        return $versions;
    }

    private function resolveDownloadFileName(array $download): string
    {
        $fileName = trim((string) ($download['file_name'] ?? ''));

        if ($fileName !== '') {
            return $fileName;
        }

        $downloadUrl = trim((string) ($download['download_url'] ?? ''));

        if ($downloadUrl === '') {
            return '';
        }

        $path = (string) parse_url($downloadUrl, PHP_URL_PATH);

        if ($path === '') {
            return '';
        }

        return rawurldecode(basename($path));
    }

    private function fallbackDownloadTitle(string $platform, string $version): string
    {
        $label = $this->humanizeDownloadName($this->normalizeDownloadPlatform($platform));

        if ($label === '') {
            $label = 'Download';
        }

        return trim($label . ' ' . $version);
    }

    private function normalizeDownloadPlatform(string $platform): string
    {
        $normalized = strtolower(trim($platform));

        return match ($normalized) {
            'j2me', 'jar' => 'java',
            'iphone', 'apple' => 'ios',
            'pc' => 'windows',
            default => $normalized,
        };
    }

    private function jarTitle(string $fileName): string
    {
        return 'Ninja ' . $this->jarVersionLabel($fileName);
    }

    private function jarVersionLabel(string $fileName): string
    {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $normalized = preg_replace('/^ninja[_\-\s]*school[_\-\s]*blue[_\-\s]*/i', '', $base) ?? $base;
        $normalized = preg_replace('/^ninja[_\-\s]*/i', '', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if ($normalized === '') {
            $normalized = $base;
        }

        return $this->humanizeDownloadName($normalized);
    }

    private function humanizeDownloadName(string $value): string
    {
        $normalized = preg_replace('/[_-]+/', ' ', trim($value)) ?? $value;

        return preg_replace_callback('/\b([a-z])/', fn (array $matches): string => strtoupper($matches[1]), strtolower($normalized)) ?? $normalized;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        $precision = $size >= 100 || $unitIndex === 0 ? 0 : 1;

        return number_format($size, $precision, '.', '') . ' ' . $units[$unitIndex];
    }
}
