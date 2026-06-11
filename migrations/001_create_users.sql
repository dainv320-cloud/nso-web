create table if not exists users (
    id bigserial primary key,
    username varchar(50) not null unique,
    email varchar(180),
    password varchar(255) not null,
    ban boolean not null default false,
    is_active boolean not null default true,
    type_admin smallint not null default 0,
    money integer not null default 0,
    totalmoney integer not null default 0
);

alter table users add column if not exists username varchar(50);
alter table users add column if not exists name varchar(255);
alter table users add column if not exists email varchar(180);
alter table users add column if not exists password varchar(255);
alter table users add column if not exists ban boolean not null default false;
alter table users add column if not exists is_active boolean not null default true;
alter table users add column if not exists type_admin smallint not null default 0;
alter table users add column if not exists money integer not null default 0;
alter table users add column if not exists totalmoney integer not null default 0;

update users set username = 'user_' || id::text where username is null or username = '';
update users set name = username where name is null or name = '';
update users set email = username || '@example.local' where email is null or email = '';
update users set password = '$2a$12$845CSTOCa.x5qoylH.DAeuUrwf92h1ywHlX8Nqzbq72j7QP0fNPr.' where password is null or password = '';

alter table users alter column name set not null;
alter table users alter column username set not null;
alter table users alter column password set not null;

create unique index if not exists users_username_unique on users(username);
create unique index if not exists users_email_unique on users(email) where email is not null;

insert into users (id, name, email, username, password, ban, is_active, type_admin, money, totalmoney)
values
(1, 'admin', 'admin@example.local', 'admin', '$2a$12$845CSTOCa.x5qoylH.DAeuUrwf92h1ywHlX8Nqzbq72j7QP0fNPr.', false, true, 99, 19990, 0),
(2, 'admin2', 'admin2@example.local', 'admin2', '$2a$12$845CSTOCa.x5qoylH.DAeuUrwf92h1ywHlX8Nqzbq72j7QP0fNPr.', false, true, 0, 7809999, 0),
(3, 'admin3', 'admin3@example.local', 'admin3', '$2a$12$845CSTOCa.x5qoylH.DAeuUrwf92h1ywHlX8Nqzbq72j7QP0fNPr.', false, true, 1, 0, 0),
(4, 'admin4', 'admin4@example.local', 'admin4', '$2a$12$845CSTOCa.x5qoylH.DAeuUrwf92h1ywHlX8Nqzbq72j7QP0fNPr.', false, true, 99, 0, 0)
on conflict (id) do update set
    name = excluded.name,
    email = excluded.email,
    username = excluded.username,
    password = excluded.password,
    ban = excluded.ban,
    is_active = excluded.is_active,
    type_admin = excluded.type_admin,
    money = excluded.money,
    totalmoney = excluded.totalmoney;

select setval(pg_get_serial_sequence('users', 'id'), greatest((select coalesce(max(id), 1) from users), 1), true);

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
