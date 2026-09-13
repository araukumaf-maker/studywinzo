-- StudyWinzo schema
-- Run this once in Supabase SQL Editor before deploying the PHP app.

create table if not exists public.institutions (
  id text primary key,
  name text not null,
  logo text not null default '',
  color text not null default '#2cee82',
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.batches (
  id text primary key,
  institution_id text references public.institutions(id) on delete cascade,
  name text not null,
  subject text not null default '',
  color text not null default '#2cee82',
  image text not null default '',
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.subjects (
  id text primary key,
  batch_id text references public.batches(id) on delete cascade,
  name text not null,
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.chapters (
  id text primary key,
  subject_id text references public.subjects(id) on delete cascade,
  name text not null,
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.content (
  id text primary key,
  chapter_id text references public.chapters(id) on delete cascade,
  type text not null check (type in ('note', 'dpp', 'video', 'link')),
  title text not null,
  description text not null default '',
  file text not null default '',
  video_url text not null default '',
  external_url text not null default '',
  thumbnail text not null default '',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.kv_store (
  key text primary key,
  data jsonb not null default '{}'::jsonb,
  updated_at timestamptz not null default now()
);

-- Public pages use Supabase public object URLs, while PHP uses the
-- service_role key for writes. Do not expose that key in browser code.
insert into storage.buckets (id, name, public)
values ('studywinzo', 'studywinzo', true)
on conflict (id) do update set public = excluded.public;