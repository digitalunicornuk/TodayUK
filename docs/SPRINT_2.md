# Sprint 2 — discovery desk

Implemented slice: Croydon Council RSS fetched on demand, immutable original item payload/text, workspace-wide canonical URL deduplication, audited triage (new/shortlisted/dismissed), source pause/resume and trust classification, manual web/social URL and tip intake, searchable latest-200 queue and source detail drawer.

Council feed: https://news.croydon.gov.uk/feed/ . Server fetches are restricted to this reviewed HTTPS endpoint, disallow redirects, have a 15-second timeout and 2 MB bound. XML declarations with document types/entities are rejected. Original markup is retained in the payload and displayed as text, never injected as HTML. Council Azure-origin links normalize to the official public domain.

Only discovery is enabled by the migration. Publishing and all other workflow switches remain off. Members can read; owners/editors can import; reviewers can triage; viewers cannot mutate. Original items cannot be updated or deleted by clients. Unique database keys make retries safe. Duplicate URLs preserve the first original; revision detection belongs to Sprint 3.

Scheduled background polling and arbitrary feed onboarding are not implemented in this slice. Manual web/social references retain supplied text; they do not scrape external pages. No drafts or publication approvals are created by shortlisting. The blueprint's full S2 is not marked complete until polling and broader source onboarding are delivered and tested.

Validation: embedded PostgreSQL permission/immutability/duplicate/audit checks, parser/URL/size guard implementation, XML and fetch allowlist tests, TypeScript and Linux production build. Live migration and import verification are recorded after deployment.
