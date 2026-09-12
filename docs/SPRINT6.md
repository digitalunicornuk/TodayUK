# S6 — Published content CRM

Blueprint scope: manage live content, edits, updates, follow-ups and monitoring.

Implemented: published content list with source-group links; owner editing of existing WordPress posts; 400-word and editorial-check gate; saved risk notes; same post ID and URL; version and concurrent-operation guards; remote-content fingerprint checks before updates; explicit reconciliation for interrupted/uncertain outcomes; persistent follow-up tasks, due dates and completion; persistent live status and change notices. Live checks repeat every five minutes while the selected article is open, visible and not being edited. They do not run with the newsroom closed.

AI material-development alerts remain paused under the owner's instruction not to enable paid AI. This release does not introduce background scheduled monitoring, performance analytics or social distribution (later blueprint sprints). It does not automatically import edits made directly in WordPress: those are flagged for review before replacement. WordPress has no atomic compare-and-swap in this integration; a second read narrows, but cannot eliminate, the race with external editors. Exact post content is read back after updating, with uncertain outcomes locked for reconciliation.

Validation: database tests cover owner/viewer access, stale versions, active update locks, mismatched operation tokens, uncertain outcomes, reconciliation, follow-up persistence and attributed auditing. WordPress transport tests verify updates address the same post and preserve its status. Live validation is recorded after deployment.
