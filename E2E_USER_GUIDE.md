# Uniform System — Fresh-start E2E guide

**Goal:** Test the full product from an empty business DB (no mock hospital/products).  
**Seeded by default:** Kamatani admin logins only.  
**Local URL:** `http://127.0.0.1:8000`  
**Official billing model (confirmed):** plan unit price (ex-tax) × **number of people on that plan** as of closing day — **not** × number of uniforms / SKUs, **not** × 契約口数.

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
# Optional (emails + queued mail): MAIL_MAILER=log is fine locally
# php artisan queue:work
```

See also: `PRODUCTION.md` (go-live checklist), `README.md` (setup).

---

## Latest end-to-end flow (one view)

```text
[Kamatani setup]
  Institution (+ hospital admin login, 締め日)
  → Maker → Uniform products
  → Contract plan (単価税抜 + linked products + rentable points)
  → Customer contract plan (hospital ↔ plan, 契約数＝参考枠) → 本登録 (confirmed)

[Hospital]
  Staff (+ hire/resign dates, 主部署 + 兼務) → Assign confirmed plan
  → Apply uniform (申請 / 特別承認待ち)
      types: 新規貸与 / サイズ変更 / 返却 / 交換 / 異動（部分変更＝同一職員の旧品返却＋新品）
      ※ 異動 is NOT person-to-person handoff (no 移管先)

[Kamatani ops]
  Accept (受付) → status 対応中
  → PDFs: application detail OR bulk OR 受注一覧 (対応中/完了 only)
  → Ship (出荷登録) → status 完了

[Kamatani accounting]
  Billing → Generate as-of 締め日 (system_admin / accounting only)
  → Official amount = 割当人数 × 契約プラン単価（税抜）
  → 契約口数 shown as reference capacity only
  → Excel export (請求明細 + 個人明細)
```

Statuses (applications):

| Status (JA) | Meaning |
|-------------|---------|
| 申請 | Submitted by hospital |
| 特別承認待ち | Over rentable points / stock — waiting ops special approval |
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
| **Hospital** | `/login` | Staff, assign plans, apply, cancel request |

**Important rules (from SPEC):**

- Contract plans belong to the **hospital**, then are assigned to **employees** — not to departments.
- Products apply only if linked under the employee’s assigned plan.
- Hospital cannot set desired delivery date; use hire date / return-to-work date.
- Cleaning needs no application.
- Immediate cancel/delete is not allowed; cancel request → Kamatani approval.
- Billing is data/export only (no invoice payment / ERP inside the app).
- Staff may have **multiple departments (兼務)**; department admins see affiliates (primary **or** concurrent).

---

## Master codes (auto + uniqueness)

On create/edit for institution, department, job type, maker, contract plan, and staff number:

| Behavior | Expected |
|----------|----------|
| Leave code **empty** | Unique code auto-generated on save (`MI-`, `DEP-`, `JOB-`, `SUP-`, `PLN-`, `EMP-` prefixes) |
| Type a code, then **blur** (leave the field) | Live check: available (green) or already taken (amber — clear or change) |

**Pass if:** Empty save creates a code; duplicate manual code shows warning and server rejects if forced.

**Note (staff create):** Hospital employee create may still auto-assign `EMP-…` even if a manual staff no is typed (server generator). Prefer empty for auto, or edit after create if you need a fixed number.

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
- **Code** — optional (auto if empty)  
- Notification email  
- **締め日** (billing closing day, e.g. `1`) — drives default billing as-of date  
- Sales person (optional)  
- **Manager name / email / password** ← hospital institution admin  

Price for billing lives on **Contract Plan → 単価（税抜）**, not on the hospital.

**Pass if:** Institution appears; you can log in at `/login` with the manager.

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
Link products on **Contract Plan** (next) — not on the product screen as the application filter.

**Pass if:** Products listed with stock.

---

### 5. Contract plan (plan master)

**Menu:** Contract plans → New  
- Code / name / rentable points / **unit price ex-tax**  
- Check 適用可能ユニフォーム (products allowed for this plan)

**Pass if:** Plan shows linked products and unit price.

---

### 6. Customer contract plan (hospital ↔ plan)

**Menu:** Customer contract plans → New  
- Hospital, plan, **契約数** (contracted slots = **reference capacity**, not invoice multiplier), start/end  
- Confirm → **本登録** (status `confirmed`)

Provisional (仮登録) plans cannot be assigned for applications / billing until confirmed.

**Pass if:** Status confirmed. Form helper text: 請求 = 割当人数 × 単価.

**Ops note (SPEC):** Hospital does not increase plans/slots in the app. They contact Kamatani; admin adds/updates CCP.

---

## Phase 2 — Hospital configuration

Login: hospital manager from step 1 → `/login`

### 7. Create staff + assign plan

**Menu:** Employees → Add  
- Staff no (optional / auto)  
- Hire date / resign date (used by billing as-of logic)  
- Sizes, **主部署**, **兼務** rows (部署 + 職種) + optional per-position plans  
- **Assign confirmed CCP** (employee-wide and/or per affiliation; required before apply)

Staff may belong to **multiple departments**. Department admins see / can apply for anyone whose primary **or** concurrent post is in their department.

**CSV:** Export is multi-row (`職種`, `主所属` Y/N + plan codes). Older single-row CSV still imports.

**Pass if:** Apply search by staff no shows plan name and plan products; list shows `主: … · 兼務: …`; dept-B admin can see a staff whose primary is dept-A but has 兼務 in B.

### 8. (Optional) Department admin

Create under hospital masters; test affiliation visibility (not only primary isolation).

---

## Phase 3 — Core E2E (happy path)

| Step | Who | Login | UI | Action | Pass if |
|------|-----|-------|----|--------|---------|
| 9 | Hospital | institution admin | Applications → New | 新規貸与, plan products only, hire/return date, delivery, submit | Status **申請** |
| 9b | Hospital | same | Dashboard → 部門一括 | Opens `?mode=departmental` | Mode switches to departmental; apps stamp selected dept |
| 10 | Ops | `operations-admin@…` | Admin Applications | **受付** | **対応中**; can issue **5 PDFs** (order / delivery notice / ship sales / ship warehouse / work) |
| 10b | Ops | same | **受注一覧** | Multi-select bulk PDF | Same 5 docs for 対応中/完了 |
| 11 | Ops | same | App detail | **出荷登録（完了）** | **完了**; detail shows 新品/旧品区分 |
| 12 | Accounting | `accounting-admin@…` | Billing → Generate | Hospital + as-of (default = 締め日) → export | Official ≈ **割当人数 × 単価（税抜）**; 口数 reference only |

### Billing check detail (step 12)

Engine: `PlanBillingService`

```text
As-of date = institution 締め日 (or override)
Include staff only if:
  - employed as of that date (join/retire + status history)
  - has active employee↔plan assignment
  - hospital CCP is confirmed and within start/end
Official amount = people_on_plan × plan.unit_price_ex_tax
  (NOT × uniform piece count; NOT × contracted slot capacity)
契約口数 (contracted slots) = reference capacity only, not the invoice total
```

Excel sheets roughly match client samples (Koyama style): summary + person detail.

---

## Phase 4 — Role UI checks

| Role | Login | Must see | Must not (menu / write) |
|------|-------|----------|-------------------------|
| system_admin | kamatani-admin | All admin menus | — |
| operation | operations-admin | Apps, 受注一覧, products, recommendations, billing **view** | Plan/CCP/maker write, billing generate |
| sales | sales-admin | CCP read, billing **view** | Apps accept, CCP/plan write buttons, billing generate |
| accounting | accounting-admin | Billing generate + export | Apps accept, plan masters |
| contract_plan_manager | plan-manager | Makers, plans, CCP write | Billing, accept apps, products |
| institution_admin | (step 1) | Apps, employees, masters, view contract plans | Accept/ship/billing |
| department_admin | (step 8) | Affiliated apps/employees (主 or 兼務) | Institution masters / contract setup |

---

## Phase 5 — Extra checks

| # | Feature | Pass if |
|---|---------|---------|
| 13 | Special approval over rentable points | Status 特別承認待ち; reason **required** in UI; ops can accept |
| 14 | Cancel before ship | Ops approve cancel |
| 15 | Cancel after complete | Button disabled |
| 16 | Staff without plan | Apply blocked / empty catalog message |
| 17 | Outside-plan product | Not selectable on apply |
| 18 | Join date after as-of | Excluded from bill |
| 19 | Resign on/before as-of | Excluded from bill |
| 20 | Unconfirmed CCP | Not billable / not assignable for apply |
| 21 | 異動（部分変更） | Same employee: **return_items** (旧品) + **items** (新品); no 移管先; show lists 旧品/新品 |
| 22 | Bulk PDF / 受注一覧 | Multi-select issue works for 対応中/完了 |
| 23–24 | Hospital / admin employee CSV | Export/import works (multi-row 兼務 + plan fields; legacy single-row OK) |
| 25 | Plan manager path | Plans OK; no billing |
| 26 | Per-position / concurrent plan | Assignments saved; dept admin edit does not wipe other 兼務 |
| 27 | Auto code | Code assigned; duplicate warns |
| 28 | List pagination | 10/20/30/50 (default 10) |
| 29 | Welcome email | Send + history (log driver OK locally; queue worker recommended) |
| 30 | Recommended uniforms | Admin CRUD; apply shows hints only (plan still gates eligibility) |
| 31 | Admin users / notifications | `receives_app_notifications` on admin user form; app emails respect flag |
| 32 | DB backups | system_admin menu create/download (PRODUCTION checklist) |
| 33 | Lockout | 5 fails → 15 min lock + mail (config: `AUTH_LOCKOUT_*`) |

---

## Phase 6 — What NOT to expect

- Seeded hospital users from `DatabaseSeeder` alone (create manager on institution form)  
- Invoice / payment / ERP import (export only)  
- Billing from loan piece counts or product SKUs  
- Billing = 契約口数 × 単価 (口数 is reference only)  
- Matsuda-style item + piece-tier billing (`K-JJ-5` etc.) — **not** implemented; official model is plan × assigned headcount  
- Hospital increasing contract slots themselves (Kamatani updates CCP)  
- Desired delivery date from hospital  
- Cleaning application  
- Immediate delete of an application from hospital side  
- Person-to-person 移管 (removed; use 異動 = same-employee partial change)  

---

## Frontend ↔ backend mapping (smoke)

| Flow | Form / UI | Backend expects | OK if |
|------|-----------|-----------------|-------|
| Individual apply | `employee_id`, `items[]`, dates, delivery | `ApplicationController@store` → individual | App created for selected staff |
| Departmental apply | `application_mode=departmental`, `department_id` | `storeDepartmental`; apps use **selected** dept | All affiliated employed staff with plan |
| 異動 | loan checkboxes → `return_items[]`; cart → `items[]` | `exchange_out` + `loan`; `transfer_to=null` | Detail shows 旧品 + 新品 |
| Special approval | reason when over points/stock | `waiting_special_approval` | Ops 受付 works |
| Billing | Generate as-of | `assigned_count × unit_price` | Matches headcount, not 口数 |

Automated coverage: `php artisan test` (application lifecycle, multi-affiliation, billing basis, auth lockout).

---

## Client reference docs (`clientdocs/`)

| Folder / file | What it is |
|---------------|------------|
| `202601.こやまクリニック_請求金額・人数/` | Monthly plan × headcount billing (closest to implemented model) |
| `202607在宅ほくと_請求集計/` | Same model + site breakdown |
| `2603.松田病院_明細/` | Item + piece-tier layout (reference only; not app billing engine) |
| `260805.ベースデータ.xlsx` | Latest client base master data (hospitals, plans, products, suppliers) |
| `マスタ資料_テーブル一覧確認用.xlsx` | Earlier master table sketch |
| `受注伝票_20260626_1396466.xlsx` | Order / shipping form sample (PDF targets) |
| `ユニフォーム管理システム_仕様書_改定版_20260731 (2).docx` | Official JP spec |
| `Uniform_Management_System_Specification_20260731_EN.md` | English translation of the spec |

Load the latest base data (best-fit; category slot caps flattened to one `rentable_points`):

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
Admin: Institution(+hospital login, 締め日) → Maker → Products → (Unit prices) → Plan(単価) → Confirm CCP
Hospital: Staff(+兼務) + assign plan → Apply (申請)
Ops: Accept + PDFs / 受注一覧 (対応中) → Ship (完了)
Accounting: Billing Excel (割当人数 × 単価税抜; 口数=参考)
```
