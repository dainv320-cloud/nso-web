<?php

declare(strict_types=1);

namespace App;

use PDO;
use Throwable;

final class ContentRepository
{
    public function featuredPosts(): array
    {
        return $this->fetch(
            'select * from posts where status = :status order by is_featured desc, published_at desc limit 6',
            ['status' => 'published'],
            $this->fallbackPosts()
        );
    }

    public function posts(?string $category = null): array
    {
        $fallback = $this->fallbackPosts();

        if ($category === null) {
            return $this->fetch(
                'select * from posts where status = :status order by published_at desc',
                ['status' => 'published'],
                $fallback
            );
        }

        return $this->fetch(
            'select * from posts where status = :status and category = :category order by published_at desc',
            ['status' => 'published', 'category' => $category],
            array_values(array_filter($fallback, fn (array $post): bool => $post['category'] === $category))
        );
    }

    public function post(string $slug): ?array
    {
        $posts = $this->fetch(
            'select * from posts where slug = :slug and status = :status limit 1',
            ['slug' => $slug, 'status' => 'published'],
            []
        );

        if ($posts !== []) {
            return $posts[0];
        }

        foreach ($this->fallbackPosts() as $post) {
            if ($post['slug'] === $slug) {
                return $post;
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

        return array_slice($this->fetch(
            'select * from posts
             where status = :status and category = :category and slug <> :slug
             order by published_at desc, id desc
             limit ' . max(1, $limit),
            ['status' => 'published', 'category' => $category, 'slug' => $slug],
            $fallback
        ), 0, $limit);
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
                'is_featured' => true,
            ],
            [
                'title' => 'Khai mo may chu Lang Gio',
                'slug' => 'khai-mo-may-chu-lang-gio',
                'category' => 'thong-bao',
                'summary' => 'May chu moi mo cung chuoi qua đăng nhập chờ tan thu.',
                'content' => 'May chu Lang Gio mo cua voi nhiem vu tan thu, phan thuong đăng nhập va dua top luc chien trong tuan dau.',
                'image_url' => '/img/post-default.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'is_featured' => true,
            ],
            [
                'title' => 'Su kien san boss cuoi tuan',
                'slug' => 'su-kien-san-boss-cuoi-tuan',
                'category' => 'su-kien',
                'summary' => 'Boss the gioi xuat hien theo khung gio co dinh voi vat pham hiem.',
                'content' => 'Nguoi chời tham gia san boss co co hoi nhan trang bi, da cuong hoa va dảnh hieu gioi han.',
                'image_url' => '/img/ns2d-ninja.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'is_featured' => true,
            ],
            [
                'title' => 'Hướng dẫn nạp an toàn',
                'slug' => 'huong-dan-nap-an-toan',
                'category' => 'huong-dan',
                'summary' => 'Chỉ nạp qua kênh chính thức và kiểm tra đúng tên nhân vật.',
                'content' => 'Không chia sẻ mật khẩu, ma OTP hoac thông tin tài khoản chờ nguoi tu xung la ho tro vien.',
                'image_url' => '/img/post-default.webp',
                'published_at' => date('Y-m-d H:i:s'),
                'is_featured' => false,
            ],
        ];
    }
}

