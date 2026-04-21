# Requirements Document

## Introduction

Phase 2 of WorkForge SaaS introduces a formal Role & Permission System backed by `spatie/laravel-permission`, an Admin Dashboard with org-scoped statistics, and role-based post-login redirects. The system builds on Phase 1's single-database multi-tenancy foundation where users already carry a plain `role` string column (`admin`, `member`, `superadmin`) and belong to an organization via `org_id`. This phase migrates that string-based role into Spatie's RBAC model, enforces permissions via middleware and Laravel Policies, and delivers a clean Tailwind dashboard for Admin users.

---

## Glossary

- **System**: The WorkForge Laravel application
- **RBAC_System**: The Spatie Laravel Permission integration responsible for managing roles and permissions
- **Auth_Controller**: The `AuthenticatedSessionController` that handles login and post-login redirects
- **Dashboard_Controller**: The controller responsible for rendering the Admin Dashboard view
- **Role_Seeder**: The artisan seeder that creates Spatie roles/permissions and syncs existing users
- **Permission**: A named capability (e.g., `manage-employees`, `approve-leave`, `apply-leave`) assigned to one or more roles
- **Org_Scope**: The constraint that all role assignments and data queries are filtered by the authenticated user's `org_id`
- **Admin**: A user with the `admin` role string, mapped to the Spatie `Admin` role; has full org-level access
- **Manager**: A user with the `manager` role string, mapped to the Spatie `Manager` role; can approve leave
- **Employee**: A user with the `member` role string, mapped to the Spatie `Employee` role; can apply for leave
- **Superadmin**: A platform-level user with the `superadmin` role string, mapped to the Spatie `SuperAdmin` role; has all permissions (`*`) and cross-org access
- **Stats_Card**: A Tailwind UI component displaying a single numeric metric on the dashboard
- **Leave_Request**: A future-phase model; for Phase 2 the dashboard counts are sourced from a placeholder or the `leave_requests` table if it exists

---

## Requirements

### Requirement 1: Install and Configure Spatie Laravel Permission

**User Story:** As a developer, I want Spatie Laravel Permission installed and configured for single-database multi-tenancy, so that roles and permissions are stored in the database and scoped per organization.

#### Acceptance Criteria

1. THE RBAC_System SHALL use the `spatie/laravel-permission` package with its published migration and config file present in the project.
2. THE RBAC_System SHALL store roles and permissions in the default Spatie tables (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`).
3. THE System SHALL add the `Spatie\Permission\Traits\HasRoles` trait to the `User` model so that role and permission checks are available on every user instance.
4. WHEN the Spatie permission config is published, THE RBAC_System SHALL set `teams` to `false` because org-scoping is handled via `org_id` on the `User` model, not via Spatie's teams feature.

---

### Requirement 2: Define Roles and Permissions

**User Story:** As a developer, I want four roles and their associated permissions seeded into the database, so that the application has a consistent, reproducible permission structure.

#### Acceptance Criteria

1. THE Role_Seeder SHALL create four Spatie roles: `SuperAdmin`, `Admin`, `Manager`, and `Employee`.
2. THE Role_Seeder SHALL create three permissions: `manage-employees`, `approve-leave`, and `apply-leave`.
3. THE Role_Seeder SHALL assign ALL permissions (`*`) to the `SuperAdmin` role using `givePermissionTo(Permission::all())`, so that SuperAdmin bypasses all permission checks.
4. THE Role_Seeder SHALL assign the `manage-employees` permission to the `Admin` role.
5. THE Role_Seeder SHALL assign the `approve-leave` permission to both the `Admin` role and the `Manager` role.
6. THE Role_Seeder SHALL assign the `apply-leave` permission to the `Employee` role.
7. WHEN the Role_Seeder is run more than once, THE Role_Seeder SHALL use `firstOrCreate` (or equivalent idempotent method) so that duplicate roles and permissions are not created.

---

### Requirement 3: Sync Existing Users to Spatie Roles

**User Story:** As a developer, I want existing users whose `role` column is already populated to be automatically assigned the corresponding Spatie role, so that no manual data migration is required after the seeder runs.

#### Acceptance Criteria

1. WHEN the Role_Seeder runs, THE Role_Seeder SHALL iterate over all existing users and assign each user the Spatie role that corresponds to their `role` column value according to the mapping: `admin` → `Admin`, `member` → `Employee`, `superadmin` → `SuperAdmin`.
2. WHEN a user's `role` column value does not match any known mapping, THE Role_Seeder SHALL skip that user and log a warning message to the Laravel log.
3. WHEN the Role_Seeder assigns a Spatie role to a user who already has that role, THE Role_Seeder SHALL not create a duplicate assignment.

---

### Requirement 4: Sync Spatie Role on Login

**User Story:** As a developer, I want a user's Spatie role to be verified and synced at login time, so that users created after the initial seeder run are also correctly assigned their Spatie role.

#### Acceptance Criteria

1. WHEN a user successfully authenticates, THE Auth_Controller SHALL check whether the authenticated user has any Spatie roles assigned.
2. WHEN the authenticated user has no Spatie roles, THE Auth_Controller SHALL assign the Spatie role that corresponds to the user's `role` column value using the same mapping defined in Requirement 3.
3. WHEN the authenticated user already has a Spatie role assigned, THE Auth_Controller SHALL not modify the existing role assignment.

---

### Requirement 5: Protect Routes via Role Middleware

**User Story:** As a developer, I want routes protected by Spatie role middleware, so that unauthorized users cannot access areas outside their permission level.

#### Acceptance Criteria

1. THE System SHALL register the Spatie `role` and `permission` middleware aliases in the application's middleware configuration.
2. WHEN a request is made to `/dashboard`, THE System SHALL allow access only to users who have the `Admin` Spatie role or the `superadmin` string role.
3. WHEN a request is made to `/employee/dashboard`, THE System SHALL allow access only to users who have the `Employee` Spatie role.
4. WHEN a request is made to `/manager/dashboard`, THE System SHALL allow access only to users who have the `Manager` Spatie role.
5. WHEN a request is made to `/superadmin/dashboard`, THE System SHALL allow access only to users who have the `superadmin` string role.
6. IF an authenticated user attempts to access a route for which they lack the required role, THEN THE System SHALL redirect the user to their own role-appropriate dashboard with an HTTP 403 response or a redirect, not a raw exception page.

---

### Requirement 6: Role-Based Post-Login Redirect

**User Story:** As a user, I want to be redirected to the correct dashboard after login based on my role, so that I land on a page relevant to my responsibilities without manual navigation.

#### Acceptance Criteria

1. WHEN a user with the `Admin` Spatie role successfully logs in, THE Auth_Controller SHALL redirect the user to `/dashboard`.
2. WHEN a user with the `Employee` Spatie role successfully logs in, THE Auth_Controller SHALL redirect the user to `/employee/dashboard`.
3. WHEN a user with the `Manager` Spatie role successfully logs in, THE Auth_Controller SHALL redirect the user to `/manager/dashboard`.
4. WHEN a user with the `superadmin` role string successfully logs in, THE Auth_Controller SHALL redirect the user to `/superadmin/dashboard`.
5. IF a user has no recognized role at login time, THEN THE Auth_Controller SHALL redirect the user to `/dashboard` as a safe fallback.

---

### Requirement 7: Admin Dashboard — Statistics Display

**User Story:** As an Admin, I want a dashboard that shows key organization metrics at a glance, so that I can monitor my organization's activity without navigating multiple pages.

#### Acceptance Criteria

1. WHEN an Admin user visits `/dashboard`, THE Dashboard_Controller SHALL query and pass to the view: the total count of users belonging to the authenticated user's `org_id`, the count of leave requests with status `pending` for that org, the count of leave requests with status `approved` for that org, and the count of leave requests with status `rejected` for that org.
2. WHILE the `leave_requests` table does not yet exist, THE Dashboard_Controller SHALL pass zero values for all leave-related counts so that the view renders without errors.
3. THE Dashboard_Controller SHALL scope all queries to the authenticated user's `org_id` so that no cross-organization data is exposed.
4. WHEN the dashboard view renders, THE System SHALL display each metric inside a Stats_Card component showing a label and a numeric value.
5. THE System SHALL display a minimum of four Stats_Cards on the Admin Dashboard: "Total Employees", "Pending Leaves", "Approved Leaves", and "Rejected Leaves".

---

### Requirement 8: Admin Dashboard — UI Design

**User Story:** As an Admin, I want the dashboard UI to match the existing indigo-themed split-screen design, so that the application has a consistent visual identity.

#### Acceptance Criteria

1. THE System SHALL render the Admin Dashboard using the existing `x-app-layout` Blade component so that the navigation bar and layout are consistent with other authenticated pages.
2. THE System SHALL style Stats_Cards using Tailwind CSS utility classes with an indigo color scheme (e.g., `bg-indigo-600`, `text-indigo-100`) consistent with the existing login and registration views.
3. THE System SHALL arrange Stats_Cards in a responsive CSS grid that displays one column on small screens and four columns on large screens (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`).
4. WHEN a Stats_Card is rendered, THE System SHALL display an icon or visual indicator alongside the metric label and value to aid quick scanning.

---

### Requirement 9: Laravel Policies for Resource Authorization

**User Story:** As a developer, I want Laravel Policies to enforce fine-grained authorization on resource actions, so that business logic for access control is centralized and testable independently of middleware.

#### Acceptance Criteria

1. THE System SHALL define a `UserPolicy` that authorizes the `manage-employees` permission check for actions that create, update, or delete users within an organization.
2. WHEN a user without the `manage-employees` permission attempts an action gated by `UserPolicy`, THE System SHALL return an unauthorized response.
3. THE System SHALL register all Policies in `AuthServiceProvider` (or via Laravel's auto-discovery) so that they are available throughout the application.
4. WHEN a Policy check is performed, THE System SHALL additionally verify that the target resource belongs to the same `org_id` as the authenticated user, enforcing org-level isolation.

---

### Requirement 10: Superadmin Platform Dashboard

**User Story:** As a Superadmin, I want a dedicated platform-level dashboard, so that I can monitor the overall WorkForge platform without being scoped to a single organization.

#### Acceptance Criteria

1. WHEN a Superadmin user visits `/superadmin/dashboard`, THE Dashboard_Controller SHALL query and pass to the view: the total count of all organizations, and the total count of all users across all organizations.
2. THE System SHALL protect the `/superadmin/dashboard` route so that only users with the `superadmin` role string can access it; all other users SHALL be redirected to their own dashboard.
3. THE System SHALL render the Superadmin Dashboard using the same `x-app-layout` and indigo Tailwind styling as the Admin Dashboard.
