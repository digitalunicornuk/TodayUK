# Sprint 1 — foundation verified

## Completed
- Sites newsroom foundation source, saved in digitalunicornuk/TodayUK.
- Supabase foundation applied on 10 September 2026 to project jtacoapgmpzuitqlzuwe.
- TodayUK workspace, CR News publication and eight districts seeded: CR0, CR2, CR3, CR5, CR6, CR7, CR8, CR9.
- Seven application tables: workspaces, workspace_members, hierarchy_nodes, tags, node_tags, module_config and audit_events.
- Workspace-scoped read policies; controlled editor writes; anonymous grants removed; cross-workspace relationships blocked; client membership escalation and audit modification denied.
- Immutable workspace ownership. Editors may rename hierarchy nodes; structural changes are backend-only and include cycle checks.
- Eight district tags and factual links. Eight workflow modules disabled by default. Seed produced 33 audit records.
- Migration and seed executed through the logged-in Supabase SQL editor after local PostgreSQL tests. Applied version recorded in newsroom_private.applied_migrations. Supabase CLI history is not initialized; reconcile this applied version before first CLI database push.
- 26 embedded PostgreSQL tests pass. GitHub Linux build and TypeScript checks passed on initial source; each subsequent push is validated again.

## Live verification
- Passwordless sign-in and owner membership verified in the hosted newsroom on 10 September 2026.
- Live workspace reads display CR News, eight districts, tags and audit activity.
- Callback cookies are attached explicitly to the redirect response; resend cooldown and visible authentication errors added.
- Discovery mutations and audit coverage continue in Sprint 2.

## Build environment
Mac native build tools stall during startup. Dependency installation without setup scripts permits TypeScript and embedded PostgreSQL checks. Linux GitHub Actions performs the complete build. No system-wide macOS security settings were changed.

## Source and reference
Existing public repository: https://github.com/digitalunicornuk/TodayUK
The original blueprint is retained locally and excluded from public source history.
Permission guidance: https://supabase.com/docs/guides/database/postgres/row-level-security

## Next vertical slice
Croydon Council RSS -> retained original item -> proof/risk decision -> editorial draft -> approved WordPress publication -> managed live content.
WordPress access and CR URL structure are needed for Sprint 5.

## Dependency maintenance
Updated React, Vinext, Vite and Cloudflare tools to patched compatible releases. Pinned sharp 0.35.4 through an override for the Cloudflare image dependency. npm audit reports zero vulnerabilities after the update; Linux CI validates the resulting build.

## Live connection implementation
Added passwordless Supabase sign-in using the official SSR client, a fixed same-site PKCE callback, and a live newsroom read endpoint. The endpoint verifies the Supabase user before querying workspace membership and data; it never uses a service-role key. Database policies remain the authority for workspace access. User tokens are refreshed through the SSR cookie adapter; newsroom responses are private/no-store.

Owner provisioning and the allowed Supabase callback are complete. No owner email or privileged key is committed to source.
