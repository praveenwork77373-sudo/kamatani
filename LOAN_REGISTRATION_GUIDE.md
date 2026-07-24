# Loan Registration Guide

## Overview
Loans (EmployeeLoan records) are **NOT directly registered** by users. They are automatically created when uniform applications are approved by administrators.

## Loan Registration Flow

### Step 1: Create Application
**Route:** `/applications/create`  
**Who can do it:**
- Medical Institution Administrators (`institution_admin`)
- Department Administrators (`department_admin`)

**What happens:**
1. User selects employee(s) and uniform products
2. User specifies quantities and application type (new_loan, exchange, return, resize, transfer)
3. System checks:
   - Contract limits (per person loan limit)
   - Stock availability
4. Application is created with status:
   - `pending` - Normal application
   - `waiting_special_approval` - If limits exceeded and special approval reason provided

**Location in UI:**
- Menu: **Applications** → **New Application**
- Or: Click "新規ユニフォーム申請" button

### Step 2: Approve Application
**Route:** `/admin/applications/{id}/approve`  
**Who can do it:**
- System Administrators (`system_admin`)
- Operations Administrators (`operation`)

**What happens:**
1. Admin reviews the application
2. Admin clicks "承認" (Approve) button
3. System automatically:
   - Creates/updates `EmployeeLoan` records
   - Decrements stock quantities
   - Sets loan status to `in_use`
   - Links loan to application via `application_id`
   - Sets `loan_start_date` from application's `start_date`

**Location in UI:**
- Menu: **Admin** → **Applications**
- View application details and click "承認" button

### Step 3: Loan Record Created
When application is approved, the system automatically:
- Creates `EmployeeLoan` record(s) for each uniform product
- Sets `employee_id`, `uniform_product_id`, `quantity`
- Sets `status` to `in_use`
- Sets `loan_start_date` from application start date
- Links to application via `application_id`
- `loan_end_date` remains `NULL` (ongoing loan)

## Important Notes

1. **No Direct Loan Registration**: There is no separate "Register Loan" page. Loans are always created through the application approval process.

2. **Application Types**:
   - `new_loan` - Creates new loan records
   - `exchange` - Returns old + creates new loans
   - `return` - Updates loan status to `returned`
   - `resize` - Exchanges for different size
   - `transfer` - Transfers loan to another employee

3. **Loan Status**:
   - `in_use` - Active loan
   - `scheduled_return` - Scheduled for return
   - `returned` - Loan has been returned

4. **Where Loans Are Used**:
   - Billing calculations (count active loans)
   - Employee loan history
   - Stock management
   - Contract limit enforcement

## Summary

**To register a loan:**
1. Go to **Applications** → **New Application**
2. Select employee and uniforms
3. Submit application
4. Wait for admin approval
5. Loan is automatically created when approved

**To view existing loans:**
- Loans are tracked in the `employee_loans` table
- Used in billing calculations
- Can be viewed through employee details or billing reports


