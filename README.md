# TodayUK Publishing OS

Sprint 1 foundation in progress. Private Sites newsroom preview and Supabase platform core.

See docs/SPRINT_1.md for verified status and remaining acceptance criteria.

The preview uses a labelled database snapshot. Supabase schema and CR seed are applied; app authentication and live reads remain in progress.

Development: npm ci, npm run dev. Checks: npm test, npx tsc --noEmit, npm run build.

GitHub Actions validates changes on Linux. Never commit credentials or environment files.
