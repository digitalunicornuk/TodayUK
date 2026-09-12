# Sprints 9–11 — Combined community release

## Implemented
S9: signed-in reader text/link submission, automatic rule-based content classification (no API cost), private review queue, contributor history, verified community/commercial media links with rights declaration, retained original, editor changes, audit and a single linked WordPress draft. All contributors require publication approval. Verified status is administrator-only and gives no publishing permissions.

S10: structured local detail fields and section browsing for planning, events, directory, business changes, schools, sport, lost/found and free items. Required listing details prevent incomplete publication. Event details cover organiser, venue, date/time, recurrence, geography, category, price, booking/source/status. Free items cover collection, removal and vehicle needs. Lost/free/directory responses enter the existing private editor queue. Status supports closure of responses. Existing article engagement remains available. Commercial competitions are a separate listing type with rules/prize/sponsor hooks.

S11: weekly mixed news/local knowledge quiz authoring, time windows, one entry per account enforced by a database unique key, server-side grading, home-district snapshot, post-close results and confirmed-winner district league. Equal scores use earliest entry, disclosed before entry. Prize/donor/terms required. Admin confirms eligibility and handles prize fulfilment manually. Questions are immutable; a faulty quiz can be cancelled. No paid draw, automatic prize award, paid API, DU data transfer or marketing consent is introduced.

## Acceptance still required
Live upgrade and checks are recorded separately. Real reader password/email delivery is blocked by WordPress mail failure. Reader-session acceptance for submissions and quiz participation remains necessary; an admin test is not evidence of reader permissions. No production quiz or prize is invented for testing. Weekly quiz content and real donated prize details require editorial input. Media are contributor-supplied links reviewed by editors, not public automatic uploads. Organisation trust must be verified manually. Competition hooks do not implement paid/sponsored campaign fulfilment. These phases are not declared fully accepted until end-to-end checks pass.

Public entry points: ?todayuk=local, ?todayuk=submit, ?todayuk=quiz.
Editors: WordPress Posts → Reader submissions / Weekly quizzes. Structured listing details sit in the post editor. Current Sites newsroom runtime is unchanged by this WordPress-only release.
