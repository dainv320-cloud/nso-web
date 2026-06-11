<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Response;
use App\View;
use PDO;
use Throwable;

final class AdminController
{
    private const ROLE_ADMIN = 1;
    private const ROLE_COLLABORATOR = 2;

    public function login(): never
    {
        if ($this->currentAdmin()) {
            header('Location: /admin');
            exit;
        }

        View::render('admin-login', [
            'title' => 'Admin Login',
            'error' => null,
        ]);
    }

    public function submitLogin(): never
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            View::render('admin-login', [
                'title' => 'Admin Login',
                'error' => "Vui l\u{00F2}ng nh\u{1EAD}p t\u{00E0}i kho\u{1EA3}n v\u{00E0} m\u{1EAD}t kh\u{1EA9}u admin.",
            ], 422);
        }

        try {
            $statement = Database::connection()->prepare(
                'select id, username, password, ban, is_active, type_admin from users where lower(username) = lower(:username) limit 1'
            );
            $statement->execute(['username' => $username]);
            $account = $statement->fetch();

            if (
                !$account
                || !password_verify($password, (string) $account['password'])
                || !$this->databaseBool($account['is_active'])
                || $this->databaseBool($account['ban'])
                || !$this->isAdminRole((int) $account['type_admin'])
            ) {
                View::render('admin-login', [
                    'title' => 'Admin Login',
                    'error' => "T\u{00E0}i kho\u{1EA3}n admin ho\u{1EB7}c m\u{1EAD}t kh\u{1EA9}u kh\u{00F4}ng \u{0111}\u{00FA}ng.",
                ], 401);
            }
        } catch (Throwable) {
            View::render('admin-login', [
                'title' => 'Admin Login',
                'error' => "Kh\u{00F4}ng th\u{1EC3} \u{0111}\u{0103}ng nh\u{1EAD}p admin l\u{00FA}c n\u{00E0}y.",
            ], 500);
        }

        $_SESSION['user'] = [
            'id' => $account['id'],
            'username' => $account['username'],
            'display_name' => $account['username'],
            'login_at' => date('Y-m-d H:i:s'),
        ];
        $_SESSION['admin_login'] = true;
        $_SESSION['toast_success'] = "\u{0110}\u{0103}ng nh\u{1EAD}p admin th\u{00E0}nh c\u{00F4}ng!";

        header('Location: /admin');
        exit;
    }

    public function logout(): never
    {
        unset($_SESSION['admin_login']);
        header('Location: /admin/login');
        exit;
    }

    public function dashboard(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/dashboard', [
            'title' => 'Admin',
            'admin' => $admin,
            'section' => 'dashboard',
            'stats' => $this->dashboardStats(),
            'flash' => $this->pullFlash(),
        ]);
    }

    public function users(): never
    {
        $admin = $this->requireAdmin();
        View::render('admin/list', [
            'title' => 'Admin - Users',
            'admin' => $admin,
            'section' => 'users',
            'heading' => 'Danh sach user',
            'description' => 'Quan ly tai khoan, quyen, trang thai va so du nguoi choi.',
            'createUrl' => '/admin/users/create',
            'rows' => $this->fetchAll('select id, name, username, email, ban, is_active, type_admin, money, totalmoney, tongnapthang, created_at, updated_at from users order by id desc limit 200'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Hiển thị'],
                ['key' => 'username', 'label' => 'User'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'is_active', 'label' => 'Kích hoạt', 'format' => 'bool'],
                ['key' => 'ban', 'label' => 'Ban', 'format' => 'bool'],
                ['key' => 'money', 'label' => 'Coin', 'format' => 'money'],
                ['key' => 'totalmoney', 'label' => 'Tổng nạp', 'format' => 'money'],
                ['key' => 'tongnapthang', 'label' => 'Nạp tháng', 'format' => 'money'],
                ['key' => 'money', 'label' => 'Coin', 'format' => 'money'],
                ['key' => 'type_admin', 'label' => 'Quyen', 'format' => 'admin_role'],
                ['key' => 'created_at', 'label' => 'Tạo lúc', 'format' => 'datetime'],
                ['key' => 'updated_at', 'label' => 'Cập nhật', 'format' => 'datetime'],
            ],
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Hien thi'],
                ['key' => 'username', 'label' => 'User'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'is_active', 'label' => 'Kich hoat', 'format' => 'bool'],
                ['key' => 'ban', 'label' => 'Ban', 'format' => 'bool'],
                ['key' => 'money', 'label' => 'Coin', 'format' => 'money'],
                ['key' => 'totalmoney', 'label' => 'Tong nap', 'format' => 'money'],
                ['key' => 'tongnapthang', 'label' => 'Nap thang', 'format' => 'money'],
                ['key' => 'type_admin', 'label' => 'Quyen', 'format' => 'admin_role'],
                ['key' => 'created_at', 'label' => 'Tao luc', 'format' => 'datetime'],
                ['key' => 'updated_at', 'label' => 'Cap nhat', 'format' => 'datetime'],
            ],
            'actions' => ['edit' => '/admin/users/%s/edit', 'delete' => '/admin/users/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function userForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('users', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y user.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a user" : "Admin - Th\u{00EA}m user",
            'admin' => $admin,
            'section' => 'users',
            'heading' => $id ? "S\u{1EED}a user" : "Th\u{00EA}m user",
            'backUrl' => '/admin/users',
            'actionUrl' => '/admin/users/save',
            'row' => $row ?? ['is_active' => true, 'tongnapthang' => 0],
            'fields' => [
                ['name' => 'username', 'label' => "T\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p", 'required' => true],
                ['name' => 'name', 'label' => "T\u{00EA}n hi\u{1EC3}n th\u{1ECB}"],
                ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
                ['name' => 'password', 'label' => $id ? "M\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i, b\u{1ECF} tr\u{1ED1}ng n\u{1EBF}u kh\u{00F4}ng \u{0111}\u{1ED5}i" : "M\u{1EAD}t kh\u{1EA9}u", 'type' => 'password', 'required' => !$id],
                ['name' => 'money', 'label' => 'Coin', 'type' => 'number', 'min' => 0],
                ['name' => 'totalmoney', 'label' => "T\u{1ED5}ng n\u{1EA1}p", 'type' => 'number', 'min' => 0],
                ['name' => 'tongnapthang', 'label' => "T\u{1ED5}ng n\u{1EA1}p th\u{00E1}ng", 'type' => 'number', 'min' => 0],
                ['name' => 'type_admin', 'label' => "Quy\u{1EC1}n", 'type' => 'select', 'options' => $this->userRoleOptions($row)],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
                ['name' => 'ban', 'label' => 'Ban', 'type' => 'checkbox'],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmUserDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('users', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y user.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a user",
            'admin' => $admin,
            'section' => 'users',
            'heading' => "X\u{00F3}a user",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a user n\u{00E0}y?",
            'row' => $row,
            'summary' => ['ID' => 'id', 'Hiển thị' => 'name', 'User' => 'username', 'Email' => 'email'],
            'summary' => ['ID' => 'id', 'Hien thi' => 'name', 'User' => 'username', 'Email' => 'email'],
            'actionUrl' => '/admin/users/delete',
            'backUrl' => '/admin/users',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveUser(): never
    {
        $admin = $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || !preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
            $this->redirectWithFlash($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create', "T\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}.", 'error');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->redirectWithFlash($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create', "Email kh\u{00F4}ng h\u{1EE3}p l\u{1EC7}.", 'error');
        }

        if ($id === 0 && strlen($password) < 6) {
            $this->redirectWithFlash('/admin/users/create', "M\u{1EAD}t kh\u{1EA9}u user m\u{1EDB}i t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1}.", 'error');
        }

        $data = [
            'name' => trim((string) ($_POST['name'] ?? $username)) ?: $username,
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'ban' => isset($_POST['ban']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'type_admin' => $this->userRoleFromRequest($id),
            'money' => max(0, (int) ($_POST['money'] ?? 0)),
            'totalmoney' => max(0, (int) ($_POST['totalmoney'] ?? 0)),
            'tongnapthang' => max(0, (int) ($_POST['tongnapthang'] ?? 0)),
        ];

        try {
            if ($id > 0) {
                $sql = 'update users set name = :name, username = :username, email = :email, ban = :ban, is_active = :is_active, type_admin = :type_admin, money = :money, totalmoney = :totalmoney, tongnapthang = :tongnapthang';

                if ($password !== '') {
                    if (strlen($password) < 6) {
                        $this->redirectWithFlash('/admin/users/' . $id . '/edit', "M\u{1EAD}t kh\u{1EA9}u m\u{1EDB}i t\u{1ED1}i thi\u{1EC3}u 6 k\u{00FD} t\u{1EF1}.", 'error');
                    }

                    $sql .= ', password = :password';
                    $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                }

                $sql .= ', updated_at = now() where id = :id';
                $data['id'] = $id;
                Database::connection()->prepare($sql)->execute($data);
            } else {
                $data['password'] = password_hash($password, PASSWORD_BCRYPT);
                Database::connection()->prepare(
                    'insert into users (name, username, email, password, ban, is_active, type_admin, money, totalmoney, tongnapthang, tongnapthang_reset_at, created_at, updated_at)
                     values (:name, :username, :email, :password, :ban, :is_active, :type_admin, :money, :totalmoney, :tongnapthang, now(), now(), now())'
                )->execute($data);
            }
        } catch (Throwable) {
            $this->redirectWithFlash($id ? '/admin/users/' . $id . '/edit' : '/admin/users/create', "Kh\u{00F4}ng th\u{1EC3} l\u{01B0}u user. T\u{00EA}n \u{0111}\u{0103}ng nh\u{1EAD}p/email c\u{00F3} th\u{1EC3} \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.", 'error');
        }

        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "\u{0110}\u{00E3} l\u{01B0}u user."];
        header('Location: /admin/users');
        exit;
    }

    public function deleteUser(): never
    {
        $admin = $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0 || $id === (int) $admin['id']) {
            $this->redirectWithFlash('/admin/users', "Kh\u{00F4}ng th\u{1EC3} x\u{00F3}a t\u{00E0}i kho\u{1EA3}n n\u{00E0}y.", 'error');
        }

        Database::connection()->prepare('delete from users where id = :id')->execute(['id' => $id]);
        $this->redirectWithFlash('/admin/users', "\u{0110}\u{00E3} x\u{00F3}a user.");
    }

    public function posts(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => "Admin - Tin t\u{1EE9}c",
            'admin' => $admin,
            'section' => 'posts',
            'heading' => "Danh s\u{00E1}ch tin t\u{1EE9}c",
            'createUrl' => '/admin/posts/create',
            'rows' => $this->fetchAll('select * from posts order by published_at desc, id desc limit 200'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'title', 'label' => "Ti\u{00EA}u \u{0111}\u{1EC1}"],
                ['key' => 'category', 'label' => "Danh m\u{1EE5}c"],
                ['key' => 'status', 'label' => "Tr\u{1EA1}ng th\u{00E1}i"],
                ['key' => 'published_at', 'label' => "Ng\u{00E0}y \u{0111}\u{0103}ng"],
            ],
            'actions' => $this->isCollaborator($admin)
                ? ['edit' => '/admin/posts/%s/edit']
                : ['edit' => '/admin/posts/%s/edit', 'delete' => '/admin/posts/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function postForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('posts', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/posts', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y tin t\u{1EE9}c.", 'error');
        }

        View::render('admin/post-form', [
            'title' => $id ? "Admin - S\u{1EED}a tin t\u{1EE9}c" : "Admin - Th\u{00EA}m tin t\u{1EE9}c",
            'admin' => $admin,
            'section' => 'posts',
            'heading' => $id ? "S\u{1EED}a tin t\u{1EE9}c" : "Th\u{00EA}m tin t\u{1EE9}c",
            'description' => "So\u{1EA1}n n\u{1ED9}i dung b\u{00E0}i vi\u{1EBF}t v\u{00E0} ch\u{00E8}n \u{1EA3}nh tr\u{1EF1}c ti\u{1EBF}p trong b\u{00E0}i vi\u{1EBF}t.",
            'backUrl' => '/admin/posts',
            'actionUrl' => '/admin/posts/save',
            'row' => $row ?? ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmPostDelete(int $id): never
    {
        $admin = $this->requireAdmin();

        if ($this->isCollaborator($admin)) {
            $this->redirectWithFlash('/admin/posts', 'Cong tac vien khong duoc xoa tin tuc.', 'error');
        }

        $row = $this->findRow('posts', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/posts', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y tin t\u{1EE9}c.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a tin t\u{1EE9}c",
            'admin' => $admin,
            'section' => 'posts',
            'heading' => "X\u{00F3}a tin t\u{1EE9}c",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a tin t\u{1EE9}c n\u{00E0}y?",
            'row' => $row,
            'summary' => ['ID' => 'id', "Ti\u{00EA}u \u{0111}\u{1EC1}" => 'title', 'Slug' => 'slug'],
            'actionUrl' => '/admin/posts/delete',
            'backUrl' => '/admin/posts',
            'flash' => $this->pullFlash(),
        ]);
    }
    public function savePost(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim((string) ($_POST['title'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? '')) ?: $this->slugify($title);

        if ($title === '' || $slug === '') {
            $this->redirectWithFlash($id ? '/admin/posts/' . $id . '/edit' : '/admin/posts/create', "Ti\u{00EA}u \u{0111}\u{1EC1} v\u{00E0} slug b\u{1EAF}t bu\u{1ED9}c.", 'error');
        }

        $data = [
            'title' => $title,
            'slug' => $slug,
            'category' => trim((string) ($_POST['category'] ?? 'tin-tuc')) ?: 'tin-tuc',
            'summary' => trim((string) ($_POST['summary'] ?? '')),
            'content' => trim((string) ($_POST['content'] ?? '')),
            'image_url' => trim((string) ($_POST['image_url'] ?? '')) ?: null,
            'status' => trim((string) ($_POST['status'] ?? 'published')) ?: 'published',
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'published_at' => trim((string) ($_POST['published_at'] ?? '')) ?: date('Y-m-d H:i:s'),
        ];

        try {
            if ($id > 0) {
                $data['id'] = $id;
                Database::connection()->prepare(
                    'update posts set title = :title, slug = :slug, category = :category, summary = :summary, content = :content, image_url = :image_url, status = :status, is_featured = :is_featured, published_at = :published_at, updated_at = now() where id = :id'
                )->execute($data);
            } else {
                Database::connection()->prepare(
                    'insert into posts (title, slug, category, summary, content, image_url, status, is_featured, published_at)
                     values (:title, :slug, :category, :summary, :content, :image_url, :status, :is_featured, :published_at)'
                )->execute($data);
            }
        } catch (Throwable) {
            $this->redirectWithFlash($id ? '/admin/posts/' . $id . '/edit' : '/admin/posts/create', "Kh\u{00F4}ng th\u{1EC3} l\u{01B0}u tin t\u{1EE9}c. Slug c\u{00F3} th\u{1EC3} \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.", 'error');
        }

        $this->redirectWithFlash('/admin/posts', "\u{0110}\u{00E3} l\u{01B0}u tin t\u{1EE9}c.");
    }

    public function deletePost(): never
    {
        $admin = $this->requireAdmin();

        if ($this->isCollaborator($admin)) {
            $this->redirectWithFlash('/admin/posts', 'Cong tac vien khong duoc xoa tin tuc.', 'error');
        }

        Database::connection()->prepare('delete from posts where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/posts', "\u{0110}\u{00E3} x\u{00F3}a tin t\u{1EE9}c.");
    }

    public function uploadImage(): never
    {
        $this->requireAdmin();

        $file = $_FILES['upload'] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Response::json(['error' => ['message' => "Kh\u{00F4}ng nh\u{1EAD}n \u{0111}\u{01B0}\u{1EE3}c file upload."]], 422);
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            Response::json(['error' => ['message' => "\u{1EA2}nh kh\u{00F4}ng \u{0111}\u{01B0}\u{1EE3}c v\u{01B0}\u{1EE3}t qu\u{00E1} 5MB."]], 422);
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mimeType = function_exists('mime_content_type') ? (string) mime_content_type($tmpName) : '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($extensions[$mimeType])) {
            Response::json(['error' => ['message' => "Ch\u{1EC9} h\u{1ED7} tr\u{1EE3} JPG, PNG, WEBP, GIF."]], 422);
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/news';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            Response::json(['error' => ['message' => "Kh\u{00F4}ng t\u{1EA1}o \u{0111}\u{01B0}\u{1EE3}c th\u{01B0} m\u{1EE5}c upload."]], 500);
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mimeType];
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($tmpName, $target)) {
            Response::json(['error' => ['message' => "Kh\u{00F4}ng l\u{01B0}u \u{0111}\u{01B0}\u{1EE3}c file upload."]], 500);
        }

        Response::json([
            'url' => '/uploads/news/' . $filename,
        ]);
    }

    public function payments(): never
    {
        $admin = $this->requireAdmin();
        $paymentEnabled = $this->paymentEnabled();

        View::render('admin/list', [
            'title' => 'Admin - Payments',
            'admin' => $admin,
            'section' => 'payments',
            'heading' => "Danh s\u{00E1}ch payment",
            'description' => $paymentEnabled
                ? "Ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n \u{0111}ang b\u{1EAD}t."
                : "Ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n \u{0111}ang t\u{1EAF}t.",
            'paymentEnabled' => $paymentEnabled,
            'paymentToggleUrl' => '/admin/payments/toggle',
            'rows' => $this->fetchAll(
                'select p.*, u.username
                 from payments p
                 left join users u on u.id = p.user_id
                 order by p.created_at desc, p.id desc
                 limit 200'
            ),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'username', 'label' => 'User'],
                ['key' => 'transaction_id', 'label' => 'TXN'],
                ['key' => 'amount', 'label' => 'Amount', 'format' => 'money'],
                ['key' => 'coin_amount', 'label' => 'Coin', 'format' => 'money'],
                ['key' => 'status', 'label' => 'Status'],
            ],
            'actions' => ['edit' => '/admin/payments/%s/edit'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function togglePayment(): never
    {
        $this->requireAdmin();
        $enabled = ($_POST['enabled'] ?? '') === '1';

        if (!$this->writeEnvValue('PAYMENT_ENABLED', $enabled ? 'true' : 'false')) {
            $this->redirectWithFlash('/admin/payments', "Kh\u{00F4}ng th\u{1EC3} c\u{1EAD}p nh\u{1EAD}t file .env.", 'error');
        }

        $message = $enabled
            ? "\u{0110}\u{00E3} b\u{1EAD}t ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n."
            : "\u{0110}\u{00E3} t\u{1EAF}t ch\u{1EE9}c n\u{0103}ng n\u{1EA1}p ti\u{1EC1}n.";
        $this->redirectWithFlash('/admin/payments', $message);
    }

    public function paymentForm(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('payments', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/payments', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y payment.", 'error');
        }

        View::render('admin/form', [
            'title' => "Admin - S\u{1EED}a payment",
            'admin' => $admin,
            'section' => 'payments',
            'heading' => "S\u{1EED}a payment",
            'description' => "Admin ch\u{1EC9} s\u{1EED}a payment c\u{00F3} s\u{1EB5}n.",
            'backUrl' => '/admin/payments',
            'actionUrl' => '/admin/payments/save',
            'row' => $row,
            'fields' => [
                ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'min' => 0, 'step' => 1000],
                ['name' => 'coin_amount', 'label' => 'Coin', 'type' => 'number', 'min' => 0],
                ['name' => 'status', 'label' => 'Status'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 5],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function savePayment(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->redirectWithFlash('/admin/payments', "Payment ch\u{1EC9} \u{0111}\u{01B0}\u{1EE3}c s\u{1EED}a b\u{1EA3}n ghi \u{0111}\u{00E3} c\u{00F3}.", 'error');
        }

        $data = [
            'id' => $id,
            'amount' => max(0, (float) ($_POST['amount'] ?? 0)),
            'coin_amount' => max(0, (int) ($_POST['coin_amount'] ?? 0)),
            'status' => trim((string) ($_POST['status'] ?? 'success')) ?: 'success',
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];

        $connection = Database::connection();
        $connection->beginTransaction();

        try {
            $connection->prepare(
                'update payments set amount = :amount, coin_amount = :coin_amount, status = :status, description = :description, updated_at = now() where id = :id'
            )->execute($data);
            $connection->prepare(
                'update deposits set amount = :amount, coin_amount = :coin_amount, status = :status, description = :description, updated_at = now() where payment_id = :id'
            )->execute($data);
            $connection->commit();
        } catch (Throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            $this->redirectWithFlash('/admin/payments/' . $id . '/edit', "Kh\u{00F4}ng th\u{1EC3} c\u{1EAD}p nh\u{1EAD}t payment.", 'error');
        }

        $this->redirectWithFlash('/admin/payments', "\u{0110}\u{00E3} c\u{1EAD}p nh\u{1EAD}t payment.");
    }

    public function banks(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => 'Admin - Bank Accounts',
            'admin' => $admin,
            'section' => 'banks',
            'heading' => "Danh s\u{00E1}ch t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'createUrl' => '/admin/banks/create',
            'rows' => $this->fetchAll('select * from bank_accounts order by sort_order asc, id desc'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'bank_name', 'label' => 'Bank'],
                ['key' => 'acc_num', 'label' => 'Account'],
                ['key' => 'code', 'label' => 'Prefix'],
                ['key' => 'bank_rate', 'label' => 'Rate'],
                ['key' => 'is_active', 'label' => 'Active', 'format' => 'bool'],
            ],
            'actions' => ['edit' => '/admin/banks/%s/edit', 'delete' => '/admin/banks/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function bankForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('bank_accounts', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/banks', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng" : "Admin - Th\u{00EA}m t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'admin' => $admin,
            'section' => 'banks',
            'heading' => $id ? "S\u{1EED}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng" : "Th\u{00EA}m t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'backUrl' => '/admin/banks',
            'actionUrl' => '/admin/banks/save',
            'row' => $row ?? ['bank_rate' => 1, 'is_active' => true],
            'fields' => [
                ['name' => 'bank_name', 'label' => 'Bank name', 'required' => true],
                ['name' => 'bank_code', 'label' => 'Bank code VietQR', 'required' => true],
                ['name' => 'acc_num', 'label' => 'Account number', 'required' => true],
                ['name' => 'acc_name', 'label' => 'Account name', 'required' => true],
                ['name' => 'code', 'label' => 'Prefix content', 'required' => true],
                ['name' => 'bank_rate', 'label' => 'Rate', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
                ['name' => 'sort_order', 'label' => 'Sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmBankDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('bank_accounts', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/banks', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'admin' => $admin,
            'section' => 'banks',
            'heading' => "X\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng n\u{00E0}y?",
            'row' => $row,
            'summary' => ['Bank' => 'bank_name', 'Account' => 'acc_num', 'Prefix' => 'code'],
            'actionUrl' => '/admin/banks/delete',
            'backUrl' => '/admin/banks',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveBank(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'bank_name' => trim((string) ($_POST['bank_name'] ?? '')),
            'bank_code' => strtoupper(trim((string) ($_POST['bank_code'] ?? ''))),
            'acc_num' => trim((string) ($_POST['acc_num'] ?? '')),
            'acc_name' => trim((string) ($_POST['acc_name'] ?? '')),
            'code' => strtoupper(trim((string) ($_POST['code'] ?? ''))),
            'bank_rate' => max(0, (float) ($_POST['bank_rate'] ?? 1)),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];

        if ($data['bank_name'] === '' || $data['bank_code'] === '' || $data['acc_num'] === '' || $data['acc_name'] === '' || $data['code'] === '') {
            $this->redirectWithFlash($id ? '/admin/banks/' . $id . '/edit' : '/admin/banks/create', "Th\u{00F4}ng tin ng\u{00E2}n h\u{00E0}ng ch\u{01B0}a \u{0111}\u{1EA7}y \u{0111}\u{1EE7}.", 'error');
        }

        if ($id > 0) {
            $data['id'] = $id;
            Database::connection()->prepare(
                'update bank_accounts set bank_name = :bank_name, bank_code = :bank_code, acc_num = :acc_num, acc_name = :acc_name, code = :code, bank_rate = :bank_rate, is_active = :is_active, sort_order = :sort_order, updated_at = now() where id = :id'
            )->execute($data);
        } else {
            Database::connection()->prepare(
                'insert into bank_accounts (bank_name, bank_code, acc_num, acc_name, code, bank_rate, is_active, sort_order)
                 values (:bank_name, :bank_code, :acc_num, :acc_name, :code, :bank_rate, :is_active, :sort_order)'
            )->execute($data);
        }

        $this->redirectWithFlash('/admin/banks', "\u{0110}\u{00E3} l\u{01B0}u t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.");
    }

    public function deleteBank(): never
    {
        $this->requireAdmin();
        Database::connection()->prepare('delete from bank_accounts where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/banks', "\u{0110}\u{00E3} x\u{00F3}a t\u{00E0}i kho\u{1EA3}n ng\u{00E2}n h\u{00E0}ng.");
    }

    public function downloads(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => 'Admin - Downloads',
            'admin' => $admin,
            'section' => 'downloads',
            'heading' => "Danh s\u{00E1}ch download",
            'createUrl' => '/admin/downloads/create',
            'rows' => $this->fetchAll('select * from downloads order by sort_order asc, id desc'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'platform', 'label' => 'Platform'],
                ['key' => 'version', 'label' => 'Version'],
                ['key' => 'file_size', 'label' => 'Size'],
                ['key' => 'is_active', 'label' => 'Active', 'format' => 'bool'],
            ],
            'actions' => ['edit' => '/admin/downloads/%s/edit', 'delete' => '/admin/downloads/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function downloadForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('downloads', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/downloads', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y download.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a download" : "Admin - Th\u{00EA}m download",
            'admin' => $admin,
            'section' => 'downloads',
            'heading' => $id ? "S\u{1EED}a download" : "Th\u{00EA}m download",
            'backUrl' => '/admin/downloads',
            'actionUrl' => '/admin/downloads/save',
            'row' => $row ?? ['is_active' => true],
            'fields' => [
                ['name' => 'platform', 'label' => 'Platform', 'required' => true],
                ['name' => 'version', 'label' => 'Version', 'required' => true],
                ['name' => 'file_size', 'label' => 'File size', 'required' => true],
                ['name' => 'download_url', 'label' => 'Download URL', 'required' => true],
                ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'rows' => 4],
                ['name' => 'sort_order', 'label' => 'Sort', 'type' => 'number'],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmDownloadDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('downloads', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/downloads', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y download.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a download",
            'admin' => $admin,
            'section' => 'downloads',
            'heading' => "X\u{00F3}a download",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a download n\u{00E0}y?",
            'row' => $row,
            'summary' => ['Platform' => 'platform', 'Version' => 'version', 'URL' => 'download_url'],
            'actionUrl' => '/admin/downloads/delete',
            'backUrl' => '/admin/downloads',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveDownload(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'platform' => trim((string) ($_POST['platform'] ?? '')),
            'version' => trim((string) ($_POST['version'] ?? '')),
            'file_size' => trim((string) ($_POST['file_size'] ?? '')),
            'download_url' => trim((string) ($_POST['download_url'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        ];

        if ($data['platform'] === '' || $data['version'] === '' || $data['file_size'] === '' || $data['download_url'] === '') {
            $this->redirectWithFlash($id ? '/admin/downloads/' . $id . '/edit' : '/admin/downloads/create', "Th\u{00F4}ng tin download ch\u{01B0}a \u{0111}\u{1EA7}y \u{0111}\u{1EE7}.", 'error');
        }

        try {
            if ($id > 0) {
                $data['id'] = $id;
                Database::connection()->prepare(
                    'update downloads set platform = :platform, version = :version, file_size = :file_size, download_url = :download_url, notes = :notes, is_active = :is_active, sort_order = :sort_order, updated_at = now() where id = :id'
                )->execute($data);
            } else {
                Database::connection()->prepare(
                    'insert into downloads (platform, version, file_size, download_url, notes, is_active, sort_order)
                     values (:platform, :version, :file_size, :download_url, :notes, :is_active, :sort_order)'
                )->execute($data);
            }
        } catch (Throwable) {
            $this->redirectWithFlash($id ? '/admin/downloads/' . $id . '/edit' : '/admin/downloads/create', "Kh\u{00F4}ng th\u{1EC3} l\u{01B0}u download. Platform c\u{00F3} th\u{1EC3} \u{0111}\u{00E3} t\u{1ED3}n t\u{1EA1}i.", 'error');
        }

        $this->redirectWithFlash('/admin/downloads', "\u{0110}\u{00E3} l\u{01B0}u download.");
    }

    public function deleteDownload(): never
    {
        $this->requireAdmin();
        Database::connection()->prepare('delete from downloads where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/downloads', "\u{0110}\u{00E3} x\u{00F3}a download.");
    }

    public function rates(): never
    {
        $admin = $this->requireAdmin();

        View::render('admin/list', [
            'title' => 'Admin - Rate',
            'admin' => $admin,
            'section' => 'rates',
            'heading' => "Chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i",
            'description' => $this->activeCampaign()
                ? "\u{0110}ang c\u{00F3} chi\u{1EBF}n d\u{1ECB}ch active. Base rate ng\u{00E2}n h\u{00E0}ng s\u{1EED}a trong Bank account."
                : "Ch\u{01B0}a c\u{00F3} chi\u{1EBF}n d\u{1ECB}ch active. Base rate ng\u{00E2}n h\u{00E0}ng s\u{1EED}a trong Bank account.",
            'createUrl' => '/admin/rates/create',
            'banks' => $this->fetchAll('select id, bank_name, bank_rate, is_active from bank_accounts order by sort_order asc, id desc'),
            'activeCampaign' => $this->activeCampaign(),
            'rows' => $this->fetchAll('select * from promotion_campaigns order by starts_at desc, id desc'),
            'columns' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'name', 'label' => 'Campaign'],
                ['key' => 'bonus_percent', 'label' => 'Bonus %'],
                ['key' => 'starts_at', 'label' => "B\u{1EAF}t \u{0111}\u{1EA7}u"],
                ['key' => 'ends_at', 'label' => "K\u{1EBF}t th\u{00FA}c"],
                ['key' => 'is_active', 'label' => 'Active', 'format' => 'bool'],
            ],
            'actions' => ['edit' => '/admin/rates/%s/edit', 'delete' => '/admin/rates/%s/delete'],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function campaignForm(?int $id = null): never
    {
        $admin = $this->requireAdmin();
        $row = $id ? $this->findRow('promotion_campaigns', $id) : null;

        if ($id && !$row) {
            $this->redirectWithFlash('/admin/rates', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y chi\u{1EBF}n d\u{1ECB}ch.", 'error');
        }

        View::render('admin/form', [
            'title' => $id ? "Admin - S\u{1EED}a KM" : "Admin - Th\u{00EA}m KM",
            'admin' => $admin,
            'section' => 'rates',
            'heading' => $id ? "S\u{1EED}a chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i" : "Th\u{00EA}m chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i",
            'description' => "V\u{00ED} d\u{1EE5}: ch\u{1EA1}y 2 ng\u{00E0}y v\u{1EDB}i bonus 30% coin n\u{1EA1}p.",
            'backUrl' => '/admin/rates',
            'actionUrl' => '/admin/rates/save',
            'row' => $row ?? ['bonus_percent' => 30, 'starts_at' => date('Y-m-d H:i:s'), 'ends_at' => date('Y-m-d H:i:s', strtotime('+2 days')), 'is_active' => true],
            'fields' => [
                ['name' => 'name', 'label' => "T\u{00EA}n chi\u{1EBF}n d\u{1ECB}ch", 'required' => true],
                ['name' => 'bonus_percent', 'label' => 'Bonus %', 'type' => 'number', 'min' => 0, 'step' => '0.01'],
                ['name' => 'starts_at', 'label' => "B\u{1EAF}t \u{0111}\u{1EA7}u", 'type' => 'datetime-local', 'required' => true],
                ['name' => 'ends_at', 'label' => "K\u{1EBF}t th\u{00FA}c", 'type' => 'datetime-local', 'required' => true],
                ['name' => 'note', 'label' => "Ghi ch\u{00FA}", 'type' => 'textarea', 'rows' => 4],
                ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox', 'checked' => !$id],
            ],
            'flash' => $this->pullFlash(),
        ]);
    }

    public function confirmCampaignDelete(int $id): never
    {
        $admin = $this->requireAdmin();
        $row = $this->findRow('promotion_campaigns', $id);

        if (!$row) {
            $this->redirectWithFlash('/admin/rates', "Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y chi\u{1EBF}n d\u{1ECB}ch.", 'error');
        }

        View::render('admin/delete', [
            'title' => "Admin - X\u{00F3}a KM",
            'admin' => $admin,
            'section' => 'rates',
            'heading' => "X\u{00F3}a chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i",
            'message' => "B\u{1EA1}n c\u{00F3} ch\u{1EAF}c ch\u{1EAF}n mu\u{1ED1}n x\u{00F3}a chi\u{1EBF}n d\u{1ECB}ch n\u{00E0}y?",
            'row' => $row,
            'summary' => ['Name' => 'name', 'Bonus' => 'bonus_percent', "B\u{1EAF}t \u{0111}\u{1EA7}u" => 'starts_at'],
            'actionUrl' => '/admin/rates/delete',
            'backUrl' => '/admin/rates',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function saveCampaign(): never
    {
        $this->requireAdmin();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'bonus_percent' => max(0, (float) ($_POST['bonus_percent'] ?? 0)),
            'starts_at' => trim((string) ($_POST['starts_at'] ?? '')),
            'ends_at' => trim((string) ($_POST['ends_at'] ?? '')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];

        if ($data['name'] === '' || $data['starts_at'] === '' || $data['ends_at'] === '') {
            $this->redirectWithFlash($id ? '/admin/rates/' . $id . '/edit' : '/admin/rates/create', "T\u{00EA}n chi\u{1EBF}n d\u{1ECB}ch v\u{00E0} th\u{1EDD}i gian l\u{00E0} b\u{1EAF}t bu\u{1ED9}c.", 'error');
        }

        if ($id > 0) {
            $data['id'] = $id;
            Database::connection()->prepare(
                'update promotion_campaigns set name = :name, bonus_percent = :bonus_percent, starts_at = :starts_at, ends_at = :ends_at, is_active = :is_active, note = :note, updated_at = now() where id = :id'
            )->execute($data);
        } else {
            Database::connection()->prepare(
                'insert into promotion_campaigns (name, bonus_percent, starts_at, ends_at, is_active, note)
                 values (:name, :bonus_percent, :starts_at, :ends_at, :is_active, :note)'
            )->execute($data);
        }

        $this->redirectWithFlash('/admin/rates', "\u{0110}\u{00E3} l\u{01B0}u chi\u{1EBF}n d\u{1ECB}ch khuy\u{1EBF}n m\u{00E3}i.");
    }

    public function deleteCampaign(): never
    {
        $this->requireAdmin();
        Database::connection()->prepare('delete from promotion_campaigns where id = :id')->execute(['id' => (int) ($_POST['id'] ?? 0)]);
        $this->redirectWithFlash('/admin/rates', "\u{0110}\u{00E3} x\u{00F3}a chi\u{1EBF}n d\u{1ECB}ch.");
    }

    private function requireAdmin(): array
    {
        $admin = $this->currentAdmin();

        if (!$admin) {
            header('Location: /admin/login');
            exit;
        }

        if ($this->isCollaborator($admin) && !$this->collaboratorCanAccessCurrentPath()) {
            if ($this->currentAdminPath() === '/admin') {
                header('Location: /admin/posts');
                exit;
            }

            $this->redirectWithFlash('/admin/posts', "C\u{1ED9}ng t\u{00E1}c vi\u{00EA}n ch\u{1EC9} \u{0111}\u{01B0}\u{1EE3}c qu\u{1EA3}n l\u{00FD} tin t\u{1EE9}c.", 'error');
        }

        return $admin;
    }

    private function currentAdmin(): ?array
    {
        $user = $_SESSION['user'] ?? null;

        if (!$user || empty($user['username'])) {
            return null;
        }

        try {
            $statement = Database::connection()->prepare(
                'select id, username, type_admin, ban, is_active from users where lower(username) = lower(:username) limit 1'
            );
            $statement->execute(['username' => $user['username']]);
            $admin = $statement->fetch();

            if (
                $admin
                && $this->isAdminRole((int) $admin['type_admin'])
                && !$this->databaseBool($admin['ban'])
                && $this->databaseBool($admin['is_active'])
            ) {
                return $admin;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function isCollaborator(array $admin): bool
    {
        return (int) ($admin['type_admin'] ?? 0) === self::ROLE_COLLABORATOR;
    }

    private function isAdminRole(int $typeAdmin): bool
    {
        return in_array($typeAdmin, [self::ROLE_ADMIN, self::ROLE_COLLABORATOR], true);
    }

    private function currentAdminPath(): string
    {
        return (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    }

    private function collaboratorCanAccessCurrentPath(): bool
    {
        $path = $this->currentAdminPath();

        return $path === '/admin'
            || str_starts_with($path, '/admin/posts')
            || $path === '/admin/uploads/image'
            || $path === '/admin/logout';
    }

    private function userRoleOptions(?array $row = null): array
    {
        $options = [
            ['value' => '0', 'label' => 'User'],
            ['value' => (string) self::ROLE_COLLABORATOR, 'label' => "C\u{1ED9}ng t\u{00E1}c vi\u{00EA}n"],
        ];

        $currentType = (int) ($row['type_admin'] ?? 0);

        if ($currentType > 0 && $currentType !== self::ROLE_COLLABORATOR) {
            $options[] = ['value' => (string) $currentType, 'label' => "Admin hi\u{1EC7}n t\u{1EA1}i"];
        }

        return $options;
    }

    private function userRoleFromRequest(int $id): int
    {
        $requestedType = (int) ($_POST['type_admin'] ?? 0);

        if (in_array($requestedType, [0, self::ROLE_COLLABORATOR], true)) {
            return $requestedType;
        }

        if ($id > 0) {
            $row = $this->findRow('users', $id);

            if ($row) {
                return (int) ($row['type_admin'] ?? 0);
            }
        }

        return 0;
    }

    private function dashboardStats(): array
    {
        return [
            'users' => $this->scalar('select count(*) from users'),
            'posts' => $this->scalar('select count(*) from posts'),
            'payments' => $this->scalar('select count(*) from payments'),
            'banks' => $this->scalar('select count(*) from bank_accounts'),
            'downloads' => $this->scalar('select count(*) from downloads'),
            'campaigns' => $this->scalar('select count(*) from promotion_campaigns'),
        ];
    }

    private function fetchAll(string $sql): array
    {
        try {
            return Database::connection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function findRow(string $table, int $id): ?array
    {
        $allowed = ['users', 'posts', 'payments', 'bank_accounts', 'downloads', 'promotion_campaigns'];

        if ($id <= 0 || !in_array($table, $allowed, true)) {
            return null;
        }

        $statement = Database::connection()->prepare("select * from {$table} where id = :id limit 1");
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }

    private function scalar(string $sql): int
    {
        try {
            return (int) Database::connection()->query($sql)->fetchColumn();
        } catch (Throwable) {
            return 0;
        }
    }

    private function activeCampaign(): ?array
    {
        try {
            $statement = Database::connection()->query(
                'select * from promotion_campaigns
                 where is_active = true and now() between starts_at and ends_at
                 order by bonus_percent desc, ends_at asc
                 limit 1'
            );
            $campaign = $statement->fetch();

            return $campaign ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    private function redirectWithFlash(string $url, string $message, string $type = 'success'): never
    {
        $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
        header('Location: ' . $url);
        exit;
    }

    private function pullFlash(): ?array
    {
        $flash = $_SESSION['admin_flash'] ?? null;
        unset($_SESSION['admin_flash']);

        return is_array($flash) ? $flash : null;
    }

    private function databaseBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 't', 'yes', 'y'], true);
    }

    private function paymentEnabled(): bool
    {
        return $this->databaseBool(env('PAYMENT_ENABLED', 'true'));
    }

    private function writeEnvValue(string $key, string $value): bool
    {
        $path = dirname(__DIR__, 2) . '/.env';

        if (!is_file($path) || !is_writable($path)) {
            return false;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return false;
        }

        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $content)) {
            $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $content) ?? $content;
        } else {
            $content = rtrim($content) . PHP_EOL . $line . PHP_EOL;
        }

        return file_put_contents($path, $content) !== false;
    }

    private function slugify(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';

        return trim($value, '-');
    }
}
