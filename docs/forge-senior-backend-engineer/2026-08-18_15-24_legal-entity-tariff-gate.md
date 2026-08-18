# Legal-entity tariff gate

- **Date:** 2026-08-18 15:24
- **Scope:**
  - `app/Support/AbonentProfile.php` — new `isLegalEntity(): bool` reader for the `legal` field on `/abonent/info`
  - `app/Http/Controllers/TariffController.php` — `abort_if($profile->isLegalEntity(), 403)` in `index()` and `connect()`
  - `resources/views/partials/topbar.blade.php` — filters the `tariff` nav item out of `$items` for a legal entity
  - `resources/views/cabinet/index.blade.php` — filters the tariff card out of `$cards` for a legal entity
  - `docs/api/SOLA_API_REFERENCE.md` §3 — documented the new `legal` field
  - `tests/Unit/AbonentProfileTest.php` (new) + 3 new tests in `tests/Feature/CabinetTest.php`

- **Decisions:**
  - Field name/values are exactly as the client stated (2026-08-18): individual = literal string `"Физическое лицо"` or `0`; anything else = legal entity.
  - No extra SOLA API call needed: every cabinet controller (`CabinetController`, `TariffController`, `DeviceController`, `TrafficController`, `PaymentController`) already builds `AbonentProfile` from `/abonent/info` per request and passes it to the view as `profile` — the nav/dashboard filters just read that.
  - Missing `legal` key → fails **open** (not restricted), matching `AbonentProfile`'s existing convention for fields not every account/environment sends yet (candidate-key null-safe pattern already used for `contract_number`, `next_tariff`, etc.). This is deliberately different from `AbonentSession::isPermanent()`, which fails closed — that gate is a session-persisted access-control type from `/identify`, this one is a per-request display-gating field from `/abonent/info`.
  - Server-side `abort_if` in `TariffController` is the actual control; the Blade filters are a courtesy only, matching this codebase's own stated philosophy ("The view's conditions are a courtesy to the subscriber, not a control").
  - Did not touch `isPermanent`/`abonentType`/`AbonentSession` (orthogonal, pre-existing gate for switching only) or the in-flight uncommitted admin `TariffVisibility` feature (composes cleanly — the legal-entity check runs before it in both controller methods).

- **Pipeline results:**
  - Debug/verify: full suite green, 109/109 (102 pre-existing + 7 new).
  - `forge-code-reviewer`: **APPROVE**, no blockers/warnings. Two nitpicks: duplicated 3-line filter block in the two Blade files (YAGNI'd, fine for two call sites), and the exact-string match on `"Физическое лицо"` could miss casing/whitespace variants.
  - `forge-security-auditor`: **SHIP**, no critical/high/medium findings. Confirmed the controller guard is real (checked fresh every request, no caching, no route bypass, no race window) and that `connect()` independently re-validates the tariff offer list even in a SOLA-outage edge case. Two low/hardening notes below.

- **Risks flagged:**
  - ⚠️ `isLegalEntity()` does an exact (trimmed) string match on `"Физическое лицо"`. If billing ever sends a casing or whitespace variant, a legitimate individual gets denied (fails closed for them, not a security hole, but a support ticket). Worth a case-insensitive compare if that turns out to happen in practice.
  - ⚠️ Missing `legal` field fails open by design (see Decisions). If billing rolls this field out unevenly, some legal-entity accounts on old data may still see the tariff section until their `/abonent/info` starts returning it.

- **Left for later:** none — the duplicated filter block noted by the reviewer is not worth abstracting for two call sites (YAGNI).

- **User must do:** nothing — no migration, no env var, no deploy step beyond the normal one. Worth confirming with billing that the `legal` field's exact string and casing stay stable in production.
