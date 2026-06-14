<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Response;

final class UsersController
{
    public function index(): never
    {
        $statement = Database::connection()
            ->query('select id, username, name, email, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, created_at, updated_at from users order by id desc');

        Response::json(['data' => $statement->fetchAll()]);
    }

    public function show(int $id): never
    {
        $statement = Database::connection()
            ->prepare('select id, username, name, email, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, created_at, updated_at from users where id = :id');
        $statement->execute(['id' => $id]);

        $user = $statement->fetch();

        if (!$user) {
            Response::json(['error' => 'User not found'], 404);
        }

        Response::json(['data' => $user]);
    }

    public function store(): never
    {
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true);

        if (!is_array($payload)) {
            Response::json(['error' => 'Invalid JSON body'], 422);
        }

        $username = trim((string) ($payload['username'] ?? ''));
        $name = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $password = trim((string) ($payload['password'] ?? ''));

        if ($username === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || strlen($password) < 6) {
            Response::json([
                'error' => 'Validation failed',
                'fields' => [
                    'username' => $username === '' ? 'Username is required' : null,
                    'email' => $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL) ? 'Valid email is required' : null,
                    'password' => strlen($password) < 6 ? 'Password must be at least 6 characters' : null,
                ],
            ], 422);
        }

        $connection = Database::connection();
        $statement = $connection->prepare(
            'insert into users (name, username, email, password, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, created_at, updated_at)
             values (:name, :username, :email, :password, 1, 1, 1, 0, 0, 0, 0, 0, 0, now(), now())'
        );
        $statement->execute([
            'name' => $name !== '' ? $name : $username,
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $id = (int) $connection->lastInsertId();
        $created = $connection->prepare(
            'select id, username, name, email, status, activated, active, role, balance, tongnap, tongNapThang, tongNapTuan, quanew, created_at, updated_at from users where id = :id'
        );
        $created->execute(['id' => $id]);

        Response::json(['data' => $created->fetch()], 201);
    }
}
