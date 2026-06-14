CREATE TABLE `users`  (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `phone` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `otp` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `email` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `status` int NULL DEFAULT 1 COMMENT '0: Deactivate, 1: Active, 2: Block',
  `activated` int NULL DEFAULT 0 COMMENT '0: Chưa kh\r\n1: Đã kh',
  `active` int NOT NULL DEFAULT 0,
  `kh` int NOT NULL DEFAULT 0 COMMENT '0: Deactive,\r\n1: Active',
  `remember_token` varchar(225) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `balance` int NOT NULL DEFAULT 0,
  `luong` int NOT NULL DEFAULT 0,
  `amount_unpaid` int NOT NULL DEFAULT 0,
  `online` tinyint(1) NOT NULL DEFAULT 0,
  `role` int NULL DEFAULT NULL,
  `group_id` int NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `received_first_gift` int NOT NULL DEFAULT 0,
  `last_attendance_at` bigint NULL DEFAULT 0,
  `ip_address` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `level_reward` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '[0,0,0,0,0]',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `ban_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp,
  `updated_at` timestamp NULL DEFAULT current_timestamp,
  `tongnap` int NOT NULL DEFAULT 0,
  `tongNapThang` int NOT NULL DEFAULT 0,
  `tongNapTuan` int NOT NULL DEFAULT 0,
  `tongNapThangResetAt` datetime NULL DEFAULT NULL,
  `tongNapTuanResetAt` datetime NULL DEFAULT NULL,
  `topGT` int NOT NULL DEFAULT 0,
  `isVIP` int NOT NULL DEFAULT 0,
  `quanew` int NOT NULL DEFAULT 0,
  `magioithieu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `countGT` int NOT NULL DEFAULT 0,
  `rewardtop` int NOT NULL DEFAULT 0,
  `mocnap` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '[0,0,0,0,0,0,0]',
  `streamer` int NOT NULL DEFAULT 0,
  `gdv` int NOT NULL DEFAULT 0,
  `goiTanThu` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '[0,0,0,0,0,0,0,0,0]',
  `newplay` int NOT NULL DEFAULT 0,
  `vxmm` int NOT NULL DEFAULT 0,
  `pointBosss` int NOT NULL DEFAULT 0,
  `pointCauCa` int NOT NULL DEFAULT 0,
  `veluong` int NOT NULL DEFAULT 0,
  `luotGopHongBao` int NOT NULL DEFAULT 0,
  `goinap` int NOT NULL DEFAULT 0,
  `goiNap2` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '[0,0,0,0,0,0,0]',
  `goiNap3` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '[0,0,0,0,0,0,0]',
  `activated_at` datetime NULL DEFAULT NULL,
  `ip_web` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL,
  `ip_register` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL,
  `mokhoa` int NOT NULL DEFAULT 1,
  `countRename` int NOT NULL DEFAULT 1,
  `realGold` bigint NULL DEFAULT 0,
  `realCoin` bigint NOT NULL DEFAULT 0,
  `moKhoaTam` int NOT NULL DEFAULT 0,
  `win` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `id`(`id` ASC) USING BTREE,
  UNIQUE INDEX `id_2`(`id` ASC) USING BTREE,
  UNIQUE INDEX `id_3`(`id` ASC) USING BTREE,
  INDEX `username`(`username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5000 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;

create unique index if not exists users_email_unique on users(email) where email is not null

create unique index if not exists users_username_unique on users(username);
create unique index if not exists users_email_unique on users(email) where email is not null;



create table if not exists deposit_history (
    id bigserial primary key,
    user_id bigint not null references users(id) on delete cascade,
    amount numeric(15, 2) not null,
    payment_method varchar(50) not null,
    transaction_code varchar(100),
    status varchar(20) not null default 'pending',
    description text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

insert into deposit_history (id, user_id, amount, payment_method, transaction_code, status, description, created_at, updated_at)
values
(1, 1, 100000.00, 'momo', 'TXN001', 'success', 'Nạp qua MoMo thành công', '2026-04-24 03:20:49+00', '2026-04-24 03:20:49+00'),
(2, 2, 50000.00, 'card', 'TXN002', 'pending', 'Đang chờ xử lý thẻ Viettel', '2026-04-24 03:20:49+00', '2026-04-24 03:20:49+00'),
(3, 3, 200000.00, 'bank_transfer', 'TXN003', 'success', 'Chuyen khoan ngân hàng Vietcombank', '2026-04-24 03:20:49+00', '2026-04-24 03:20:49+00'),
(4, 1, 10000.00, 'card', 'TXN004', 'failed', 'Sai menh gia the', '2026-04-24 03:20:49+00', '2026-04-24 03:20:49+00'),
(5, 4, 300000.00, 'momo', 'TXN005', 'success', 'Nạp MoMo tự động', '2026-04-24 03:20:49+00', '2026-04-24 03:20:49+00'),
(6, 2, 70000.00, 'card', 'TXN007', 'success', 'The Vinaphone hop le', '2026-04-24 03:20:49+00', '2026-04-24 03:20:49+00')
on conflict (id) do nothing;

select setval(pg_get_serial_sequence('deposit_history', 'id'), greatest((select coalesce(max(id), 1) from deposit_history), 1), true);
