# MISSING_APIS

Gaps between the cabinet's requirements and the SOLA Billing API as it actually
behaves today.

**Source of truth:** `docs/api/SOLA_API.md` — reverse-documented from 29 real
request/response pairs captured in Telescope, not from billing's own docs.
**Base:** `http://172.19.1.101:808` · all endpoints are `POST` + JSON, HTTP Basic
+ `X-Access-Token`.

Every item below is **blocked on billing**, not on this codebase. The cabinet has
no database — a field the API does not return cannot be derived, cached, or
computed. In each case the UI is already built and renders an honest empty state
(`—`, "Tanlanmagan", "Net dannyx") instead of inventing a value.

> **Verified by probing, not assumed (2026-08-10).** Every missing endpoint below
> was sent a real read-only request against the live API. All of them answer
> `code 109 "Неопределенный метод"` — byte for byte what a made-up path like
> `/zzz/nonexistent` answers. The gaps are measured, not inferred from silence.
> The probe also turned up one endpoint nobody had documented, `POST
> /acct/balance`, and settled the `saldo` unit question. See `docs/api/SOLA_API.md`
> §10.
>
> **`next_charge_date` cannot be derived — this was tried and reverted.** On
> 2026-08-10 the cabinet briefly computed it from the day of the month in
> `contract_date`. The client then confirmed the charge does **not** follow the
> contract day: it follows the **last tariff change**. Since `/tariff/history`
> does not exist either, nothing in the API can produce this date, and the
> fallback was removed the same day. `CabinetTest` pins the empty state so the
> shortcut is not re-added.
>
> This raises the value of `/tariff/history` below: it is not only spec §12
> history, it is the **second possible route to the charge date**.

---

### Task 1.1 — Header User Info Widget
* **Missing Field**: `contract_number` in `POST /abonent/info`
* **Reason**: The header must show "Shartnoma №"; the response carries only `contract_date`, never the number.

### Task 2.1 — Top Summary Cards
* **Missing Field**: `next_charge_date` (`Y-m-d`) in `POST /abonent/info`
* **Reason**: "Keyingi yechilish sanasi" depends on the billing cycle and payment history, which the cabinet cannot reconstruct.

### Task 2.2 — Tariff Management Block
* **Missing Field**: `next_tariff_name`, `next_tariff_cost` (tiyin) in `POST /abonent/info`
* **Reason**: `/tariff/connect` queues a tariff change but nothing in the API reports which tariff is queued, so "Keyingi tarif" can never leave the "Tanlanmagan" state.

### Task 2.2 — Current Tariff Cost
* **Missing Field**: `curr_tariff_cost` (tiyin) in `POST /abonent/info`
* **Reason**: Only `curr_tariff_name` is returned; reading the price from `/tariff/available` is unsafe because that list is "tariffs you may switch to" and need not contain the current one.

### Task 3.1 — Traffic Statistics
* **Missing Params**: `date_from`, `date_to` (`Y-m-d`) on `POST /traffic/detail`
* **Reason**: The endpoint accepts only `detail_month`, so an arbitrary range costs one HTTP call per month and is capped at 12 months (`App\Support\Period::MAX_MONTHS`).

### Task 3.2 — Financial Statistics: Status
* **Missing Field**: `payment_status_code` (`paid|pending|failed|cancelled`) in `POST /acct/payments`
* **Reason**: `payment_status` is free text **translated by `lang`**, so status detection currently depends on matching localized strings.

### Task 3.2 — Financial Statistics: Range
* **Missing Params**: `date_from`, `date_to` (`Y-m-d`) on `POST /acct/payments`
* **Reason**: Same month-at-a-time limitation as `/traffic/detail`.

### Task 4.2 — Loyalty Program
* **Missing Endpoint**: `POST /loyalty/info` → `bonus_balance`, `tier`, `privileges[]`
* **Reason**: Nothing about bonuses, client tiers, or privileges exists anywhere in the API; the block cannot be filled without inventing numbers.

### TZ §12 — Tariff Change History
* **Missing Endpoint**: `POST /tariff/history` → `change_date`, `old_tariff_name`, `new_tariff_name`, `changed_by`
* **Reason**: No endpoint returns past tariff changes.

### TZ §9 — Promotions & Personal Discounts
* **Missing Endpoint**: `POST /abonent/discounts` → `code`, `name`, `percent`, `amount`, `date_from`, `date_to`
* **Reason**: A personal discount is subscriber-bound data that must come from billing; only the generic entry-point card can be built without it.

---

## Not missing — naming differences

| TZ names it | Actually exists as | Note |
|---|---|---|
| `POST /api/tariff/change` | `POST /tariff/connect` | Takes `acc_id`, `tariff_id` (int), `tariff_conndate`. Sends **no** `lang` — the signature covers the exact body bytes, so the shape must not drift. |
| device attachment endpoint | `POST /device/new` | Eligibility is enforced by billing; the cabinet reads `connect_cost` from `/device/list`. |
| device eligibility check | `connect_cost` on `POST /device/list` | `-1` is returned in practice and its meaning is **unconfirmed** — the UI hides the price rather than displaying `-1`. |

## Open questions for billing

1. `saldo` unit — so'm or tiyin? (`cost` and `amount` are confirmed tiyin.)
2. Full list of `payment_status` values, or a machine code instead.
3. What `connect_cost = "-1"` means.
4. Full error-code reference (only `110 = Абонент не найден` and `114 = missing required parameter` observed).
5. Whether `errMsg` is translated by `lang` (all observed errors were fetched with `lang=ru`).

> When a field lands, wire it by adding the real key to the matching
> `CANDIDATE_*` list in `app/Support/AbonentProfile.php`. Nothing else changes —
> the dashboard fills in by itself.
