# Uniform System — Fresh-start E2E guide

**Goal:** Test the full product from an empty business DB (no mock hospital/products).  
**Seeded by default:** Kamatani admin logins only.  
**Local URL:** `http://127.0.0.1:8000`

**Official billing model (confirmed):** plan unit price (ex-tax) × **number of people on that plan** as of closing day — **not** × number of uniforms / SKUs, **not** × 契約口数. Tax is calculated **outside** this system.

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
# Optional (emails + queued mail): MAIL_MAILER=log is fine locally
# php artisan queue:work
```

**Automated tests (always run after changes):**

```bash
php artisan test
# Focused suites:
php artisan test --filter=EndToEndFlowTest
php artisan test --filter=ExcelClientRulesTest
php artisan test --filter=ClientBillingAndApplyRulesTest
```

See also: `PRODUCTION.md` (go-live), `README.md` (setup), `LOAN_REGISTRATION_GUIDE.md` (loan lifecycle).

---

## Latest end-to-end flow (one view)

```text
[Kamatani setup]
  Institution (+ hospital admin login, 締め日, 得意先コード, オノエコード)
  → Maker → Uniform products (size required for apply; use "F" for one-size)
  → Contract plan
      · 対顧客単価（税抜）＝請求に使用
      · 対帳合先単価＝登録枠のみ（請求未使用）
      · カテゴリ別貸与上限: 上衣 / 下衣 / その他 / その他2 / その他3
      · linked products (+ slot_category from Excel import)
  → Customer contract plan (hospital ↔ plan, 契約数＝参考枠)
      · start_date + end_date required when 本登録
      → Confirm (confirmed)

[Hospital]
  Staff (+ hire/resign, 主部署 + 兼務) → Assign confirmed plan
  → Apply uniform — **individual only** (per employee)
      types: 新規貸与 / サイズ変更 / 返却 / 交換 / 異動（同一職員の旧品＋新品）
      · Over category/total rentable cap → **hard block** (no special approval)
      · Stock shortage → 特別承認待ち (reason required)
      · Products without usable size (empty / "-") → hidden from catalog

[Kamatani ops]
  Accept (受付) → status 対応中
      · Re-checks rentable caps (incl. other pending apps)
      · Special approval only for stock (clamps stock ≥ 0)
  → PDFs / 受注一覧
  → Ship (出荷登録) → status 完了

[Kamatani accounting]
  Billing
      · Auto-generate on hospital 締め日 (daily schedule 01:00) and/or manual Generate
      · Official amount = 割当人数 × 契約プラン単価（税抜）
      · Excel shows 契約口数 + 割当 + 請求金額 + 得意先/オノエコード
```

Statuses (applications):

| Status (JA) | Meaning |
|-------------|---------|
| 申請 | Submitted by hospital |
| 特別承認待ち | **Stock shortage only** — waiting ops special approval |
| 対応中 | Accepted; internal PDFs / warehouse work |
| 完了 | Shipped / done |
| キャンセル申請中 | Hospital requested cancel (ops must approve) |

**申請一覧 vs 受注一覧:**  
- **申請一覧** = full lifecycle (pending → complete / cancel)  
- **受注一覧** = post-accept work queue (`対応中` / `完了`) + bulk PDF  

---

## Login credentials (seeded)

All seeded admin passwords: **`password123`**  
Change these in production (`SEED_ADMIN_PASSWORD` if re-seeding).

### Kamatani admin portal — `/admin/login`

| Role | Email | Role code | Typical use |
|------|-------|-----------|-------------|
| System admin | `kamatani-admin@example.com` | `system_admin` | Full masters, apps, billing, users, backups |
| Operations | `operations-admin@example.com` | `operation` | Accept apps, ship, products, recommendations, billing **view** |
| Sales | `sales-admin@example.com` | `sales` | CCP **read**, billing **view** (write buttons hidden) |
| Accounting | `accounting-admin@example.com` | `accounting` | Billing generate / Excel export |
| Contract plan manager | `plan-manager@example.com` | `contract_plan_manager` | Makers, plans, CCP write (not apps/billing) |

### Hospital portal — `/login` (not seeded)

Create when you add an institution (manager email/password on the form).

| Role | How |
|------|-----|
| `institution_admin` | Institutions → New → manager fields |
| `department_admin` | Hospital master → department admins |

**Example hospital login you create in Phase 1:**  
`hospital-admin@example.com` / `password123` (or whatever you set)

---

## What each side does

| Side | Who | Responsibility |
|------|-----|----------------|
| **Kamatani** | Admin portal | Institutions, makers, products, plans, CCP, accept apps, PDFs, ship, billing export |
| **Hospital** | `/login` | Staff, assign plans, **per-employee** apply, cancel request |

**Important rules (client-confirmed):**

- Contract **plan master** is reusable; each hospital has one or more **Customer Contract Plans** (CCP) with 契約口数.
- Kamatani sets hospital↔plan CCP; **hospital admin** assigns plans to staff (not Kamatani for day-to-day assignment).
- Applications are **individual only** (no department batch).
- Rentable caps are **per category** (上衣／下衣／その他／その他2／その他3). Over-limit use is **not allowed** (hard block).
- Special approval is for **stock shortage only**.
- Billing = 割当人数 × 単価（税抜）. Show 契約口数 + 割当 + 金額. No payment status in-app. Tax external.
- **得意先コード** may show on hospital UI; **オノエコード** is store + Excel/export only (not customer display).
- Products without size are hidden; one-size → register size **`F`**.
- Confirmed CCP requires **start and end dates** (no open-ended).
- Staff may have **multiple departments (兼務)**; department admins see affiliates (primary **or** concurrent).
- Hospital cannot set desired delivery date; use hire date / return-to-work date.
- Cleaning needs no application.
- Cancel request → Kamatani approval (no immediate delete).

---

## Master codes (auto + uniqueness)

On create/edit for institution, department, job type, maker, contract plan, and staff number:

| Behavior | Expected |
|----------|----------|
| Leave code **empty** | Unique code auto-generated on save (`MI-`, `DEP-`, `JOB-`, `SUP-`, `PLN-`, `EMP-` prefixes) |
| Type a code, then **blur** (leave the field) | Live check: available (green) or already taken (amber — clear or change) |

**Institution codes (client):**

| Field | Meaning | Hospital UI | Billing Excel |
|-------|---------|-------------|---------------|
| `code` | 得意先コード (e.g. `30170300`) | Show (OK / better) | Yes |
| `onoe_code` | オノエコード (e.g. `91703`) | **Do not show** | Yes (Kamatani / core) |

**Pass if:** Empty save creates a code; duplicate manual code shows warning and server rejects if forced.

---

## Phase 0 — Empty state check (system admin)

Login: `kamatani-admin@example.com` → `/admin/login`

| Check | Expected |
|-------|----------|
| Institutions list | Empty |
| Products / makers / plans / CCP | Empty |
| Applications / billing | Empty |
| Admin users | Seeded Kamatani roles present |

---

## Phase 1 — Kamatani configuration (in order)

Stay as **system admin** (or use plan manager where noted).

### 1. Create hospital + hospital admin login

**Menu:** Master data → Institutions → New

Fill at least:
- Institution name  
- **得意先コード** — optional (auto if empty)  
- **オノエコード** — optional (export only; not shown to hospital)  
- Notification email  
- **締め日** (billing closing day, e.g. `1`) — drives auto + manual billing as-of date  
- Sales person (optional)  
- **Manager name / email / password** ← hospital institution admin  

Price for billing lives on **Contract Plan → 対顧客単価（税抜）**, not on the hospital.

**Pass if:** Institution appears; you can log in at `/login` with the manager; hospital sidebar/dashboard shows 得意先コード, **not** オノエ.

Optional: Institutions → **Welcome email** (+ history). Locally with `MAIL_MAILER=log`, check `storage/logs`. Prefer running `queue:work` because mailables are queued.

---

### 2. Hospital masters (dept / job / delivery)

- **Kamatani:** Master data → Departments / Job types / Delivery locations, **or**
- **Hospital:** `/login` as institution admin → Master data

Create at least **2 departments** if you will test 兼務 (e.g. 内科 + 外科), 1 job type, 1 delivery location.

**Pass if:** Employee form can select dept/job; apply can pick delivery.

---

### 3. Maker (仕入先／メーカー)

**Menu:** Makers → New — code optional (`SUP-…`), name required, Tel/Fax/備考, active.

**Pass if:** Maker available on product form.  
**Sales:** index is view-only (no 新規/編集).

---

### 4. Uniform products

**Menu:** Uniform products → New — maker, product code/name/category/gender/sizes + stock.

| Size rule | Behavior |
|-----------|----------|
| Empty or `-` | Hidden from apply catalog |
| `F` | Allowed (one-size / fre size) |
| Letter / shoe sizes | Normal |

Categories include: 上衣 / 下衣 / 靴 / その他 / **その他2** / **その他3**.

**Pass if:** Products with size show on apply; stubs without size do not.

---

### 5. Contract plan (plan master)

**Menu:** Contract plans → New  
- Code / name  
- **カテゴリ別貸与上限** (上衣・下衣・その他・その他2・その他3) — enforced separately on apply  
- **対顧客単価（税抜）** — used in billing  
- **対帳合先単価（税抜）** — optional registration field only (not billed)  
- Check 適用可能ユニフォーム  

**Pass if:** Plan shows category caps, customer unit price, partner price (if set), linked products. Saving the plan **preserves** Excel `slot_category` on pivots.

---

### 6. Customer contract plan (hospital ↔ plan)

**Menu:** Customer contract plans → New  
- Hospital, plan, **契約数** (contracted slots = **reference capacity**, not invoice multiplier)  
- **start_date + end_date required** when status = confirmed  
- Confirm → **本登録**

Provisional (仮登録) plans cannot be assigned for applications / billing until confirmed.

**Pass if:** Status confirmed with both dates. Form helper: 請求 = 割当人数 × 単価.

**Ops note:** Hospital does not increase plans/slots in the app. They contact Kamatani; admin updates CCP.

---

## Phase 2 — Hospital configuration

Login: hospital manager from step 1 → `/login`

### 7. Create staff + assign plan

**Menu:** Employees → Add  
- Staff no (optional / auto)  
- Hire date / resign date (used by billing as-of logic)  
- Sizes, **主部署**, **兼務** rows (部署 + 職種) + optional per-position plans  
- **Assign confirmed CCP** (required before apply)

**Pass if:** Apply search shows plan name and plan products; category limit breakdown appears when caps are set.

### 8. (Optional) Department admin

Create under hospital masters; test affiliation visibility.  
**Pass if:** Department admin **without** department assigned gets 403 (not institution-wide access).

---

## Phase 3 — Core E2E (happy path)

| Step | Who | Login | UI | Action | Pass if |
|------|-----|-------|----|--------|---------|
| 9 | Hospital | institution admin | Applications → New | **Individual** 新規貸与, plan products only, dates, delivery, submit | Status **申請** |
| 9x | Hospital | same | Try over category cap | Add more than 上衣 (or other) cap | **Hard blocked** — cannot submit even with special-approval reason |
| 10 | Ops | `operations-admin@…` | Admin Applications | **受付** | **対応中**; PDFs issue |
| 10b | Ops | same | **受注一覧** | Multi-select bulk PDF | Same docs for 対応中/完了 |
| 11 | Ops | same | App detail | **出荷登録（完了）** | **完了** |
| 12 | Accounting | `accounting-admin@…` | Billing → Generate **or** wait for auto on 締め日 | Hospital + as-of → export | Official ≈ **割当人数 × 単価（税抜）**; Excel has 契約口数 / 割当 / 金額 / codes |

### Billing check detail (step 12)

Engine: `PlanBillingService` + `BillingGenerationService` (auto on closing day)

```text
As-of date = institution 締め日 (or override)
Include staff only if:
  - employed as of that date (join/retire + status)
  - has active employee↔plan assignment
  - hospital CCP is confirmed and within start/end
Official amount = people_on_plan × plan.unit_price_ex_tax
  (NOT × uniform pieces; NOT × 契約口数; NOT × partner_unit_price)
契約口数 = reference capacity only
消費税 = external (UI/Excel show ex-tax only)
```

Schedule: `billing:generate-closing-day` daily at 01:00 (`routes/console.php`).

---

## Phase 4 — Role UI checks

| Role | Login | Must see | Must not (menu / write) |
|------|-------|----------|-------------------------|
| system_admin | kamatani-admin | All admin menus | — |
| operation | operations-admin | Apps, 受注一覧, products, recommendations, billing **view** | Plan/CCP/maker write, billing generate |
| sales | sales-admin | CCP read, billing **view** | Apps accept, CCP/plan write buttons, billing generate |
| accounting | accounting-admin | Billing generate + export | Apps accept, plan masters |
| contract_plan_manager | plan-manager | Makers, plans, CCP write | Billing, accept apps, products |
| institution_admin | (step 1) | Apps, employees, masters, view contract plans, 得意先コード | Accept/ship/billing, オノエコード |
| department_admin | (step 8) | Affiliated apps/employees (主 or 兼務) | Institution masters / contract setup |

---

## Phase 5 — Extra checks

| # | Feature | Pass if |
|---|---------|---------|
| 13 | Over rentable / category cap | **Hard block**; special approval does **not** bypass |
| 13b | Stock shortage | Status 特別承認待ち; reason required; accept clamps stock at ≥ 0 |
| 13c | Second pending app that would exceed cap | Blocked (pending qty counts toward limit) |
| 13d | Forged `return_items` on `new_loan` | Rejected |
| 14 | Cancel before ship | Ops approve cancel |
| 15 | Cancel after complete | Button disabled |
| 16 | Staff without plan | Apply blocked / empty catalog |
| 17 | Outside-plan product | Not selectable |
| 17b | Product size `-` / empty | Hidden from apply; size `F` visible |
| 18 | Join date after as-of | Excluded from bill |
| 19 | Resign on/before as-of | Excluded from bill |
| 20 | Unconfirmed CCP / missing dates | Not billable / confirm rejected |
| 21 | 異動（部分変更） | Same employee: `return_items` + `items`; no 移管先 |
| 22 | Bulk PDF / 受注一覧 | Multi-select for 対応中/完了 |
| 23–24 | Employee CSV | Multi-row 兼務 + plan fields |
| 25 | Plan manager path | Plans OK; no billing |
| 26 | Concurrent plan / 兼務 | Assignments saved |
| 27 | Auto code | Code assigned; duplicate warns |
| 28 | List pagination | 10/20/30/50 (default 10) |
| 29 | Welcome email | Send + history |
| 30 | Recommended uniforms | Admin CRUD; plan still gates eligibility |
| 31 | Admin users / notifications | `receives_app_notifications` respected |
| 32 | DB backups | system_admin |
| 33 | Lockout | 5 fails → 15 min lock + mail |
| 34 | Re-import base data | Preserves CCP `contract_count`; keeps category caps + `slot_category` |
| 35 | Edit contract plan after import | `slot_category` not wiped |

---

## Phase 6 — What NOT to expect

- Seeded hospital users from `DatabaseSeeder` alone (create manager on institution form)  
- **Department batch apply** (disabled — individual only)  
- Over-limit loan via special approval (hard block)  
- Invoice / payment status / ERP import  
- Billing from loan piece counts or product SKUs  
- Billing = 契約口数 × 単価 (口数 is reference only)  
- Billing using 対帳合先単価  
- In-app consumption tax calculation  
- Matsuda-style item + piece-tier billing — **not** implemented  
- Hospital increasing contract slots themselves  
- Desired delivery date from hospital  
- Cleaning application  
- Immediate delete of an application from hospital side  
- Person-to-person 移管 (use 異動 = same-employee partial change)  
- Open-ended CCP (no end date) when confirmed  
- Gender split for same plan code — **still 確認中 with client (Excel Q2)**  

---

## Frontend ↔ backend mapping (smoke)

| Flow | Form / UI | Backend expects | OK if |
|------|-----------|-----------------|-------|
| Individual apply | `application_mode=individual`, `employee_id`, `items[]` | `ApplicationController@store` | App for selected staff |
| Departmental mode | — | Rejected (`in:individual` only) | Validation error |
| Category / total cap | Progress + category breakdown | `loanLimitViolation` (+ pending apps) | Hard block over cap |
| Stock shortage | Special approval reason | `waiting_special_approval` | Ops accept clamps stock ≥ 0 |
| 異動 | `return_items[]` + `items[]` | `exchange_out` + `loan` | Detail shows 旧品 + 新品 |
| Billing | Generate / auto closing day | `assigned_count × unit_price` | Matches headcount; codes on Excel |

---

## Automated test map

| Test class | Covers |
|------------|--------|
| `EndToEndFlowTest` | Apply → accept → bill; forged returns; pending double-submit; special-approval stock clamp; slot_category preserve |
| `ExcelClientRulesTest` | Category caps; hide unsized; partner price not billed; CCP dates; import category caps |
| `ClientBillingAndApplyRulesTest` | Hard-block over limit; individual-only; closing-day auto generate |
| `PlanBillingBasisTest` | Headcount × unit price; end-date exclude; dedupe; regenerate |
| `LoanLifecycleGuardTest` | Delete/retire guards; return qty; cancel inventory |
| `ApplicationLifecycleFixTest` | Return/transfer/cancel lifecycle |
| `BaseDataImportTest` | 260805 import + institution codes |

```bash
php artisan test
```

---

## Client reference docs (`clientdocs/`)

| Folder / file | What it is |
|---------------|------------|
| `202601.こやまクリニック_請求金額・人数/` | Monthly plan × headcount billing (closest to implemented model) |
| `202607在宅ほくと_請求集計/` | Same model + site breakdown |
| `2603.松田病院_明細/` | Item + piece-tier layout (reference only; not app billing engine) |
| `260805.ベースデータ.xlsx` | Latest client base master data |
| `マスタ資料_テーブル一覧確認用.xlsx` | Earlier master table sketch |
| `受注伝票_20260626_1396466.xlsx` | Order / shipping form sample |
| `Uniform_Management_System_Specification_20260731_EN.md` | English translation of the spec |

Load base data (stores **per-category caps** + `slot_category`; does **not** wipe existing `contract_count` on re-import):

```bash
php artisan masters:import-base-data --dry-run
php artisan masters:import-base-data
```

---

## Quick reset

```bash
php artisan migrate:fresh --seed
# then start again at Phase 0 / 1
```

---

## One-line flow

```text
Admin: Institution(+hospital login, 締め日, codes) → Maker → Products(size/F) → Plan(category caps + 単価 + partner field) → Confirm CCP(dates)
Hospital: Staff(+兼務) + assign plan → Individual apply (hard-block over category cap)
Ops: Accept + PDFs → Ship (完了)
Accounting: Billing Excel / auto 締め日 (割当人数 × 単価税抜; 口数=参考; tax external)
```
