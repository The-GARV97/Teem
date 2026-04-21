# Requirements Document

## Introduction

The Superadmin feature introduces a platform-level administrative role for WorkForge, a multi-tenant SaaS HR platform. A Superadmin operates above all organizations, with no affiliation to any specific tenant. The Superadmin can manage all organizations, all users across every organization, platform-wide configurations (plans, billing settings, feature flags), and can impersonate any organization admin. The Superadmin account is provisioned exclusively via a database seeder and accesses a dedicated panel protected by its own middleware.

## Glossary

- **Superadmin**: A platform-level user with `role = 'superadmin'` and `org_id = null`, who has unrestricted access to all platform data and configuration.
- **Organization**: A tenant entity in the `organizations` table representing a company using WorkForge.
- **Org_Admin**: A user with `role = 'admin'` belonging to a specific organization.
- **Member**: A user with `role = 'member'` belonging to a specific organization.
- **Platform_Config**: Platform-wide settings including subscription plans, billing parameters, and feature flags.
- **Feature_Flag**: A boolean platform-level toggle that enables or disables a specific feature across all or selected organizations.
- **Impersonation**: The act of the Superadmin authenticating as an Org_Admin to act on their behalf within that organization's context.
- **Superadmin_Panel**: The dedicated web interface accessible only to Superadmin users, served under the `/superadmin` route prefix.
- **Superadmin_Middleware**: The Laravel middleware that restricts access to Superadmin_Panel routes to users with `role = 'superadmin'`.
- **Seeder**: A Laravel database seeder class used to provision the Superadmin account during platform setup.
- **Platform_Dashboard**: The overview page within the Superadmin_Panel displaying aggregate platform statistics.

---

## Requirements

### Requirement 1: Superadmin Role and Account Provisioning

**User Story:** As a platform operator, I want a Superadmin account created via a seeder, so that I can bootstrap platform administration without exposing a registration flow.

#### Acceptance Criteria

1. THE Seeder SHALL create a User record with `role = 'superadmin'` and `org_id = null`.
2. THE Seeder SHALL set the Superadmin's email and password from environment variables `SUPERADMIN_EMAIL` and `SUPERADMIN_PASSWORD`.
3. IF `SUPERADMIN_EMAIL` or `SUPERADMIN_PASSWORD` environment variables are not set, THEN THE Seeder SHALL throw a descriptive exception and halt execution.
4. THE Seeder SHALL be idempotent: WHEN run multiple times, THE Seeder SHALL not create duplicate Superadmin records.
5. THE User model SHALL allow `org_id` to be null for users with `role = 'superadmin'`.
6. THE users table migration SHALL make the `org_id` foreign key nullable to support Superadmin records.

---

### Requirement 2: Superadmin Authentication

**User Story:** As a Superadmin, I want to log in through the standard login form, so that I can access the platform panel without a separate authentication system.

#### Acceptance Criteria

1. WHEN a user with `role = 'superadmin'` submits valid credentials, THE Authentication_System SHALL authenticate the user and redirect to the Superadmin_Panel dashboard.
2. WHEN a user with `role = 'superadmin'` successfully authenticates, THE Authentication_System SHALL not redirect to the standard `/dashboard` route.
3. IF a Superadmin attempts to access a non-superadmin route, THEN THE Superadmin_Middleware SHALL redirect the request to the Superadmin_Panel dashboard.

---

### Requirement 3: Superadmin Middleware and Route Protection

**User Story:** As a platform operator, I want all Superadmin_Panel routes protected by dedicated middleware, so that no regular user or org admin can access platform-level controls.

#### Acceptance Criteria

1. THE Superadmin_Middleware SHALL verify that the authenticated user has `role = 'superadmin'` before allowing access to any `/superadmin/*` route.
2. IF an unauthenticated user requests a `/superadmin/*` route, THEN THE Superadmin_Middleware SHALL redirect to the login page.
3. IF an authenticated user without `role = 'superadmin'` requests a `/superadmin/*` route, THEN THE Superadmin_Middleware SHALL return a 403 Forbidden response.
4. THE Superadmin_Panel routes SHALL be registered under the `/superadmin` prefix and grouped under the `superadmin` middleware alias.
5. THE Superadmin_Panel routes SHALL be defined in a dedicated `routes/superadmin.php` file, separate from `routes/web.php`.

---

### Requirement 4: Platform Dashboard

**User Story:** As a Superadmin, I want a platform-level dashboard, so that I can monitor the overall health and growth of the WorkForge platform.

#### Acceptance Criteria

1. WHEN the Superadmin accesses the Superadmin_Panel dashboard, THE Platform_Dashboard SHALL display the total count of organizations.
2. WHEN the Superadmin accesses the Superadmin_Panel dashboard, THE Platform_Dashboard SHALL display the total count of users across all organizations.
3. WHEN the Superadmin accesses the Superadmin_Panel dashboard, THE Platform_Dashboard SHALL display the count of users grouped by role (`admin`, `member`).
4. WHEN the Superadmin accesses the Superadmin_Panel dashboard, THE Platform_Dashboard SHALL display the count of organizations created within the last 30 days.
5. THE Platform_Dashboard SHALL load all statistics in a single page request without requiring additional API calls.

---

### Requirement 5: Organization Management

**User Story:** As a Superadmin, I want to view and manage all organizations on the platform, so that I can oversee tenant health and take corrective action when needed.

#### Acceptance Criteria

1. WHEN the Superadmin navigates to the organizations list, THE Superadmin_Panel SHALL display all Organization records with their name, user count, and creation date.
2. WHEN the Superadmin views an organization's detail page, THE Superadmin_Panel SHALL display all users belonging to that organization with their name, email, and role.
3. WHEN the Superadmin submits a valid organization name, THE Superadmin_Panel SHALL create a new Organization record and display a success confirmation.
4. WHEN the Superadmin submits an updated organization name, THE Superadmin_Panel SHALL update the Organization record and display a success confirmation.
5. WHEN the Superadmin confirms deletion of an organization, THE Superadmin_Panel SHALL delete the Organization record and all associated users via cascading delete.
6. IF the Superadmin submits an empty or duplicate organization name, THEN THE Superadmin_Panel SHALL display a descriptive validation error and not persist the change.

---

### Requirement 6: User Management Across All Organizations

**User Story:** As a Superadmin, I want to view and manage all users across every organization, so that I can handle account issues without requiring org-level admin involvement.

#### Acceptance Criteria

1. WHEN the Superadmin navigates to the users list, THE Superadmin_Panel SHALL display all User records across all organizations with their name, email, role, and organization name.
2. WHEN the Superadmin updates a user's name, email, or role, THE Superadmin_Panel SHALL persist the change and display a success confirmation.
3. WHEN the Superadmin confirms deletion of a user, THE Superadmin_Panel SHALL delete the User record and display a success confirmation.
4. IF the Superadmin attempts to delete the Superadmin account itself, THEN THE Superadmin_Panel SHALL reject the request and display an error message.
5. IF the Superadmin submits invalid data for a user update (e.g., malformed email, invalid role value), THEN THE Superadmin_Panel SHALL display a descriptive validation error and not persist the change.
6. THE Superadmin_Panel SHALL allow filtering the users list by organization.

---

### Requirement 7: Platform Configuration Management

**User Story:** As a Superadmin, I want to manage platform-wide configurations, so that I can control subscription plans, billing settings, and feature availability across all tenants.

#### Acceptance Criteria

1. THE Superadmin_Panel SHALL provide a configuration interface for managing Platform_Config records including plan names, billing parameters, and Feature_Flags.
2. WHEN the Superadmin saves a Platform_Config value, THE Superadmin_Panel SHALL persist the change and display a success confirmation.
3. WHEN the Superadmin toggles a Feature_Flag, THE Superadmin_Panel SHALL update the flag's boolean value and display a success confirmation.
4. IF the Superadmin submits an invalid Platform_Config value (e.g., negative billing amount, blank plan name), THEN THE Superadmin_Panel SHALL display a descriptive validation error and not persist the change.
5. THE Platform_Config values SHALL be stored in a dedicated `platform_configs` database table with `key` and `value` columns.

---

### Requirement 8: Org Admin Impersonation

**User Story:** As a Superadmin, I want to impersonate any Org_Admin, so that I can troubleshoot tenant-specific issues from within their context.

#### Acceptance Criteria

1. WHEN the Superadmin initiates impersonation of an Org_Admin, THE Superadmin_Panel SHALL store the original Superadmin user ID in the session and authenticate the session as the target Org_Admin.
2. WHILE an impersonation session is active, THE Superadmin_Panel SHALL display a persistent banner indicating the Superadmin is impersonating another user.
3. WHEN the Superadmin ends an impersonation session, THE Superadmin_Panel SHALL restore the original Superadmin session and redirect to the Superadmin_Panel dashboard.
4. IF the Superadmin attempts to impersonate a user with `role = 'superadmin'`, THEN THE Superadmin_Panel SHALL reject the request and display an error message.
5. IF the Superadmin attempts to impersonate a user with `role = 'member'`, THEN THE Superadmin_Panel SHALL reject the request and display an error message indicating only Org_Admins can be impersonated.
6. WHILE an impersonation session is active, THE Superadmin_Panel SHALL prevent the impersonated session from accessing `/superadmin/*` routes.
