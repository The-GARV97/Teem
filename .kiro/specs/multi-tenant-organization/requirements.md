# 📄 WorkForge SaaS — Requirements Document

## 🧠 1. Product Overview

**Product Name:** WorkForge (working name)
**Type:** Multi-tenant SaaS platform
**Core Purpose:**
A Laravel-based SaaS platform that provides:

* Authentication & Organization Management
* Employee Management
* Leave Management
* Subscription & Billing System

**Target Users:**

* Small to Medium Businesses (SMBs)
* Startups
* Internal HR/Admin teams
* Developers (future API usage)

---

## 🏗️ 2. System Architecture

### Multi-Tenancy Model

* Single database
* All data scoped by `org_id`
* Strict isolation between organizations

### Core Principle

> Every record must belong to an organization

---

## 🧩 3. Core Modules

---

# 🔐 3.1 Authentication & Organization System

### Features:

* User Registration & Login
* Organization creation during registration
* Each user belongs to exactly one organization
* First user becomes **Admin**

### Functional Requirements:

* Add `company_name` field during registration
* Automatically create organization
* Assign `org_id` to user
* Role assignment (Admin, Manager, Employee)

### Constraints:

* No user without org_id
* Users cannot access other org data

---

# 🏢 3.2 Organization Management

### Features:

* Organization settings page:

  * Name
  * Logo upload
  * Timezone
* Invite users via email
* Role assignment per user

### Functional Requirements:

* Admin can:

  * Invite users
  * Manage roles
* Users accept invite via secure link

---

# 👨‍💼 3.3 Employee Management

### Features:

* Employee directory
* Employee profile
* Department & designation management
* Manager hierarchy

### Fields:

* Name
* Email
* Phone
* Designation
* Department
* Manager
* Joining Date
* Status (Active/Inactive)

### Functional Requirements:

* CRUD operations
* Filter by organization
* Assign manager (self-relation)

---

# 🏖️ 3.4 Leave Management System

### Features:

* Leave types (Casual, Sick, Paid)
* Leave application
* Approval workflow
* Leave balance tracking

### Leave Flow:

1. Employee applies leave
2. Manager/Admin reviews
3. Approve or Reject
4. Deduct balance if approved

### Functional Requirements:

* Prevent leave if insufficient balance
* Track leave history
* Support multiple leave types

---

# 🔐 3.5 Role & Permission System

### Roles:

* Admin
* Manager
* Employee

### Permissions:

* Manage employees (Admin)
* Approve leave (Manager/Admin)
* Apply leave (Employee)

### Requirements:

* Use role-based access control
* Protect routes via middleware
* Use Laravel policies

---

# 📊 3.6 Dashboard

### Admin Dashboard:

* Total employees
* Pending leaves
* Approved/rejected leaves

### Platform Dashboard (Super Admin future):

* Total organizations
* Total users
* Revenue

---

# 🔔 3.7 Notification System

### Events:

* Leave applied → notify manager
* Leave approved/rejected → notify employee
* User invited → email notification

### Channels:

* Database
* Email (optional)

---

# 💳 3.8 Subscription & Billing

### Plans:

* Free
* Pro
* Enterprise

### Plan Features:

* Max users
* Feature access
* Pricing

### Functional Requirements:

* Each org has 1 active subscription
* Default = Free plan
* Enforce plan limits (e.g., max employees)

---

# 💰 3.9 Payment Integration

### Provider:

* Stripe (Laravel Cashier)

### Features:

* Upgrade plan
* Downgrade plan
* Cancel subscription

### Requirements:

* Store Stripe customer ID
* Handle webhooks
* Track payment status

---

# 🔐 3.10 Security & Audit Logs

### Features:

* Audit logs:

  * User login
  * Employee creation
  * Leave approval
* Login tracking (IP, time)

### Requirements:

* Store logs per org
* View logs in dashboard

---

# 🌐 3.11 API System

### Features:

* REST API for:

  * Employees
  * Leave management

### Requirements:

* Use Laravel Sanctum
* Token-based authentication
* Org-scoped API access

---

# 🧱 4. Database Design (High-Level)

### Core Tables:

* organizations
* users
* plans
* subscriptions

### Employee:

* employees
* departments
* designations

### Leave:

* leave_types
* leave_requests
* leave_balances

### System:

* audit_logs
* notifications

---

# ⚙️ 5. Technical Requirements

### Backend:

* Laravel 13
* Blade (UI)
* Tailwind CSS

### Packages:

* laravel/breeze (auth)
* spatie/laravel-permission (RBAC)
* laravel/cashier (billing)
* sanctum (API auth)

---

# 🧠 6. Non-Functional Requirements

### Performance:

* Fast query execution
* Indexed org_id

### Security:

* Strict org isolation
* CSRF protection
* Validation on all inputs

### Scalability:

* Modular architecture
* Service layer for business logic

---

# 🎯 7. MVP Scope

### Phase 1:

* Auth + Org system

### Phase 2:

* Employee management

### Phase 3:

* Leave system

### Phase 4:

* Basic dashboard

---

# 🚀 8. Future Enhancements

* Multi-org switching
* Advanced analytics
* AI-based insights
* Mobile app
* Attendance system
* Payroll (optional)

---

# ⚠️ 9. Constraints & Assumptions

* Single database multi-tenancy
* No cross-org data access
* Initial focus on MVP (no over-engineering)

---

# 🧭 10. Success Criteria

* User can:

  * Register and create org
  * Add employees
  * Apply and approve leaves
* System enforces org isolation
* UI is clean and usable
* Ready for SaaS billing integration

---

# 🏁 11. Development Guidelines

* Follow Laravel best practices
* Keep controllers thin
* Use services for business logic
* Always filter by org_id
* Write reusable components

---

# ✅ FINAL NOTE

This document defines the full scope of a **production-ready SaaS HR + Auth platform**.

AI IDE (Kiro) should:

* Follow modular development
* Generate clean, maintainable Laravel code
* Avoid over-complication
* Ensure security and scalability
