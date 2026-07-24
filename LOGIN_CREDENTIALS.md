# 🔐 Uniform Management System - Login Credentials

## System Overview
This document contains login credentials for all 6 user roles in the Uniform Management System.

---

## 👥 User Roles & Credentials

### 1. 🏥 Medical Institution Overall Administrator (医療機関全体管理者)
**Email:** `hospital-admin@example.com`  
**Password:** `password123`  
**Role:** `institution_admin`  
**Access:** 
- All departments and employees within the medical institution
- All applications and history
- Employee management (create, edit, CSV import/export)
- Contract viewing
- Department and job type management

---

### 2. 🏢 Department Administrator (部署管理者)
**Email:** `department-admin@example.com`  
**Password:** `password123`  
**Role:** `department_admin`  
**Access:**
- Own department only
- Own department's employees
- Own department's applications
- Employee management for own department
- CSV import/export for own department

---

### 3. ⚙️ System Administrator (システム管理者)
**Email:** `kamatani-admin@example.com`  
**Password:** `password123`  
**Role:** `system_admin`  
**Access:**
- Full system access
- Medical institution master data management
- Department and job type management
- Delivery location management
- Contract management (create, edit, delete)
- Application approval
- Uniform product management
- Uniform recommendations
- Billing data generation

---

### 4. 📋 Operations Administrator (運営管理者)
**Email:** `operations-admin@example.com`  
**Password:** `password123`  
**Role:** `operation`  
**Access:**
- Application management and approval
- Uniform product management
- Uniform recommendations
- Contract management (view and edit)
- Billing data access

---

### 5. 💼 Sales Administrator (営業管理者)
**Email:** `sales-admin@example.com`  
**Password:** `password123`  
**Role:** `sales`  
**Access:**
- Contract information (view-only)
- Usage statistics
- Contract viewing

---

### 6. 💰 Accounting Administrator (経理管理者)
**Email:** `accounting-admin@example.com`  
**Password:** `password123`  
**Role:** `accounting`  
**Access:**
- Billing data generation
- Historical billing reproduction
- Billing snapshot viewing
- Export billing data

---

## 📝 Notes

- All passwords are set to: `password123`
- All users are created during database seeding
- Medical Institution: "Test Hospital" (ID: 1)
- Department Admin is assigned to "内科" (Internal Medicine) department
- For security, change passwords in production environment

---

## 🚀 Quick Access

| Role | Email | Password |
|------|-------|----------|
| Institution Admin | `hospital-admin@example.com` | `password123` |
| Department Admin | `department-admin@example.com` | `password123` |
| System Admin | `kamatani-admin@example.com` | `password123` |
| Operations Admin | `operations-admin@example.com` | `password123` |
| Sales Admin | `sales-admin@example.com` | `password123` |
| Accounting Admin | `accounting-admin@example.com` | `password123` |

---

**Generated:** {{ date('Y-m-d H:i:s') }}  
**System:** Uniform Management System v1.0


