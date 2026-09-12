# Sprint 8 — Accounts and reader intelligence

WordPress reader accounts remain separate from newsroom access and Digital Unicorn. Public browsing is unrestricted. A new My TodayUK surface supports free subscriber registration using an emailed password-setting link, existing WordPress sign-in/reset, one home district and multiple followed districts/topics.

Membership defaults to individual reader. Only an administrator may assign community/non-profit, commercial/business or internal public-sector relationship classes; these do not grant editorial permissions or transfer data.

The account feed preserves recency within interest matches and includes a discovery story every fifth position when content is available. It uses explicit category/tag matches, never claims unmatched articles belong to a district. Sparse inventory falls back to general editorial coverage. Explicit home preferences take precedence; no location is inferred.

Saved engagement follows and tracked stories appear on the account page, with account-scoped removal. In-app updates are opt-in, use matching published stories and support mark-as-read. Email/push distribution is not enabled.

Reading history is off by default. With permission, a visible story open for 15 seconds is remembered, capped at 100 entries, and previously read stories move behind unread content. Withdrawing permission clears history. Account data can be downloaded; WordPress privacy export/erasure hooks cover preferences, history and engagement. Data stays with TodayUK.

Validation includes input allowlists, no role changes through preference input, sparse feed order, deduplication and 20% discovery slots. Live installation and account-page checks are recorded in the workspace sprint status report. Registration email delivery requires a real reader test; no synthetic account emails should be sent without authorization.
