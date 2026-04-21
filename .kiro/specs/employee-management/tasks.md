# Implementation Plan: Employee Management

## Overview

Implement the org-scoped Employee Management module: three new database tables (departments, designations, employees), Eloquent models with a shared `OrgScope` global scope, resource controllers gated by `manage-employees`, an `EmployeePolicy`, Blade views using the existing `x-app-layout` and indigo design system, and a full PHPUnit + Eris test suite.

## Tasks

- [x] 1. Create `OrgScope` global scope class
  - Create `app/Models/Scopes/OrgScope.php` implementing `Illuminate\Database\Eloquent\Scope`
  - In `apply()`, add `WHERE {table}.org_id = auth()->user()->org_id` when a user is authenticated
  - _Requirements: 4.1, 4.2, 4.3_

- [x] 2. Create departments migration and `Department` model
  - [x] 2.1 Create migration `create_departments_table`
    - Columns: `id`, `org_id` (FK → organizations, cascadeOnDelete), `name` (string), `timestamps`
    - Add `unique(['org_id', 'name'])` and `index('org_id')`
    - _Requirements: 1.1, 1.2, 1.4_

  - [x] 2.2 Create `app/Models/Department.php`
    - `#[Fillable(['org_id', 'name'])]`; boot `OrgScope`; define `employees(): HasMany`
    - _Requirements: 4.1, 5.1_

  - [ ]* 2.3 Write property test for unique department name within org (Property 4)
    - **Property 4: Unique name within org (departments)**
    - **Validates: Requirements 1.4**

- [x] 3. Create designations migration and `Designation` model
  - [x] 3.1 Create migration `create_designations_table`
    - Columns: `id`, `org_id` (FK → organizations, cascadeOnDelete), `name` (string), `timestamps`
    - Add `unique(['org_id', 'name'])` and `index('org_id')`
    - _Requirements: 2.1, 2.2, 2.4_

  - [x] 3.2 Create `app/Models/Designation.php`
    - `#[Fillable(['org_id', 'name'])]`; boot `OrgScope`; define `employees(): HasMany`
    - _Requirements: 4.1, 6.1_

  - [ ]* 3.3 Write property test for unique designation name within org (Property 4)
    - **Property 4: Unique name within org (designations)**
    - **Validates: Requirements 2.4**

- [x] 4. Create employees migration and `Employee` model
  - [x] 4.1 Create migration `create_employees_table`
    - Columns: `id`, `org_id` (FK → organizations), `user_id` (nullable FK → users, nullOnDelete), `name`, `email`, `phone` (nullable, max 20), `department_id` (FK → departments, restrictOnDelete), `designation_id` (FK → designations, restrictOnDelete), `manager_id` (nullable self-ref FK → employees, nullOnDelete), `joining_date` (date), `status` (enum active/inactive, default active), `softDeletes()`, `timestamps`
    - Add `unique(['org_id', 'email'])` and indexes on `org_id`, `department_id`, `designation_id`, `manager_id`
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 4.2 Create `app/Models/Employee.php`
    - Use `SoftDeletes`; `#[Fillable([...])]` all writable columns; cast `joining_date` to `date`
    - Boot `OrgScope`; define relationships: `organization`, `department`, `designation`, `manager` (belongsTo self via `manager_id`), `subordinates` (hasMany self via `manager_id`), `user`
    - Add query scopes: `scopeActive()` and `scopeSearch(string $term)`
    - _Requirements: 3.4, 4.1, 12.1, 12.2_

  - [ ]* 4.3 Write property test for org isolation on query results (Property 1)
    - **Property 1: Org isolation — query results**
    - **Validates: Requirements 4.1**

  - [ ]* 4.4 Write property test for 404 on cross-org access (Property 2)
    - **Property 2: Org isolation — 404 on cross-org access**
    - **Validates: Requirements 4.2**

- [x] 5. Create `EmployeeObserver` and register it
  - [x] 5.1 Create `app/Observers/EmployeeObserver.php`
    - In `deleting(Employee $employee)`: use `Employee::withoutGlobalScope(OrgScope::class)->where('manager_id', $employee->id)->update(['manager_id' => null])`
    - _Requirements: 11.4_

  - [x] 5.2 Register observer and `EmployeePolicy` in `AppServiceProvider::boot()`
    - Add `Employee::observe(EmployeeObserver::class)`
    - Add `Gate::policy(Employee::class, EmployeePolicy::class)`
    - _Requirements: 11.4_

  - [ ]* 5.3 Write property test for soft delete nulling subordinates (Property 12)
    - **Property 12: Soft delete nulls subordinate manager_id**
    - **Validates: Requirements 11.4**

- [x] 6. Create Form Request classes
  - [x] 6.1 Create `app/Http/Requests/StoreDepartmentRequest.php` and `UpdateDepartmentRequest.php`
    - `authorize()` returns `$this->user()->can('create', Department::class)` / `update` respectively
    - `rules()`: `name` → `required|string|max:255` + `Rule::unique('departments')->where('org_id', ...)` (Update: `->ignore($this->department)`)
    - Override `prepareForValidation()` to `strip_tags` on `name`
    - _Requirements: 5.5, 16.1, 16.2, 16.3_

  - [x] 6.2 Create `app/Http/Requests/StoreDesignationRequest.php` and `UpdateDesignationRequest.php`
    - Same structure as department requests but scoped to `designations` table
    - _Requirements: 6.5, 16.1, 16.2, 16.3_

  - [x] 6.3 Create `app/Http/Requests/StoreEmployeeRequest.php`
    - `authorize()` returns `$this->user()->can('create', Employee::class)`
    - Rules: `name` (required, string, max 255), `email` (required, email, max 255, unique within org), `phone` (nullable, string, max 20), `department_id` (required, exists in departments scoped to org), `designation_id` (required, exists in designations scoped to org), `manager_id` (nullable, exists in employees scoped to org), `user_id` (nullable, exists in users scoped to org), `joining_date` (required, date_format:Y-m-d, before_or_equal:today), `status` (nullable, in:active,inactive)
    - Override `prepareForValidation()` to `strip_tags` on `name`, `email`, `phone`
    - _Requirements: 7.2, 7.3, 7.5, 16.1, 16.2, 16.3, 16.4_

  - [x] 6.4 Create `app/Http/Requests/UpdateEmployeeRequest.php`
    - Same rules as `StoreEmployeeRequest` with `email` uniqueness ignoring current employee (`->ignore($this->employee)`)
    - Add `withValidator()` to reject `manager_id === $this->employee->id` with message "An employee cannot be their own manager."
    - _Requirements: 10.2, 10.4, 16.2_

  - [ ]* 6.5 Write property test for employee required field validation (Property 7)
    - **Property 7: Employee required field validation**
    - **Validates: Requirements 7.2, 16.3**

  - [ ]* 6.6 Write property test for email uniqueness within org (Property 8)
    - **Property 8: Employee email uniqueness within org**
    - **Validates: Requirements 3.3, 7.2, 10.2**

  - [ ]* 6.7 Write property test for manager-cannot-be-self rule (Property 9)
    - **Property 9: Manager cannot be self**
    - **Validates: Requirements 10.4**

  - [ ]* 6.8 Write property test for manager must belong to same org (Property 10)
    - **Property 10: Manager must belong to same org**
    - **Validates: Requirements 7.5, 12.4**

  - [ ]* 6.9 Write property test for joining date validation (Property 16)
    - **Property 16: Joining date validation**
    - **Validates: Requirements 16.4**

- [x] 7. Create `DepartmentController`
  - Create `app/Http/Controllers/DepartmentController.php` with `index`, `create`, `store`, `edit`, `update`, `destroy`
  - `store()`: merge `org_id` from `auth()->user()->org_id`; redirect to `departments.index` with success flash
  - `update()`: update record; redirect with success flash
  - `destroy()`: if `$department->employees()->exists()` redirect back with error; else delete and redirect with success
  - _Requirements: 1.3, 5.1, 5.2, 5.3, 5.4_

  - [ ]* 7.1 Write property test for permission gate on department mutations (Property 6)
    - **Property 6: 403 for unauthorized mutations (departments)**
    - **Validates: Requirements 5.4**

  - [ ]* 7.2 Write property test for prevent deletion when employees assigned (Property 5)
    - **Property 5: Prevent deletion when employees assigned (departments)**
    - **Validates: Requirements 1.3**

- [x] 8. Create `DesignationController`
  - Create `app/Http/Controllers/DesignationController.php` with identical structure to `DepartmentController` but operating on `Designation` model
  - _Requirements: 2.3, 6.1, 6.2, 6.3, 6.4_

  - [ ]* 8.1 Write property test for permission gate on designation mutations (Property 6)
    - **Property 6: 403 for unauthorized mutations (designations)**
    - **Validates: Requirements 6.4**

  - [ ]* 8.2 Write property test for prevent deletion when employees assigned (Property 5)
    - **Property 5: Prevent deletion when employees assigned (designations)**
    - **Validates: Requirements 2.3**

- [x] 9. Create `EmployeeController`
  - [x] 9.1 Create `app/Http/Controllers/EmployeeController.php` with `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`
    - `index()`: apply `search`, `department_id`, `status` filters via query scopes; eager-load `department` and `designation`; paginate 20 with `withQueryString()`; pass `$departments` for filter dropdown
    - `show()`: load employee with `manager`, `department`, `designation` relationships
    - `create()`: pass `$departments`, `$designations`, active employees (for manager dropdown) to view
    - `store()`: merge `org_id`; redirect to `employees.index` with success
    - `edit()`: pass employee + dropdowns; manager list excludes `$employee->id`
    - `update()`: update record; redirect to `employees.show` with success
    - `destroy()`: soft-delete; redirect to `employees.index` with success
    - _Requirements: 7.1, 8.1, 8.2, 8.3, 8.4, 8.5, 9.1, 10.1, 11.1, 11.2, 12.3_

  - [ ]* 9.2 Write property test for auto-set org_id on create (Property 3)
    - **Property 3: Auto-set org_id on create**
    - **Validates: Requirements 4.4**

  - [ ]* 9.3 Write property test for employee directory filters (Property 11)
    - **Property 11: Employee directory filters**
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 11.2**

  - [ ]* 9.4 Write property test for permission gate on employee mutations (Property 6)
    - **Property 6: 403 for unauthorized mutations (employees)**
    - **Validates: Requirements 7.4, 10.3, 11.3_

- [x] 10. Create `EmployeePolicy`
  - Create `app/Policies/EmployeePolicy.php`
  - `viewAny` and `view` return `true` for any authenticated user
  - `create`: `$user->hasPermissionTo('manage-employees')`
  - `update`: `manage-employees` AND `$user->org_id === $employee->org_id`
  - `delete`: delegates to `update`
  - _Requirements: 8.6, 9.4, 7.4, 10.3, 11.3_

  - [ ]* 10.1 Write property test for action controls visibility (Property 14)
    - **Property 14: Action controls visibility**
    - **Validates: Requirements 8.6, 9.4**

- [x] 11. Update `routes/web.php`
  - Add under `auth + verified` middleware group:
    - `GET /employees` → `EmployeeController@index` (name: `employees.index`)
    - `GET /employees/{employee}` → `EmployeeController@show` (name: `employees.show`)
  - Nest under additional `permission:manage-employees` middleware:
    - `GET /employees/create`, `POST /employees`, `GET /employees/{employee}/edit`, `PUT /employees/{employee}`, `DELETE /employees/{employee}`
    - `Route::resource('departments', DepartmentController::class)->except(['show'])`
    - `Route::resource('designations', DesignationController::class)->except(['show'])`
  - _Requirements: 4.2, 5.4, 6.4, 7.4, 8.1, 9.1_

- [x] 12. Create `status-badge` Blade component
  - Create `resources/views/components/status-badge.blade.php`
  - Accept `$status` prop; apply `bg-green-100 text-green-800` for `active`, `bg-red-100 text-red-800` for `inactive`
  - Render as `<span>` with `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium` base classes
  - _Requirements: 13.3_

  - [ ]* 12.1 Write property test for status badge rendering (Property 17)
    - **Property 17: Status badge rendering**
    - **Validates: Requirements 13.3**

- [x] 13. Create Blade views
  - [x] 13.1 Create `resources/views/employees/index.blade.php`
    - Extend `x-app-layout`; render filter bar (search input, department dropdown, status dropdown, Filter button) with active values preserved via `request()`
    - Render responsive table: name (link to show), email, department, designation, `<x-status-badge>`
    - Show "Add Employee" button and edit/delete actions only via `@can`
    - Render `$employees->links()` pagination; show success/error flash messages
    - _Requirements: 8.6, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_

  - [x] 13.2 Create `resources/views/employees/show.blade.php`
    - Display all employee fields: name, email, phone, department, designation, joining date, status badge
    - Show manager name as a link to manager's profile when `manager_id` is set
    - Show edit/delete controls only via `@can`
    - _Requirements: 9.2, 9.3, 9.4, 12.5_

  - [x] 13.3 Create `resources/views/employees/create.blade.php`
    - Form with all employee fields; `department_id`, `designation_id`, `manager_id` as `<select>` inputs populated from controller-passed collections
    - Manager dropdown includes blank "No Manager" option; `status` defaults to `active`
    - Inline validation errors via `<x-input-error>`
    - _Requirements: 14.1, 14.2, 14.4, 14.5, 14.6_

  - [x] 13.4 Create `resources/views/employees/edit.blade.php`
    - Same structure as create form; pre-populate all fields with `old()` falling back to `$employee` values
    - Manager dropdown excludes the employee being edited
    - _Requirements: 14.1, 14.2, 14.3, 14.6_

  - [x] 13.5 Create `resources/views/departments/index.blade.php`, `create.blade.php`, `edit.blade.php`
    - Index: list departments with edit/delete buttons; show success/error flash messages
    - Create/Edit: single `name` text input, submit button, `<x-input-error>` for validation errors
    - _Requirements: 15.1, 15.2, 15.3, 15.4_

  - [x] 13.6 Create `resources/views/designations/index.blade.php`, `create.blade.php`, `edit.blade.php`
    - Same structure as department views but for designations
    - _Requirements: 15.1, 15.2, 15.3, 15.4_

  - [ ]* 13.7 Write property test for form dropdowns are org-scoped (Property 15)
    - **Property 15: Form dropdowns are org-scoped**
    - **Validates: Requirements 14.4, 14.5, 14.6**

  - [ ]* 13.8 Write property test for manager dropdown excludes self (Property 13)
    - **Property 13: Manager dropdown excludes self**
    - **Validates: Requirements 12.3**

- [x] 14. Run migrations and checkpoint
  - Run `php artisan migrate` to apply all three new migrations
  - Ensure all existing tests still pass, ask the user if questions arise.

- [x] 15. Write PHPUnit feature tests
  - [x] 15.1 Create `tests/Feature/DepartmentControllerTest.php`
    - Test store/update/destroy happy paths with `manage-employees` user
    - Test 403 for user without `manage-employees`
    - Test validation error on duplicate name within org
    - Test error redirect when deleting department with assigned employees
    - _Requirements: 1.3, 1.4, 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 15.2 Create `tests/Feature/DesignationControllerTest.php`
    - Same structure as `DepartmentControllerTest` for designations
    - _Requirements: 2.3, 2.4, 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 15.3 Create `tests/Feature/EmployeeControllerTest.php`
    - Test index with each filter (search, department_id, status) returns only matching employees
    - Test show returns correct employee data
    - Test store creates employee with correct `org_id`
    - Test update modifies record and redirects to show
    - Test destroy soft-deletes and excludes from index
    - Test 403 for user without `manage-employees` on create/update/destroy
    - Test 404 for cross-org employee access
    - _Requirements: 4.2, 7.1, 8.1, 8.2, 8.3, 8.4, 9.1, 10.1, 11.1, 11.2_

  - [x] 15.4 Create `tests/Feature/EmployeeObserverTest.php`
    - Assert that soft-deleting a manager sets `manager_id` to `null` on all subordinates
    - _Requirements: 11.4_

  - [x] 15.5 Create `tests/Unit/EmployeePolicyTest.php`
    - Assert `create`/`update`/`delete` return `true` for user with `manage-employees` in same org
    - Assert `false` for user without permission
    - Assert `false` for user with permission but different `org_id`
    - Assert `viewAny` and `view` return `true` for any authenticated user
    - _Requirements: 8.6, 9.4, 7.4, 10.3, 11.3_

- [ ] 16. Write Eris property-based tests
  - [ ] 16.1 Create `tests/Feature/Properties/OrgIsolationPropertyTest.php`
    - **Property 1**: generate two orgs with random employees; authenticate as org A; assert `Employee::all()` contains only org A records
    - **Property 2**: generate employee in org B; authenticate as org A user; assert `GET /employees/{id}` returns 404
    - **Property 3**: POST valid employee payload (with wrong/no org_id) as org A admin; assert persisted `org_id === orgA->id`
    - _Requirements: 4.1, 4.2, 4.4_

  - [ ] 16.2 Create `tests/Feature/Properties/DepartmentDesignationPropertyTest.php`
    - **Property 4**: create department/designation with random name in org A; attempt duplicate in org A → assert failure; same name in org B → assert success
    - **Property 5**: create department/designation with assigned employees; attempt DELETE → assert redirect with error and record still exists
    - **Property 6**: for each mutation route (store/update/destroy) on departments and designations, authenticate without `manage-employees` → assert 403
    - _Requirements: 1.3, 1.4, 2.3, 2.4, 5.4, 6.4_

  - [ ] 16.3 Create `tests/Feature/Properties/EmployeeValidationPropertyTest.php`
    - **Property 7**: generate payloads with one required field missing/invalid; assert redirect back with errors and old input preserved
    - **Property 8**: generate random email; create employee in org A; attempt duplicate in org A → error; same email in org B → success; update own email → no error
    - **Property 9**: for any employee, submit update with `manager_id = employee->id` → assert validation error
    - **Property 10**: generate manager in org B; submit create/update in org A with that `manager_id` → assert validation error
    - **Property 16**: generate invalid date strings and future dates → assert validation failure; valid past Y-m-d dates → assert pass
    - _Requirements: 3.3, 7.2, 7.5, 10.2, 10.4, 16.3, 16.4_

  - [ ] 16.4 Create `tests/Feature/Properties/EmployeeBehaviorPropertyTest.php`
    - **Property 6**: for employee store/update/destroy routes, authenticate without `manage-employees` → assert 403
    - **Property 11**: generate random employees across departments/statuses; for any filter combination assert all returned employees satisfy all active filters and none are soft-deleted
    - **Property 12**: generate manager with random subordinates; soft-delete manager; assert all subordinates have `manager_id = null`
    - _Requirements: 7.4, 8.1, 8.2, 8.3, 8.4, 11.2, 11.4_

  - [ ] 16.5 Create `tests/Feature/Properties/ViewPropertyTest.php`
    - **Property 13**: for any employee, render edit form; assert employee's own id is absent from manager select options
    - **Property 14**: for any user without `manage-employees`, render `employees.index` and `employees.show`; assert no create/edit/delete controls in HTML
    - **Property 15**: render employee create form as org A user; assert all department/designation/manager options belong to org A only
    - **Property 17**: for `status='active'` assert badge HTML contains `green`; for `status='inactive'` assert badge HTML contains `red`
    - _Requirements: 8.6, 9.4, 12.3, 13.3, 14.4, 14.5, 14.6_

- [x] 17. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Property tests use Eris (`giorgiosironi/eris`) already in `require-dev`; each property runs a minimum of 100 iterations
- Unit/feature tests use PHPUnit 12 with Laravel's `RefreshDatabase` trait
- `OrgScope` must be bypassed with `withoutGlobalScope(OrgScope::class)` in the observer and seeders
- Route model binding automatically applies `OrgScope`, so cross-org record access yields 404 without extra controller code
- The `manage-employees` permission is already seeded by `RoleAndPermissionSeeder` from Phase 2
