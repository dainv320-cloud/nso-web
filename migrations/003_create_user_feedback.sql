create table if not exists user_feedback (
    id bigserial primary key,
    user_id bigint not null references users(id) on delete cascade,
    type varchar(30) not null,
    subject varchar(180) not null,
    content text not null,
    status varchar(30) not null default 'new',
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now(),
    constraint user_feedback_type_check check (type in ('bug', 'feature')),
    constraint user_feedback_status_check check (status in ('new', 'reviewing', 'done'))
);

alter table user_feedback add column if not exists user_id bigint references users(id) on delete cascade;
alter table user_feedback add column if not exists type varchar(30);
alter table user_feedback add column if not exists subject varchar(180);
alter table user_feedback add column if not exists content text;
alter table user_feedback add column if not exists status varchar(30) not null default 'new';
alter table user_feedback add column if not exists created_at timestamptz not null default now();
alter table user_feedback add column if not exists updated_at timestamptz not null default now();

create index if not exists user_feedback_user_id_idx on user_feedback(user_id);
create index if not exists user_feedback_status_idx on user_feedback(status);
create index if not exists user_feedback_type_idx on user_feedback(type);
create index if not exists user_feedback_created_at_idx on user_feedback(created_at desc);
