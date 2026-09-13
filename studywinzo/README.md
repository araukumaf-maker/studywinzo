# StudyWinzo

PHP education app with Supabase Postgres and Supabase Storage.

## Wasmer setup

1. Create the database and Storage bucket by running `supabase/schema.sql` in the Supabase SQL Editor.
2. Add the variables from `.env.example` to Wasmer. `SUPABASE_SERVICE_ROLE_KEY` is server-only; never put it in browser JavaScript or GitHub.
3. Set the start command to `bash start.sh`. The script listens on Wasmer's `PORT` and binds to `0.0.0.0`.
4. Open `/admin/` and sign in with `ADMIN_USER` / `ADMIN_PASS`.

All uploads, including chunked videos and documents, are saved to Supabase Storage. Wasmer's local filesystem is only used as a temporary upload buffer.