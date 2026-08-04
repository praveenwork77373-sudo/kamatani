# Uniform Management System — Specification

> English translation of: `ユニフォーム管理システム_仕様書_改定版_20260731 (2).docx`  
> Source date: 2026-07-31 (revised edition)

---

## 1. System Overview

This system is a web-based business application that digitizes employee uniform loan operations for medical institutions (hospitals, clinics, facilities, etc.), and centralizes order, exchange, return, billing, and inventory management performed by Kamata ni.

Contract plans define the number of rental uniforms. A customer (medical institution) may contract multiple contract plans. The Institution-wide Admin role or the Department Admin role assigns contracted plans to each employee. Plan assignment is **not required** at employee registration time; later assignment is allowed. However, whether a uniform application is allowed is judged by the contract plan assigned to the employee **at application time**. The system must be able to list, as of any given date, which contract plan each employed staff member is on, and how many contracts exist per plan.

**[Important]** Contract plans are **not** directly tied to departments. They are first tied to the customer (medical institution). The customer then assigns contracted plans to employees. Both Institution-wide Admin and Department Admin can perform this assignment. Uniform application eligibility is judged by the plan the customer assigned to the employee.

**Key policy:** In this system, the contract plan (contract information) is treated as the top-level reference master. The contract plan defines:

- Rentable uniform points/pieces
- Products that can be applied for
- Each employee’s contract state
- Billing amount

Uniform products are registered to **contract plans**, not departments. Even if a product exists in the product master, if it is not linked under a contract plan, it is **not** treated as an application target.

---

## 2. Users, Roles, and Authentication

### 2.1 Customer-side roles (medical institution users)

Customer (medical institution) roles are the following two types.

#### (1) Institution-wide Admin role

**Scope:** All employees and all departments within that medical institution

**Main permissions:**

- Add / edit employees; set leave of absence / resignation / return to work
- Employee CSV bulk register / bulk update
- Job type management and assignment
- Assign / change contract plans for all employees (including later registration)
- Uniform applications for all departments (individual / department batch)
- Add delivery location (delivery place) master records
- View contract status and limit information
- View all application history within the institution

#### (2) Department Admin role

**Scope:** Only employees belonging to their own department

**Main permissions:**

- Edit basic info for department employees (including top / pants / shoe sizes)
- Add / edit department employees; set leave / resignation / return to work
- Department employee CSV bulk register / bulk update
- Assign / change contract plans for department employees (including later registration)
- Uniform applications for department employees (individual / department batch)
- View application history linked to their department

### 2.2 Admin roles (Kamata ni side)

Kamata ni internal admin roles are managed in the `admin_users` master and must support at least the following categories:

| Role | Responsibilities |
|------|------------------|
| System Admin | Access to all functions, master editing, other admin account management |
| Operations staff | Application review / approve / reject (including special approval and cancel approval), order / inventory coordination |
| Sales staff | View contract information and usage status |
| Contract plan management | Register / edit / view contract plan master, customer contract plans, contract counts, available uniforms |
| Accounting staff | Generate and confirm billing data (including export of past as-of-date data) |

### 2.3 Login, password reminder, lockout

#### (1) Login

- Both customer and admin sides authenticate with login ID (email, etc.) + password.
- Passwords are stored hashed.

#### (1-2) Medical institution go-live invitation email

After a medical institution master record is registered, Kamata ni must be able to send a go-live invitation email to the registered contact from the institution list screen.

The email body must include:

- Login URL
- Login ID
- Initial password **or** first-time password setup URL

If an initial password is sent, treat it as temporary and require change on first login.

Send history must record: send datetime, sender, destination email, send status — viewable from the institution list or detail screen.

#### (2) Password reminder (password reissue)

- Place a “Forgot password” link on the login screen.
- When the user enters their registered email, send a password reset URL by email.
- Reset URL uses a one-time token with expiry (e.g. 24 hours).
- Expired or used tokens are automatically invalidated.

#### (3) Lockout after repeated login failures

- If the same account fails login continuously a set number of times (e.g. 5), temporarily lock the account.
- Lock duration guideline: e.g. 15 minutes (adjustable when specs are finalized).
- On lockout, sending a “temporary account lock” notice email to the registered address is recommended.

**Unlock methods:**

- Auto-unlock after lock time elapses
- Or manual unlock from admin screen (System Admin role only)

---

## 3. Medical Institution Side (Customer Portal) Features

### 3.1 Employee management

**Main features:**

- Employee list display and search
- Staff number search is the primary path; name search is secondary (prefer exact staff-number match to handle same-name cases)
- Add / edit employees; change employment status (employed / leave / resigned). At registration, hire date and resign date are registration targets. Contract plan is **not** required at registration (may be undecided); Institution-wide or Department Admin can assign later per employee
- Register / edit: top/pants sizes, shoe size, gender, pants hem / inseam (cm dropdown), body type info (e.g. size 7, 11), hire date, resign date
- Employee CSV bulk register / update (**Institution-wide Admin only**)
- Staff number is the primary key identifying an employee; also used for search/selection during uniform application

The customer (medical institution), via Institution-wide or Department Admin, registers an applicable contract plan per employee from the plans assigned to the institution. Plan selection is not required at staff registration and may be done later. However, **at uniform application time**, plan assignment to the target employee is required. The employee’s application scope and rentable points are determined by products and point limits linked to that plan.

Employee CSV bulk register/update must also import: hire date, resign date, pants hem/inseam, body type info, and applicable contract plan.

#### Extension: job type / concurrent posts

- **Job type setting:** Add a “job type” field to employee info.
- **Concurrent posts:** Support one employee having multiple departments / job types.
- The system must allow linking multiple affiliation records and managing uniform sets (loan conditions) per role. When loan conditions differ by affiliation/role, a different contract plan may be linked per affiliation or role.

#### By role

- Institution-wide Admin: can perform the above for all employees
- Department Admin: only for employees in their own department

### 3.2 Uniform application (individual / department batch)

#### Application types

> Note: Cleaning does **not** require an application; pickup occurs at a designated date/time and place.

- New loan
- Size change
- Return
- Exchange (return + new loan)
- Transfer (partial change): e.g. on department transfer, “change jacket only, keep pants”

#### Required application data elements

When processing an application, the system must bind and process:

| Element | Description |
|---------|-------------|
| Application type | New / change / return / exchange / transfer, etc. |
| Employee ID / staff number | Identifies the target employee; staff-number search is the primary path |
| Uniform info | Product type, size, etc. |
| Application quantity | Based on rentable point limit of the employee’s assigned contract plan |
| Date designation | “Hire date” or “return-to-work date” |
| Delivery location | Designated delivery place info |

#### Application flow and input rules

**Employee search / selection**  
On application, staff-number search is the main path. Entering/selecting staff number shows: name, affiliation, job type, registered sizes, inseam, body type, current loan status, applicable contract info.

**Application control based on contract info**  
Applicable uniforms are limited to those linked under the employee’s assigned contract plan. Products are registered to plans (not departments), so products not tied to a department may still be applicable if included in the plan. Products not linked to the plan must **not** appear on the application screen even if they exist in the product master. Applications exceeding rentable points in the plan are treated as over-limit.

**Photo-based auto display (visual selection)**  
On the application screen, filter and show loan-target uniforms included in the employee’s plan (with product images). On transfer, products not tied to the current registered department may still be referenced/selected if they are within the plan.

**Application unit / defaults**

- Applications are per employee (individual) or per department (batch)
- Show as defaults: registered sizes, pants hem/inseam, body type, hire date, resign date, and assigned contract plan from the employee master

**Delivery / delivery info**

- **Date:** Hospital-side “desired delivery date” is **not allowed**. “Hire date” or “return-to-work date” input is required; Kamata ni arranges based on work start date
- **Delivery location:** Select delivery destination from dropdown (e.g. 2F office, 3F nurse station). On transfer applications, make it easy to select registered locations other than the default (search, filter, frequently used locations on top, etc.)
- **Adding locations:** If not in the dropdown, Institution-wide Admin can add a new delivery location

**Over-limit judgment and behavior**  
If an application exceeds rentable point limits or inventory settings defined by the plan, show an error (warning) and guide the user to either:

1. “Re-enter input”
2. “Request special approval” (enter reason and continue)

Application status has three basic stages:

1. Applied
2. In progress (internal only)
3. Completed

When Kamata ni reviews the application and issues internal PDF documents, status changes to “In progress (internal only)”. After off-system processing and shipment completion, status changes to “Completed”.

**Application cancel**

- Immediate cancel (data delete) from the hospital side is **not allowed**
- Pressing “Cancel application” notifies Kamata ni for approval
- Cancel is completed when Kamata ni approves
- After delivery completion, the button is disabled (not allowed)

### 3.3 Customer application email notifications

The following customer-side application actions notify the admin side by email at registration time:

- New / change / return / exchange / transfer applications
- Special approval requests
- Cancel applications

Destination email addresses are configurable in the admin master and medical institution master.

---

## 4. Kamata ni Side (Admin) Features

### 4.1 Master management / user management

- Admin master (`admin_users`) management
- Medical institution master management
  - Manage billing closing day; default is closing on the 1st of each month; changeable per institution
  - Add go-live invitation email send from the institution list (login URL, login ID, initial password or first-setup URL)
- Per customer (institution), select multiple plans from the contract plan master and contract them. Customer contract plans store: plan, contract count, start date, end date, contract status. New contracts are provisional at input time and switched to confirmed from the institution list or contract list. On confirmation, send a registration-complete notice email
- Customer contract plans are the basis for: staff plan assignment, rental application eligibility, contract-count aggregation as of any date, and billing output
- Department / job type master management: define departments and job types per institution. Loan-target uniform linking is managed by **contract plan**, not department
- Delivery location master management
- Employee info view / search / management
  - Staff-number search as primary path; name search also available (same-name handling)
- Employee CSV bulk management
- Unlock locked user accounts
- Maker master management: register / edit / deactivate maker name and maker code
- Contract plan master management: manage contract name, contract code, rentable points, contract amount (ex-tax), and available uniforms for that contract. Treat plan as parent and uniform info as child; link available uniforms under the plan. Plans and uniforms are linked many-to-many via an intermediate table. The plan defines price and rental uniform count
- Customer contract plan setup: register multiple plans and contract counts per customer; basis for billing calculation and employee plan assignment. The uniform range available to a customer is limited to uniforms linked under the customer’s contracted plans

**Operational rule for adding plans / increasing contract count:**  
Customers must **not** directly increase these in the system. They contact Kamata ni by phone/email, etc.; after contract confirmation, a Kamata ni admin adds/updates the plan for that customer in the system.

**Example:** If a customer is on a “5-piece uniform lease” plan and wants 2 more pieces, do **not** change the existing plan by department. Instead, add another plan covering the additional pieces. After Kamata ni adds it to the customer, the customer assigns it to target employees so they can apply for uniforms linked to that plan.

### 4.2 Application management / approval

- Status management via application list / detail screens. Basic statuses: Applied → In progress (internal only) → Completed
- **Application review:** Kamata ni reviews content and issues internal PDF documents: order slip, delivery schedule notice, shipping instructions, work slip, etc.
- **Completion:** After internal PDFs are issued, processing continues outside the system; at shipment, mark complete and set status to Completed

### 4.3 Billing data generation / management (mandatory requirement)

#### (1) Reproduce status as of a past date

Based on hire date / resign date in staff registration, determine employment status as of any date (currently mainly month start). Implement a function to list which contract plan each employed staff member is on at that time, and how many contracts exist per plan (**mandatory as billing evidence data**).

#### (2) Billing aggregation based on arbitrary as-of date and contract plans

- Billing aggregation must be exportable as of any date; current operations mainly use month start
- As-of date follows the institution master’s closing-day setting or a value specified at export time
- Billing amount is **not** calculated per uniform product. It is based on, as of the as-of date, the contract counts and contract amounts (ex-tax) of contract plans assigned to employed staff
- Output both: plan-level aggregation and staff-level contract list (so Kamata ni can calculate)

**Billing output fields must include:**

- As-of date
- Medical institution name
- Staff number
- Personal name
- Hire date
- Resign date
- Contract plan name
- Contract plan code
- Contract count
- Contract unit price (ex-tax)
- Amount (ex-tax)

### 4.4 Uniform product management

- Manage product info, size ranges, inventory, maker info (maker name, maker code)
- Recommended / designated uniform settings per department / job type (including photo registration)
- Uniform products are linked to makers; searchable/exportable by maker name and maker code
- Only products linked as available under a contract plan master are shown as application targets for that plan. The product master manages product attributes; application eligibility and rentable points are judged by linkage to the contract plan

### 4.5 Form PDF output / shipping instruction management

When a uniform application is registered, after Kamata ni reviews it, the system must be able to generate PDFs for order, delivery schedule, shipping instructions, and work use. After PDF issue, status becomes “In progress (internal only)”; after off-system processing and at shipment, status becomes “Completed”.

#### Target forms to generate

1. Order slip (受注伝票)
2. Delivery schedule notice (納入予定ご連絡書)
3. Shipping instruction — sales office (出荷指示書・営業所用)
4. Shipping instruction — warehouse (出荷指示書・倉庫用)
5. Work slip (作業用伝票)

#### Common print fields on each PDF

- Order date
- Delivery due date
- Medical institution
- Department
- Personal name
- Maker name
- Product code (uniform)
- Product name
- Size
- Inseam
- Body type info
- Quantity
- Memo 1
- Memo 2

Because the contract plan is the basis for application, billing, and shipping instructions, each form **must** show the contract plan name and that the uniform is linked to that plan.

#### Output / storage rules

- PDFs can be output individually or in bulk from application detail, application list, and order list screens
- Issued PDFs are stored linked to application / order data; reissue, download, and print must be possible
- On reissue, record reissue datetime, reissuer, and target form as history
- If application content is changed or cancelled, keep old form versions as history and make the latest version identifiable

---

## Billing strategy (as stated in this source document)

Per section 4.3, and confirmed for this product:

```text
As-of date (institution closing day, usually month start)
  → Staff employed on that date (by hire / resign dates)
  → Contract plan assigned to each employed staff member
  → Aggregate by contract plan (count people on each plan)
  → Amount (ex-tax) = plan unit price (ex-tax) × number of people on that plan
```

Important clarifications:

- Bill by **people on each contract plan**, **not** by uniform product SKU / piece count
- Contracted slot capacity (契約口数) may appear as reference only — it is **not** the official invoice multiplier
- Output both plan summary and per-staff contract list
- Past as-of-date reproduction is mandatory billing evidence
- Closing day defaults to the 1st of each month, configurable per institution

> Note: Client sample Excels for Matsuda Hospital use a different item + piece-tier layout. That variant is **not** the official model for this system.
