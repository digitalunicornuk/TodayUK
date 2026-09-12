# Sprint 7 — Engagement Engine

Blueprint section 7 and sprint 7. A single WordPress plugin supplies the configuration, storage and rendering for NIMBY rating, Good/Bad sentiment, polls, Track This, Seen This, Might Be Mine, event attendance, Helpful, calendar reminders, local sharing, private updates/reports/claims and area/topic/organisation follows.

## Implemented

- Automatic keyword selection with per-article Custom / Off override; no paid AI call.
- One durable response table, unique user/tool/scope key, replacing a previous answer rather than duplicating it. Changed poll questions/options get an independent scope.
- Sign-in required for saved participation; public reading, aggregates, sharing and calendar download remain open.
- CSRF nonce checks, published-post validation, stale configuration rejection, validated choices, escaped output, message length limit, per-account rate limit and editor-per-post authorization.
- Reports/claims/updates are private; editor inbox supports handled/reopen. Reader can remove a response.
- Follow targets are explicit editor-set labels. Track/follow selections have a profile reading list. No notification delivery is claimed.
- Shared action uses the browser share sheet or copies the story link; never posts automatically to social groups.
- Calendar reminder is an ICS download for an editor-supplied event date.
- Published newsroom article management links directly to WordPress article configuration and the reader inbox.

## Boundaries

AI tool selection is paused under the owner's no-API-spend instruction. Rules supply the automatic selection during testing. WordPress accounts gate participation for now; the public reader onboarding/unified identity work belongs to Sprint 8. Email/push alert delivery and follow-based personalised feeds are not implemented by this sprint. Private claims are routed to editors, never automatically approved or forwarded to another reader. Calendar reminders depend on the reader importing the file. There is no end-to-end notification service.

The editor inbox currently shows the latest 200 responses. Account deletion removes associated responses; uninstall does not delete the table. Further privacy export/retention controls should accompany Sprint 8 onboarding before inviting readers.

## Verification

PHP syntax and catalog selection/sanitization checks run in CI alongside the existing application tests and build. Live WordPress verification: plugin activates, story controls render, a marked test report persists and appears in the editor inbox. Final rollout status is recorded in the workspace output report.
