create table if not exists posts (
    id bigserial primary key,
    title varchar(180) not null,
    slug varchar(200) not null unique,
    category varchar(50) not null,
    summary text not null,
    content text not null,
    image_url varchar(500),
    status varchar(30) not null default 'published',
    is_featured boolean not null default false,
    sort_order integer not null default 0,
    published_at timestamptz not null default now(),
    created_at timestamptz not null default now()
);

alter table posts add column if not exists updated_at timestamptz not null default now();
alter table posts add column if not exists sort_order integer not null default 0;
alter table posts alter column title type text;
alter table posts alter column slug type varchar(255);
alter table posts alter column category type varchar(100);
alter table posts alter column summary type text;
alter table posts alter column content type text;
alter table posts alter column image_url type text;
alter table posts alter column status type varchar(50);

create table if not exists downloads (
    id bigserial primary key,
    platform varchar(60) not null,
    file_name varchar(255),
    version varchar(40) not null,
    file_size varchar(40) not null,
    download_url varchar(500) not null,
    notes text,
    is_active boolean not null default true,
    sort_order integer not null default 0,
    created_at timestamptz not null default now()
);

alter table downloads add column if not exists updated_at timestamptz not null default now();
alter table downloads add column if not exists file_name varchar(255);

create table if not exists bank_accounts (
    id bigserial primary key,
    bank_name varchar(120) not null,
    bank_code varchar(50) not null,
    acc_num varchar(80) not null,
    acc_name varchar(180) not null,
    code varchar(20) not null,
    bank_rate numeric(12, 4) not null default 1,
    is_active boolean not null default true,
    sort_order integer not null default 0,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

alter table bank_accounts add column if not exists bank_name varchar(120);
alter table bank_accounts add column if not exists bank_code varchar(50);
alter table bank_accounts add column if not exists acc_num varchar(80);
alter table bank_accounts add column if not exists acc_name varchar(180);
alter table bank_accounts add column if not exists code varchar(20);
alter table bank_accounts add column if not exists bank_rate numeric(12, 4) not null default 1;
alter table bank_accounts add column if not exists is_active boolean not null default true;
alter table bank_accounts add column if not exists sort_order integer not null default 0;
alter table bank_accounts add column if not exists created_at timestamptz not null default now();
alter table bank_accounts add column if not exists updated_at timestamptz not null default now();

create table if not exists payments (
    id bigserial primary key,
    user_id bigint references users(id) on delete set null,
    bank_account_id bigint references bank_accounts(id) on delete set null,
    transaction_id varchar(120) not null unique,
    bank varchar(50),
    type varchar(20) not null default 'IN',
    amount numeric(15, 2) not null,
    coin_amount integer not null default 0,
    status varchar(30) not null default 'success',
    description text,
    raw_payload jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

alter table payments add column if not exists status varchar(30) not null default 'success';
alter table payments add column if not exists updated_at timestamptz not null default now();

create table if not exists deposits (
    id bigserial primary key,
    user_id bigint not null references users(id) on delete cascade,
    payment_id bigint references payments(id) on delete set null,
    amount numeric(15, 2) not null,
    coin_amount integer not null default 0,
    payment_method varchar(50) not null default 'vietqr',
    transaction_code varchar(120),
    status varchar(20) not null default 'success',
    description text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists promotion_campaigns (
    id bigserial primary key,
    name varchar(180) not null,
    bonus_percent numeric(8, 2) not null default 0,
    starts_at timestamptz not null,
    ends_at timestamptz not null,
    is_active boolean not null default true,
    note text,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

alter table promotion_campaigns add column if not exists name varchar(180);
alter table promotion_campaigns add column if not exists bonus_percent numeric(8, 2) not null default 0;
alter table promotion_campaigns add column if not exists starts_at timestamptz not null default now();
alter table promotion_campaigns add column if not exists ends_at timestamptz not null default now();
alter table promotion_campaigns add column if not exists is_active boolean not null default true;
alter table promotion_campaigns add column if not exists note text;
alter table promotion_campaigns add column if not exists created_at timestamptz not null default now();
alter table promotion_campaigns add column if not exists updated_at timestamptz not null default now();

insert into bank_accounts (bank_name, bank_code, acc_num, acc_name, code, bank_rate, is_active, sort_order)
select 'BIDV', 'BIDV', '2152227004', 'NGUYEN THI LIEN', 'BIDV', 1, true, 1
where not exists (
    select 1 from bank_accounts where acc_num = '2152227004'
);

insert into posts (title, slug, category, summary, content, image_url, is_featured)
values
('Hướng dẫn di hang dong 9x Ninja Schờol 2D', 'huong-dan-di-hang-dong-9x-ninja-schờol-2d', 'huong-dan', 'Cach chưan bi va di hang dong 9x hieu qua chờ nguoi chời Ninja Schờol 2D.', 'Hang dong 9x la khu vuc phu hop chờ nhan vat cap cao muon luyen cap, san vat pham va phoi hop to doi.
Truoc khi vao hang, hay chưan bi day mau, chakra, thuc an tang chi so va sửa lai trang bi de trảnh bi ngat giua duong.
Nen di theo nhom co sat thuong, không che va ho tro hoi phuc. Khi gap quai dong, uu tien dung ky nang dien rong va giu khoang cach an toan.
Trong luc di hang dong 9x, hay tap trung clear quai theo tung cum, trảnh tach doi hinh qua xa va cảnh thoi gian hoi sinh cua quai de toi uu kinh nghiem.
Neu muc tieu la san do, nen thong nhat cach chia vat pham truoc khi vao map va uu tien nhung tang co quai phu hop voi suc mảnh cua doi.', '/img/post-default.webp', true),
('Khai mo may chu Lang Gio', 'khai-mo-may-chu-lang-gio', 'thong-bao', 'May chu moi mo cung chuoi qua đăng nhập chờ tan thu.', 'May chu Lang Gio mo cua voi nhiem vu tan thu, phan thuong đăng nhập va dua top luc chien trong tuan dau.', '/img/post-default.webp', true),
('Su kien san boss cuoi tuan', 'su-kien-san-boss-cuoi-tuan', 'su-kien', 'Boss the gioi xuat hien theo khung gio co dinh voi vat pham hiem.', 'Nguoi chời tham gia san boss co co hoi nhan trang bi, da cuong hoa va dảnh hieu gioi han.', '/img/ns2d-ninja.webp', true),
('Cap nhat he thong bang hoi', 'cap-nhat-he-thong-bang-hoi', 'tinh-nang', 'Bang hoi co thêm nhiem vu tuan, kho chung va bang xep hang cong hien.', 'Tinh nang bang hoi được mo rong de nguoi chời phoi hop nhieu hon trong cac hoat dong PvE va PvP.', '/img/post-default.webp', false),
('Hướng dẫn nạp an toàn', 'huong-dan-nap-an-toan', 'huong-dan', 'Chỉ nạp qua kênh chính thức và kiểm tra đúng tên nhân vật.', 'Không chia sẻ mật khẩu, ma OTP hoac thông tin tài khoản chờ nguoi tu xung la ho tro vien.', '/img/post-default.webp', false)
on conflict (slug) do nothing;

insert into downloads (platform, version, file_size, download_url, notes, sort_order)
values
('Android', '1.0.0', '95 MB', '#', 'APK dảnh chờ Android 8 tro len', 1),
('Windows', '1.0.0', '120 MB', '#', 'Ban chời tren PC', 2),
('iOS', 'Coming soon', '-', '#', 'Đang chuẩn bị', 3)
on conflict do nothing;
