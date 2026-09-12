# Sprints 13–16 — Advertising, commercial handoff, network UX and launch checks

Social distribution (S12) is deferred at the owner's request until their Facebook page is ready.

## S13: Google Ad Manager integration
Five responsive placement paths, contextual targeting and Digital Unicorn house fallback. The Google script is requested only when enabled with valid paths and a supported positive TCF consent signal. No identity/history targeting. A filled-slot event replaces house creative; empty/blocked/unconfigured slots keep it. Consent withdrawal destroys Google slots and restores house ads. Google is the source of truth for allocation, Standard/Sponsorship guarantees, AdSense competition, forecasts, billing and revenue. Campaign briefs store intended dates, goal, placement, targeting, creative/destination and external line item reference, but do not activate/reserve inventory or claim delivery data.

Pending: Google session currently returns the Ad Manager marketing/sign-in page, not a network. No network, units, AdSense linkage, paid fill, consent integration or delivery report verified. Config remains disabled. This is integration preparation, not a completed operational Ads Manager acceptance.

References: https://developers.google.com/publisher-tag/reference ; https://support.google.com/admanager/answer/177279 ; https://support.google.com/admanager/answer/2566645

## S14: commercial handoff
Administrator-only commercial relationship records; exact website-domain deduplication, permission evidence, TodayUK reference, DU reference, downloadable handoff and manual acknowledgement/conversion audit. Reader and community/public-sector records are explicitly excluded. No data are sent to DU automatically; no DU CRM is modified. Export is marked delivery-unconfirmed. Pending: agreed receiving endpoint/import and real receipt/conversion test. Automatic cross-system integration is not complete.

## S15: homepage and network
Existing map-led TodayUK theme retained and tracked in repository. Accessible controls for CR/RH/SM/BR/TN update latest published stories through a public allowlisted read-only endpoint. No unpublished/password-protected content is returned. Empty areas are identified honestly. Explicit area beats saved member preference; default is CR. Optional browser location is used locally only when requested by the visitor to suggest nearby supported coverage. No precise location storage or third-party geocoding. Visible page refreshes latest feed every two minutes. Local quick-find links reach structured modules; typography/mobile layout improved. No false email newsletter form. UK-wide postcode polygons/automatic coarse IP territory remain outside this implemented five-area slice; no location inference is claimed by default.

## S16: operational checks
Private launch dashboard: storage, HTTPS, published count, recorded mail outcome, advertising state, commercial transfer state, participation count, links to moderation/Site Kit/WordPress health and an explicit manual acceptance checklist. Mail transport acceptance is not inbox delivery. Historical email failure remains a launch blocker. No invented traffic/revenue figures or automatic 100% score.

Pending end-to-end acceptance: separate reader account/permissions, quiz entry/winner, paid ads/consent, commercial receipt, actual analytics receipt, Facebook sharing, accessibility/performance across representative content. CI and live smoke checks do not replace these. No paid AI, DNS/real-domain changes or new account purchases.
