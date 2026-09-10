# Sprint 1 — in progress

Reference: TodayUK Master Blueprint V1, 10 September 2026.

## Current delivery
- Sites project scaffold and CR News foundation preview.
- Draft Supabase migration: workspaces, membership roles, generic hierarchy, tags, factual/audience/context relationships, module settings and append-only client audit history.
- Idempotent TodayUK / CR / eight-district seed.
- RLS enabled, anonymous grants revoked, membership writes backend-only, cross-workspace relationship constraints.

## Not yet complete
- Run migration and permission tests against a disposable Supabase development database before production application.
- Add hierarchy cycle prevention and immutable workspace ownership checks.
- Confirm initial newsroom owner's Supabase Auth user ID; bootstrap only that explicit member via a controlled migration.
- Connect authenticated app reads; current preview clearly shows planned seed data.
- Implement module enforcement in future backend workflows; every seed switch defaults off.
- Verify two-workspace isolation, role allow/deny, audit immutability and cross-workspace relationships.
- Sync source to the existing public digitalunicornuk/TodayUK repository once Git write access is available. Do not upload the private blueprint to the public repository without an explicit publication decision.

Supabase project: jtacoapgmpzuitqlzuwe. No secrets stored in source.
Supabase public schema inspected in browser: no tables/views, 10 September 2026.
No database mutation has been applied by this task.

Reference implementation guidance: https://supabase.com/docs/guides/database/postgres/row-level-security

## Next vertical slice
Croydon Council RSS -> retained original item -> proof/risk decision -> editorial draft -> approved WordPress publication -> managed live content.
WordPress credentials and CR URL structure are needed for Sprint 5, not to start Sprint 1.
