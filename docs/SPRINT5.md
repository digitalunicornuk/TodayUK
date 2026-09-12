# Sprint 5 — WordPress handoff

Implemented publishing desk and server-only WordPress client. Owner-only actions reserve the exact approved revision, send a WordPress draft, retain its post ID/link and allow a separate explicit public-publish action. Content is escaped as text paragraphs; internal research/risk notes are excluded. Redirects are not followed with credentials. Transfer records are workspace-scoped and audited. Duplicate sends are blocked; uncertain or interrupted transfers require reconciliation before another send. Draft editing is blocked during an active transfer.

Connection requires WORDPRESS_URL, WORDPRESS_USERNAME and WORDPRESS_APPLICATION_PASSWORD as server-only runtime settings. No credentials are stored in Git or exposed in browser responses. The site owner must create the application password and enter it through a secure setup path. No destination is guessed.

Tests cover client request construction and failures, SQL role restrictions, revision checks, duplicate sends, transfer locking and isolation. These are local/mocked checks, not proof of a working WordPress integration.

Remaining before the sprint milestone: obtain the intended WordPress URL and connection, confirm CR category/URL mapping, test against that destination, prepare and review a real sourced article, explicitly publish it and verify its live URL. Image upload, automatic synchronization of later WordPress edits and recovery tooling remain future work. The sprint is not complete until the real end-to-end test passes.

Implementation references: https://developer.wordpress.org/rest-api/reference/posts/ and https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/ .

Update 12 September: local draft transfer resolves the CR News category by slug and includes its ID in the WordPress post. Missing categories prevent post creation. Mock checks cover category assignment and absence; real destination transfer remains unverified.
