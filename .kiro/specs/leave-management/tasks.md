# Implementation Plan: Leave Management

## Overview

Implement the Leave Management feature (Phase 4 of WorkForge SaaS) incrementally, building from the data layer up through services, controllers, policies, views, and seeder updates. Each task builds on the previous and ends with all components wired together.

## Tasks

- [x] 1. Create WorkingDaysHelper
  - Create `app/Helpers/WorkingDaysHelper.php` with a static `count(Carbon $start, Carbon $end): int` method that iterates from `$start` to `$end` inclusive and counts non-weekend days; returns 0 if `$start > $end`
  - _Requirements: 2.2, 7.3_

  - [ ]* 1.1 Write property test for WorkingDaysHelper::count()
    - **Property 9: Working days calculation**
    - **Validates: Requirements 2.2, 7.3**
    - Generate random date pairs where `start <= end`; assert result equals manual weekday count
    - Also test all-weekend ranges return 0, single weekday returns 1, cross-month ranges

- [x] 2. Create leave_types migration and LeaveType model
  - Create migration `create_leave_types_table` with columns: `id`, `org_id` (FK → organizations, cascadeOnDelete), `name` (string), `max_days` (unsignedInteger), `timestamps`; add unique index on `[org_id, name]` and index on `org_id`
  - Create `app/Models/LeaveType.php` with `#[Fillable]`, `OrgScope` in `booted()`, and `leaveRequests(): HasMany` relationship
  - _Requirements: 1.1, 1.2, 7.1, 7.4_

  - [ ]* 2.1 Write property test for LeaveType OrgScope isolation
    - **Property 1: OrgScope isolation (leave_types)**
    - **Validates: Requirements 1.1, 7.1**
    - Create two orgs with leave types; query as user from org A; assert no org B records returned

  - [ ]* 2.2 Write property test for leave type create round-trip
    - **Property 2: Leave type create round-trip**
    - **Validates: Requirements 1.2**
    - Generate random valid name + positive `max_days`; create; read back; assert name, `max_days`, and `org_id` match

- [x] 3. Create leave_requests migration and LeaveRequest model
  - Create migration `create_leave_requests_table` with all columns per design (including `status` enum defaulting to `pending`, nullable `reviewed_by`, `reviewed_at`, `rejection_reason`); add indexes on `org_id`, `[org_id, status]`, `employee_id`, `leave_type_id`
  - Create `app/Models/LeaveRequest.php` with `#[Fillable]`, `OrgScope` in `booted()`, `casts()` for dates, and `employee()`, `leaveType()`, `reviewer()` relationships
  - _Requirements: 2.1, 3.1, 3.2, 7.1, 7.4, 7.5_

  - [ ]* 3.1 Write property test for LeaveRequest OrgScope isolation
    - **Property 1: OrgScope isolation (leave_requests)**
    - **Validates: Requirements 3.5, 5.6, 7.1**
    - Create two orgs with leave requests; query as user from org A; assert no org B records returned

  - [ ]* 3.2 Write property test for status enum enforcement
    - **Property 22: Status enum enforcement**
    - **Validates: Requirements 7.5**
    - Attempt to persist `LeaveRequest` with invalid status values; assert rejection

- [x] 4. Create leave_balances migration and LeaveBalance model
  - Create migration `create_leave_balances_table` with columns: `id`, `org_id`, `employee_id`, `leave_type_id`, `year` (unsignedSmallInteger), `used_days` (unsignedInteger, default 0), `timestamps`; add unique constraint on `[org_id, employee_id, leave_type_id, year]` named `leave_balances_unique`; add indexes on `org_id` and `employee_id`
  - Create `app/Models/LeaveBalance.php` with `#[Fillable]`, `OrgScope` in `booted()`, and `employee()`, `leaveType()` relationships
  - _Requirements: 4.3, 4.4, 7.1_

  - [ ]* 4.1 Write property test for LeaveBalance OrgScope isolation
    - **Property 1: OrgScope isolation (leave_balances)**
    - **Validates: Requirements 6.5, 7.1**
    - Create two orgs with balance records; query as user from org A; assert no org B records returned

  - [ ]* 4.2 Write property test for balance initialization at zero
    - **Property 18: Balance initialization at zero**
    - **Validates: Requirements 4.3**
    - Generate new `(employee_id, leave_type_id, year)` combos with no existing record; call `getOrInit`; assert `used_days = 0`

- [x] 5. Create LeaveBalanceService
  - Create `app/Services/LeaveBalanceService.php` implementing `getOrInit()`, `increment()`, `decrement()` (clamped to 0), and `hasSufficientBalance()` methods as specified in the design
  - _Requirements: 4.1, 4.2, 4.3, 4.5_

  - [ ]* 5.1 Write property test for balance increment on approval
    - **Property 16: Balance increment on approval**
    - **Validates: Requirements 4.1**
    - Generate a balance with known `used_days`; call `increment(N)`; assert `used_days` increased by exactly `N`

  - [ ]* 5.2 Write property test for balance decrement on reversal
    - **Property 17: Balance decrement on reversal**
    - **Validates: Requirements 4.2**
    - Generate a balance; increment by N; decrement by N; assert `used_days` returns to original value

  - [ ]* 5.3 Write property test for available days invariant
    - **Property 19: Available days invariant**
    - **Validates: Requirements 4.5**
    - Generate balance records with known `used_days` and `max_days`; assert `hasSufficientBalance` correctly reflects `max_days - used_days`

- [x] 6. Create StoreLeaveTypeRequest and UpdateLeaveTypeRequest
  - Create `app/Http/Requests/StoreLeaveTypeRequest.php`: authorize with `manage-employees` permission; validate `name` (required, string, max:100, unique per org) and `max_days` (required, integer, min:1)
  - Create `app/Http/Requests/UpdateLeaveTypeRequest.php`: same rules as Store but with `ignore` on the current record for the unique rule
  - _Requirements: 1.2, 1.3, 1.6_

  - [ ]* 6.1 Write property test for leave type validation
    - **Property 6: Leave type validation rejects invalid input**
    - **Validates: Requirements 1.6**
    - Generate blank names and non-positive `max_days` values; assert validation error returned and no record created

- [x] 7. Create StoreLeaveRequestRequest
  - Create `app/Http/Requests/StoreLeaveRequestRequest.php`: authorize with `apply-leave` permission; validate `leave_type_id` (required, integer, exists:leave_types,id), `start_date` (required, date), `end_date` (required, date, gte:start_date), `reason` (required, string, max:500)
  - _Requirements: 2.1, 2.3, 2.5_

  - [ ]* 7.1 Write property test for date range validation
    - **Property 10: Date range validation**
    - **Validates: Requirements 2.3**
    - Generate date pairs where `start_date > end_date`; assert validation error returned and no `LeaveRequest` created

- [x] 8. Create LeaveTypeController
  - Create `app/Http/Controllers/LeaveTypeController.php` with `index`, `create`, `store`, `edit`, `update`, `destroy` methods
  - In `destroy`: guard against deletion if `$leaveType->leaveRequests()->exists()` — redirect back with named error `leave_type`
  - Apply `auth`, `verified`, `permission:manage-employees` middleware
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.7_

  - [ ]* 8.1 Write feature test for LeaveTypeController CRUD
    - Test create, update, delete HTTP flows with valid data
    - Test 403 for users without `manage-employees` permission
    - Test delete blocked when leave requests exist (Property 5)
    - **Property 4: Leave type delete when no requests — Validates: Requirements 1.4**
    - **Property 5: Leave type delete blocked when requests exist — Validates: Requirements 1.5**
    - **Property 7: Permission enforcement returns 403 — Validates: Requirements 1.7**

  - [ ]* 8.2 Write property test for leave type update round-trip
    - **Property 3: Leave type update round-trip**
    - **Validates: Requirements 1.3**
    - Generate existing leave type and random valid update data; update; read back; assert new values

- [x] 9. Create LeaveRequestController
  - Create `app/Http/Controllers/LeaveRequestController.php` with `index`, `create`, `store`, `approve`, `reject` methods
  - `index`: employees see only their own requests; managers/admins see all org requests
  - `store`: resolve auth employee, compute `total_days` via `WorkingDaysHelper`, check balance via `LeaveBalanceService::hasSufficientBalance()`, create with `status=pending`; reject with error if `total_days = 0` (all-weekend range)
  - `approve`: guard `status = pending`; update status, `reviewed_by`, `reviewed_at`; call `LeaveBalanceService::increment()`
  - `reject`: guard `status = pending`; update status, `reviewed_by`, `reviewed_at`, `rejection_reason`; if previous status was `approved`, call `LeaveBalanceService::decrement()`
  - _Requirements: 2.1, 2.2, 2.4, 2.6, 2.7, 3.1, 3.2, 3.3, 3.4, 3.5, 5.1, 5.2_

  - [ ]* 9.1 Write feature test for leave application flow
    - Test valid application creates pending request with correct `org_id` and `employee_id`
    - Test insufficient balance returns error and no record created
    - Test cross-org `leave_type_id` returns 404
    - **Property 8: Leave request create round-trip with pending status — Validates: Requirements 2.1**
    - **Property 11: Balance enforcement prevents over-limit requests — Validates: Requirements 2.4**
    - **Property 12: Cross-org references return 404 — Validates: Requirements 2.5**
    - **Property 13: Employee cannot apply on behalf of another — Validates: Requirements 2.7**

  - [ ]* 9.2 Write feature test for approve/reject flow
    - Test approve sets correct fields and increments balance
    - Test reject sets correct fields including `rejection_reason`
    - Test action on non-pending request returns error
    - Test 403 for users without `approve-leave` permission
    - **Property 14: Review sets correct fields — Validates: Requirements 3.1, 3.2**
    - **Property 15: Non-pending requests cannot be actioned — Validates: Requirements 3.3**
    - **Property 16: Balance increment on approval — Validates: Requirements 4.1**
    - **Property 17: Balance decrement on reversal — Validates: Requirements 4.2**

- [x] 10. Create LeavePolicy
  - Create `app/Policies/LeavePolicy.php` with `apply(User $user): bool` (checks `apply-leave` permission) and `approve(User $user): bool` (checks `approve-leave` permission)
  - Register the policy in `AppServiceProvider` or via `#[Policy]` attribute
  - _Requirements: 2.6, 3.4_

  - [ ]* 10.1 Write unit test for LeavePolicy
    - Test `apply()` returns true for users with `apply-leave`, false otherwise
    - Test `approve()` returns true for users with `approve-leave`, false otherwise
    - **Property 7: Permission enforcement returns 403 — Validates: Requirements 2.6, 3.4**

- [x] 11. Update routes/web.php with leave routes
  - Inside the existing `middleware(['auth', 'verified'])` group, add:
    - `permission:manage-employees` sub-group: `Route::resource('leave-types', LeaveTypeController::class)->except(['show'])`
    - Public: `Route::get('/leave-requests', ...)` named `leave-requests.index`
    - `permission:apply-leave` sub-group: `create` and `store` routes for leave requests
    - `permission:approve-leave` sub-group: `approve` and `reject` POST routes for leave requests
  - Add necessary `use` imports for `LeaveTypeController` and `LeaveRequestController`
  - _Requirements: 1.7, 2.6, 3.4_

- [x] 12. Create Blade views for leave types
  - Create `resources/views/leave-types/index.blade.php`: table listing all leave types with name, max_days, edit/delete buttons (visible to Admin only); delete uses a small inline form with POST + method spoofing
  - Create `resources/views/leave-types/create.blade.php`: form with `name` text input and `max_days` number input, validation error display using `<x-input-error>`
  - Create `resources/views/leave-types/edit.blade.php`: pre-filled form matching create layout with PUT method spoofing
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6_

- [x] 13. Create Blade views for leave requests
  - Create `resources/views/leave-requests/index.blade.php`: table with leave type name, start/end dates, total_days, reason, status badge using `<x-status-badge>`; show `rejection_reason` when status is rejected; show approve/reject inline form buttons for Manager/Admin on pending requests (reject form includes `rejection_reason` textarea)
  - Create `resources/views/leave-requests/create.blade.php`: leave type select dropdown with available balance hint per type, `start_date` and `end_date` date inputs, `reason` textarea, validation error display
  - _Requirements: 2.1, 3.1, 3.2, 5.3, 5.4, 5.5_

- [x] 14. Update DefaultDataSeeder to add default leave types
  - After the existing departments/designations seeding block, add a loop that calls `LeaveType::firstOrCreate()` for: Casual Leave (12 days), Sick Leave (10 days), Paid Leave (15 days), all scoped to `$org->id`
  - Add `LeaveType` to the `use` imports at the top of the seeder
  - Update the `$this->command->info()` message to include leave type count
  - _Requirements: 1.2_

- [x] 15. Update DashboardController::employee() to pass leave counts
  - Modify `DashboardController::employee()` to resolve the auth user's `Employee` record, query `LeaveRequest` grouped by status, and pass `$counts` to the `employee.dashboard` view
  - Update `resources/views/employee/dashboard.blade.php` (or `employee.dashboard`) to display pending, approved, and rejected leave counts using the `$counts` variable
  - _Requirements: 6.4, 6.5_

  - [ ]* 15.1 Write feature test for dashboard leave counts
    - **Property 21: Dashboard counts match actual request counts**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4**
    - Create org with known leave request counts per status; assert dashboard response contains correct counts for Admin/Manager and for Employee

- [x] 16. Checkpoint — Run migrations and verify integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 17. Write PHPUnit feature tests for leave management
  - Create `tests/Feature/LeaveTypeControllerTest.php`: test index, create, store, edit, update, destroy flows; assert 403 for unauthorized users; assert delete blocked when requests exist
  - Create `tests/Feature/LeaveRequestControllerTest.php`: test apply flow (valid, insufficient balance, cross-org 404, all-weekend dates); test approve/reject flows; test non-pending guard; test employee sees only own requests; test manager sees all org requests
  - Create `tests/Unit/WorkingDaysHelperTest.php`: test specific date examples, all-weekend range, single weekday, cross-month range
  - Create `tests/Unit/LeaveBalancePolicyTest.php`: test `apply()` and `approve()` with users of different roles
  - _Requirements: all_

- [x] 18. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- `OrgScope` is applied on all three models — cross-org isolation is automatic via Eloquent
- `DashboardController::leaveCount()` already queries `leave_requests` via `DB::table()`; it returns real data once the migration runs with no further changes needed for Admin/Manager dashboard
- Property tests require Eris: `composer require --dev giorgiosironi/eris`
