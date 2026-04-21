# Implementation Plan: Roles and Dashboard

## Overview

Implement Spatie RBAC, role-based post-login redirects, org-scoped Admin Dashboard, and platform-level Superadmin Dashboard on top of the existing single-database multi-tenancy foundation.

## Tasks

- [x] 1. Install and configure spatie/laravel-permission
  - Run `composer require spatie/laravel-permission`
  - Run `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"` to publish `config/permission.php` and the Spatie migration
  - Verify `config/permission.php` has `'teams' => false`
  - Run `php artisan migrate` to create the Spatie tables
  - _Requirements: 1.1, 1.2, 1.4_

- [x] 2. Add HasRoles trait to User model
  - [x] 2.1 Update `app/Models/User.php` to use `Spatie\Permission\Traits\HasRoles`
    - Add `use HasRoles;` alongside the existing `HasFactory, Notifiable` traits
    - Add the `use Spatie\Permission\Traits\HasRoles;` import
    - _Requirements: 1.3_

  - [ ]* 2.2 Write unit test for HasRoles trait presence
    - Assert `User` instance responds to `hasRole()` and `assignRole()`
    - _Requirements: 1.3_

- [x] 3. Register Spatie middleware aliases in bootstrap/app.php
  - Update `bootstrap/app.php` `->withMiddleware()` closure to call `$middleware->alias([...])` with `role`, `permission`, and `role_or_permission` keys pointing to the three Spatie middleware classes
  - Add the `UnauthorizedException` handler in `->withExceptions()` to redirect to `/dashboard` with a flash error instead of showing a raw 403 page
  - _Requirements: 5.1, 5.6_

- [x] 4. Create RoleAndPermissionSeeder
  - [x] 4.1 Create `database/seeders/RoleAndPermissionSeeder.php`
    - Flush permission cache at the top of `run()`
    - Create three permissions via `Permission::firstOrCreate`: `manage-employees`, `approve-leave`, `apply-leave`
    - Create four roles via `Role::firstOrCreate`: `SuperAdmin`, `Admin`, `Manager`, `Employee`
    - Assign permissions to roles per the mapping table in the design
    - Call `$this->syncExistingUsers()` at the end of `run()`
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7_

  - [x] 4.2 Implement `syncExistingUsers()` private method in `RoleAndPermissionSeeder`
    - Map `['superadmin' => 'SuperAdmin', 'admin' => 'Admin', 'manager' => 'Manager', 'member' => 'Employee']`
    - Iterate `User::all()`, skip and `Log::warning()` for unknown role strings
    - Only call `assignRole()` when `!$user->hasRole($spatieName)` to avoid duplicates
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ]* 4.3 Write property test for seeder idempotence (Property 1)
    - **Property 1: Seeder idempotence**
    - **Validates: Requirements 2.7, 3.3**
    - Generate random sets of users with known role strings; run seeder twice; assert `Role::count()` and `Permission::count()` are unchanged and no user has duplicate role assignments

  - [ ]* 4.4 Write property test for user role mapping (Property 2)
    - **Property 2: User role mapping**
    - **Validates: Requirements 3.1**
    - For any user with `role` in `['superadmin','admin','manager','member']`, after seeding assert `$user->getRoleNames()` contains exactly the mapped Spatie role name

  - [ ]* 4.5 Write unit tests for RoleAndPermissionSeeder
    - Assert all four roles exist after seeding
    - Assert permission assignments match the mapping table
    - Assert unknown-role users are skipped without exception
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 3.2_

- [x] 5. Wire RoleAndPermissionSeeder into DatabaseSeeder
  - Update `database/seeders/DatabaseSeeder.php` to call `$this->call(RoleAndPermissionSeeder::class)` (after `SuperadminSeeder` if present)
  - _Requirements: 2.1_

- [x] 6. Checkpoint — Ensure seeder and model tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 7. Update AuthenticatedSessionController
  - [x] 7.1 Add `syncSpatieRole(User $user): void` private method
    - Return early if `$user->roles()->exists()`
    - Otherwise map `$user->role` string to Spatie role name and call `$user->assignRole()`
    - _Requirements: 4.1, 4.2, 4.3_

  - [x] 7.2 Add `redirectForRole(User $user): string` private method
    - Use `match(true)` to return the correct dashboard URL per role
    - Fall back to `/dashboard` for unrecognized roles
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 7.3 Update `store()` to call both new methods after session regeneration
    - Replace the existing `redirect()->intended(...)` with `redirect($this->redirectForRole($user))`
    - _Requirements: 4.1, 6.1, 6.2, 6.3, 6.4_

  - [ ]* 7.4 Write property test for login-time role sync (Property 3)
    - **Property 3: Login-time role sync**
    - **Validates: Requirements 4.2, 4.3**
    - For any user with no Spatie roles, simulate `store()` and assert `$user->roles()->count() === 1` with the correct role name; for users already having a Spatie role, assert roles are unchanged

  - [ ]* 7.5 Write property test for role-based login redirect (Property 5)
    - **Property 5: Role-based login redirect**
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5**
    - For any user with a known Spatie role, assert `redirectForRole()` returns the expected URL; for unknown roles assert fallback is `/dashboard`

  - [ ]* 7.6 Write unit tests for AuthenticatedSessionController
    - Assert login redirects to the correct URL for each of the four roles
    - Assert a user with no Spatie role gets one assigned after login
    - Assert a user with an existing Spatie role is not modified
    - _Requirements: 4.2, 4.3, 6.1, 6.2, 6.3, 6.4, 6.5_

- [x] 8. Create DashboardController
  - [x] 8.1 Create `app/Http/Controllers/DashboardController.php` with `index()`, `employee()`, `manager()`, and `superadmin()` methods
    - `index()`: query `User::where('org_id', $orgId)->count()` and call `leaveCount()` for each status; pass `$stats` to `view('dashboard')`
    - `employee()`: return `view('employee.dashboard')`
    - `manager()`: return `view('manager.dashboard')`
    - `superadmin()`: query `Organization::count()` and `User::count()`; pass `$stats` to `view('superadmin.dashboard')`
    - _Requirements: 7.1, 7.2, 7.3, 10.1_

  - [x] 8.2 Implement `leaveCount(int $orgId, string $status): int` private method
    - Return `0` immediately if `!Schema::hasTable('leave_requests')`
    - Otherwise query `DB::table('leave_requests')->where('org_id', $orgId)->where('status', $status)->count()`
    - _Requirements: 7.2_

  - [ ]* 8.3 Write property test for dashboard stats org isolation (Property 6)
    - **Property 6: Dashboard stats accuracy and org isolation**
    - **Validates: Requirements 7.1, 7.3**
    - For any two orgs with randomly generated user counts, assert stats for org A never include users from org B and `total_employees` equals `User::where('org_id', orgA)->count()`

  - [ ]* 8.4 Write property test for superadmin cross-org stats (Property 8)
    - **Property 8: Superadmin cross-org stats**
    - **Validates: Requirements 10.1**
    - For any N orgs and M users distributed across them, assert `superadmin()` passes `total_organizations === N` and `total_users === M` to the view

  - [ ]* 8.5 Write unit tests for DashboardController
    - Assert `leaveCount()` returns 0 when `leave_requests` table is absent
    - Assert superadmin stats include all orgs and all users
    - _Requirements: 7.2, 10.1_

- [x] 9. Update routes/web.php
  - Replace the existing closure-based `/dashboard` route with `DashboardController::index` protected by `['auth', 'verified', 'role:Admin|SuperAdmin']`
  - Add `/employee/dashboard` → `DashboardController::employee` with `['auth', 'role:Employee']`
  - Add `/manager/dashboard` → `DashboardController::manager` with `['auth', 'role:Manager']`
  - Add `/superadmin/dashboard` → `DashboardController::superadmin` with `['auth', 'role:SuperAdmin']`
  - _Requirements: 5.2, 5.3, 5.4, 5.5_

  - [ ]* 9.1 Write property test for route access control (Property 4)
    - **Property 4: Route access control**
    - **Validates: Requirements 5.2, 5.3, 5.4, 5.5, 5.6, 10.2**
    - For any (route, role) pair, assert `actingAs(userWithRole)->get(route)` returns 200 iff the role matches the required role, else 403 or redirect

- [x] 10. Checkpoint — Ensure controller and route tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 11. Redesign resources/views/dashboard.blade.php
  - Replace the existing content with `x-app-layout` wrapping a 4-column responsive stats grid (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`)
  - Render four Stats_Cards with `bg-indigo-600` styling for: Total Employees, Pending Leaves, Approved Leaves, Rejected Leaves
  - Each card shows an icon, label (`text-indigo-200`), and value (`text-white text-3xl font-bold`)
  - _Requirements: 7.4, 7.5, 8.1, 8.2, 8.3, 8.4_

- [x] 12. Create resources/views/superadmin/dashboard.blade.php
  - Create the `resources/views/superadmin/` directory and `dashboard.blade.php`
  - Use `x-app-layout` with the same indigo stats-card grid pattern
  - Display two cards: "Total Organizations" (`$stats['total_organizations']`) and "Total Users" (`$stats['total_users']`)
  - _Requirements: 10.1, 10.3_

- [x] 13. Create stub views for employee and manager dashboards
  - Create `resources/views/employee/dashboard.blade.php` — minimal `x-app-layout` stub with a heading "Employee Dashboard"
  - Create `resources/views/manager/dashboard.blade.php` — minimal `x-app-layout` stub with a heading "Manager Dashboard"
  - _Requirements: 5.3, 5.4_

- [x] 14. Create UserPolicy
  - [x] 14.1 Create `app/Policies/UserPolicy.php`
    - Implement `manageEmployees(User $authUser, User $targetUser): bool` — returns true iff `$authUser->hasPermissionTo('manage-employees') && $authUser->org_id === $targetUser->org_id`
    - Implement `create(User $authUser): bool` — delegates to `hasPermissionTo('manage-employees')`
    - Implement `update(User $authUser, User $targetUser): bool` — delegates to `manageEmployees()`
    - Implement `delete(User $authUser, User $targetUser): bool` — delegates to `manageEmployees()`
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [ ]* 14.2 Write property test for UserPolicy permission and org isolation (Property 7)
    - **Property 7: UserPolicy permission and org isolation**
    - **Validates: Requirements 9.1, 9.2, 9.4**
    - For any (actingUser, targetUser) pair, assert policy returns true iff actingUser has `manage-employees` AND `actingUser->org_id === targetUser->org_id`

  - [ ]* 14.3 Write unit tests for UserPolicy
    - Assert policy denies users without `manage-employees`
    - Assert policy denies cross-org access even when permission is present
    - _Requirements: 9.1, 9.2, 9.4_

- [x] 15. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Property tests use Eris (`giorgiosironi/eris`) already in `require-dev`; each property runs a minimum of 100 iterations
- Unit tests use PHPUnit with Laravel's `RefreshDatabase` trait
- The `leave_requests` table does not exist yet; `Schema::hasTable()` guards all leave-count queries
- Spatie's auto-discovery registers `UserPolicy` automatically in Laravel 13 — no manual `AppServiceProvider` registration needed
