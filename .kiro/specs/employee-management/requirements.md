# Requirements Document

## Introduction

Phase 3 of WorkForge SaaS introduces Employee Management: a fully org-scoped module for managing employees, departments, and designations within a single-database multi-tenant Laravel application. Building on Phase 1 (organizations + users) and Phase 2 (Spatie RBAC), this phase delivers an employee directory with search and filtering, individual employee profiles, CRUD operations for employees/departments/designations (Admin only), and a manager hierarchy via a self-referential relationship on the employees table. All data is strictly scoped by `org_id` to prevent cross-organization access.

---

## Glossary

- **System**: The WorkForge Laravel application
- **Employee_Controller**: The controller responsible for employee CRUD and directory operations
- **Department_Controller**: The controller responsible for department CRUD operations
- **Designation_Controller**: The controller responsible for designation CRUD operations
- **Employee**: A record in the `employees` table representing a person within an organization; may optionally be linked to a `User` account
- **Department**: An organizational unit (e.g., Engineering, HR) belonging to a single organization
- **Designation**: A job title or role label (e.g., Software Engineer, HR Manager) belonging to a single organization
- **Manager**: An Employee record assigned as the direct supervisor of another Employee via the `manager_id` self-relation
- **Employee_Directory**: The paginated, filterable list view of all employees within the authenticated user's organization
- **Employee_Profile**: The detail view for a single Employee record
- **Org_Scope**: The constraint that all queries are filtered by the authenticated user's `org_id`
- **Admin**: A user with the Spatie `Admin` or `SuperAdmin` role who holds the `manage-employees` permission
- **Soft_Delete**: Laravel's `SoftDeletes` trait behavior where records are marked with `deleted_at` rather than physically removed
- **Validator**: The Laravel form request validation layer applied to all create and update operations

---

## Requirements

### Requirement 1: Database Schema — Departments

**User Story:** As a developer, I want a `departments` table scoped by `org_id`, so that each organization can maintain its own set of departments independently.

#### Acceptance Criteria

1. THE System SHALL create a `departments` table with columns: `id`, `org_id` (foreign key to `organizations`), `name` (string, not null), `created_at`, and `updated_at`.
2. THE System SHALL add a database index on `departments.org_id` to optimize org-scoped queries.
3. IF a department is deleted, THEN THE System SHALL prevent deletion when employees are still assigned to that department, returning a validation error to the user.
4. THE System SHALL enforce a unique constraint on `(org_id, name)` so that duplicate department names within the same organization are not permitted.

---

### Requirement 2: Database Schema — Designations

**User Story:** As a developer, I want a `designations` table scoped by `org_id`, so that each organization can maintain its own set of job titles independently.

#### Acceptance Criteria

1. THE System SHALL create a `designations` table with columns: `id`, `org_id` (foreign key to `organizations`), `name` (string, not null), `created_at`, and `updated_at`.
2. THE System SHALL add a database index on `designations.org_id` to optimize org-scoped queries.
3. IF a designation is deleted, THEN THE System SHALL prevent deletion when employees are still assigned to that designation, returning a validation error to the user.
4. THE System SHALL enforce a unique constraint on `(org_id, name)` so that duplicate designation names within the same organization are not permitted.

---

### Requirement 3: Database Schema — Employees

**User Story:** As a developer, I want an `employees` table that captures all HR-relevant fields and supports a manager hierarchy, so that the organization's workforce structure is fully represented in the database.

#### Acceptance Criteria

1. THE System SHALL create an `employees` table with columns: `id`, `org_id` (foreign key to `organizations`), `user_id` (nullable foreign key to `users`), `name` (string, not null), `email` (string, not null), `phone` (string, nullable), `department_id` (foreign key to `departments`), `designation_id` (foreign key to `designations`), `manager_id` (nullable self-referential foreign key to `employees`), `joining_date` (date, not null), `status` (enum: `active`, `inactive`, default `active`), `deleted_at` (nullable timestamp for soft deletes), `created_at`, and `updated_at`.
2. THE System SHALL add database indexes on `employees.org_id`, `employees.department_id`, `employees.designation_id`, and `employees.manager_id` to optimize filtered queries.
3. THE System SHALL enforce a unique constraint on `(org_id, email)` so that duplicate employee email addresses within the same organization are not permitted.
4. THE System SHALL apply Laravel's `SoftDeletes` trait to the `Employee` model so that deleted employees are retained in the database with a `deleted_at` timestamp.

---

### Requirement 4: Org Scoping — Data Isolation

**User Story:** As a platform operator, I want all employee, department, and designation queries strictly scoped to the authenticated user's organization, so that no cross-organization data leakage is possible.

#### Acceptance Criteria

1. WHEN any query is executed against the `employees`, `departments`, or `designations` tables, THE System SHALL apply a `WHERE org_id = :authenticated_user_org_id` constraint on every query.
2. WHEN an authenticated user attempts to view, edit, or delete a record whose `org_id` does not match the authenticated user's `org_id`, THEN THE System SHALL return an HTTP 404 response.
3. THE Employee_Controller, Department_Controller, and Designation_Controller SHALL each resolve records using the authenticated user's `org_id` as the primary scope before applying any other filters.
4. WHEN a new Employee, Department, or Designation is created, THE System SHALL automatically set the `org_id` field to the authenticated user's `org_id` without relying on user-supplied input.

---

### Requirement 5: Department Management — CRUD

**User Story:** As an Admin, I want to create, view, edit, and delete departments within my organization, so that I can keep the organizational structure up to date.

#### Acceptance Criteria

1. WHEN an authenticated user with the `manage-employees` permission submits a valid department creation form, THE Department_Controller SHALL persist a new Department record scoped to the user's `org_id` and redirect to the department list with a success message.
2. WHEN an authenticated user with the `manage-employees` permission submits a valid department update form, THE Department_Controller SHALL update the matching Department record and redirect to the department list with a success message.
3. WHEN an authenticated user with the `manage-employees` permission requests deletion of a department that has no assigned employees, THE Department_Controller SHALL soft-delete or permanently delete the record and redirect with a success message.
4. IF a user without the `manage-employees` permission attempts to access department create, edit, or delete routes, THEN THE System SHALL return an HTTP 403 response.
5. THE Validator SHALL require `name` to be present, a string, and at most 255 characters for both create and update operations on departments.

---

### Requirement 6: Designation Management — CRUD

**User Story:** As an Admin, I want to create, view, edit, and delete designations within my organization, so that job titles remain accurate and consistent.

#### Acceptance Criteria

1. WHEN an authenticated user with the `manage-employees` permission submits a valid designation creation form, THE Designation_Controller SHALL persist a new Designation record scoped to the user's `org_id` and redirect to the designation list with a success message.
2. WHEN an authenticated user with the `manage-employees` permission submits a valid designation update form, THE Designation_Controller SHALL update the matching Designation record and redirect to the designation list with a success message.
3. WHEN an authenticated user with the `manage-employees` permission requests deletion of a designation that has no assigned employees, THE Designation_Controller SHALL delete the record and redirect with a success message.
4. IF a user without the `manage-employees` permission attempts to access designation create, edit, or delete routes, THEN THE System SHALL return an HTTP 403 response.
5. THE Validator SHALL require `name` to be present, a string, and at most 255 characters for both create and update operations on designations.

---

### Requirement 7: Employee CRUD — Create

**User Story:** As an Admin, I want to create new employee records with all required fields, so that new hires are immediately represented in the system.

#### Acceptance Criteria

1. WHEN an authenticated user with the `manage-employees` permission submits a valid employee creation form, THE Employee_Controller SHALL persist a new Employee record with `org_id` set to the authenticated user's `org_id` and redirect to the employee directory with a success message.
2. THE Validator SHALL require `name` (string, max 255), `email` (valid email format, unique within the org), `department_id` (exists in `departments` scoped to the same org), `designation_id` (exists in `designations` scoped to the same org), and `joining_date` (valid date, not in the future) for employee creation.
3. THE Validator SHALL treat `phone` (string, max 20), `manager_id` (nullable, must reference an employee in the same org), `user_id` (nullable, must reference a user in the same org), and `status` (one of: `active`, `inactive`) as optional fields with their respective validation rules applied when present.
4. IF a user without the `manage-employees` permission attempts to access the employee creation route, THEN THE System SHALL return an HTTP 403 response.
5. WHEN `manager_id` is provided, THE Validator SHALL reject a value where the manager's `org_id` does not match the authenticated user's `org_id`.

---

### Requirement 8: Employee CRUD — Read (Directory)

**User Story:** As any authenticated user within an organization, I want to browse a paginated employee directory filtered by department and status, so that I can quickly locate colleagues.

#### Acceptance Criteria

1. WHEN an authenticated user visits the employee directory, THE Employee_Controller SHALL return a paginated list of employees scoped to the authenticated user's `org_id`, ordered by `name` ascending, with 20 records per page.
2. WHEN a `department_id` filter parameter is present in the request, THE Employee_Controller SHALL restrict results to employees whose `department_id` matches the provided value.
3. WHEN a `status` filter parameter is present in the request with a value of `active` or `inactive`, THE Employee_Controller SHALL restrict results to employees whose `status` matches the provided value.
4. WHEN a `search` query parameter is present in the request, THE Employee_Controller SHALL restrict results to employees whose `name` or `email` contains the search string (case-insensitive).
5. THE Employee_Controller SHALL eager-load the `department` and `designation` relationships on the employee list query to avoid N+1 queries.
6. THE System SHALL display the employee directory to all authenticated users regardless of role, but SHALL display create, edit, and delete action controls only to users with the `manage-employees` permission.

---

### Requirement 9: Employee CRUD — Read (Profile)

**User Story:** As any authenticated user within an organization, I want to view a detailed profile page for an individual employee, so that I can see all relevant information about that person.

#### Acceptance Criteria

1. WHEN an authenticated user requests an employee profile, THE Employee_Controller SHALL load the Employee record scoped to the authenticated user's `org_id` and pass it to the profile view.
2. THE System SHALL display on the employee profile: name, email, phone, department name, designation name, manager name (if assigned), joining date, and status.
3. WHEN the employee has a `manager_id` assigned, THE System SHALL display the manager's name as a link to the manager's own employee profile.
4. THE System SHALL display edit and delete action controls on the profile page only to users with the `manage-employees` permission.

---

### Requirement 10: Employee CRUD — Update

**User Story:** As an Admin, I want to edit an existing employee's details, so that records stay accurate when information changes.

#### Acceptance Criteria

1. WHEN an authenticated user with the `manage-employees` permission submits a valid employee update form, THE Employee_Controller SHALL update the matching Employee record and redirect to the employee profile with a success message.
2. THE Validator SHALL apply the same field rules as Requirement 7, with the `email` uniqueness rule ignoring the current employee's own record.
3. IF a user without the `manage-employees` permission attempts to access the employee edit route, THEN THE System SHALL return an HTTP 403 response.
4. WHEN `manager_id` is set to the employee's own `id`, THE Validator SHALL reject the value with an error message indicating an employee cannot be their own manager.

---

### Requirement 11: Employee CRUD — Delete (Soft Delete)

**User Story:** As an Admin, I want to soft-delete an employee record, so that historical data is preserved while the employee no longer appears in active directory listings.

#### Acceptance Criteria

1. WHEN an authenticated user with the `manage-employees` permission requests deletion of an employee, THE Employee_Controller SHALL set the `deleted_at` timestamp on the Employee record and redirect to the employee directory with a success message.
2. WHEN the employee directory is queried, THE Employee_Controller SHALL exclude soft-deleted employees from results by default.
3. IF a user without the `manage-employees` permission attempts to delete an employee, THEN THE System SHALL return an HTTP 403 response.
4. WHEN an employee is soft-deleted and that employee is assigned as `manager_id` on other employees, THE System SHALL set `manager_id` to `null` on all subordinate employee records.

---

### Requirement 12: Manager Hierarchy

**User Story:** As an Admin, I want to assign a manager to each employee using a self-referential relationship, so that the organizational reporting structure is captured in the system.

#### Acceptance Criteria

1. THE Employee model SHALL define a `manager` belongs-to relationship pointing to another Employee record via `manager_id`.
2. THE Employee model SHALL define a `subordinates` has-many relationship returning all Employee records whose `manager_id` equals the current employee's `id`.
3. WHEN the employee create or edit form is rendered, THE System SHALL populate the manager dropdown with all active employees in the same org excluding the employee being edited.
4. WHEN `manager_id` is saved, THE System SHALL verify the referenced manager record exists and belongs to the same `org_id` as the employee being saved.
5. THE System SHALL display the manager's name on the Employee_Profile view with a navigable link to the manager's profile.

---

### Requirement 13: UI — Employee Directory View

**User Story:** As any authenticated user, I want a clean, responsive employee directory page styled with Tailwind CSS in the indigo color scheme, so that the UI is consistent with the rest of the application.

#### Acceptance Criteria

1. THE System SHALL render the employee directory using the existing `x-app-layout` Blade component so that the navigation bar and layout are consistent with other authenticated pages.
2. THE System SHALL display employees in a responsive table or card grid that shows at minimum: name, email, department, designation, and status badge.
3. THE System SHALL render the `status` field as a colored badge: `active` employees displayed with a green badge and `inactive` employees displayed with a red badge.
4. THE System SHALL render a filter bar above the employee list containing a text search input, a department dropdown, and a status dropdown, all styled with Tailwind CSS indigo accents.
5. WHEN filters are applied, THE System SHALL preserve the active filter values in the rendered form inputs so that the user can see which filters are currently active.
6. THE System SHALL render a "Add Employee" button visible only to users with the `manage-employees` permission, styled as a primary indigo button consistent with the existing design system.

---

### Requirement 14: UI — Employee Create and Edit Forms

**User Story:** As an Admin, I want well-structured create and edit forms for employees, so that data entry is straightforward and validation errors are clearly communicated.

#### Acceptance Criteria

1. THE System SHALL render employee create and edit forms using the existing `x-app-layout` Blade component with Tailwind CSS styling consistent with the indigo color scheme.
2. THE System SHALL display inline validation error messages beneath each form field using the existing `x-input-error` Blade component when validation fails.
3. WHEN the edit form is rendered, THE System SHALL pre-populate all form fields with the current employee's existing values.
4. THE System SHALL populate the `department_id` select input with all departments belonging to the authenticated user's org.
5. THE System SHALL populate the `designation_id` select input with all designations belonging to the authenticated user's org.
6. THE System SHALL populate the `manager_id` select input with all active employees in the org, excluding the employee being edited, with a blank "No Manager" option as the default.

---

### Requirement 15: UI — Department and Designation Management Views

**User Story:** As an Admin, I want simple list and form views for managing departments and designations, so that I can maintain these reference data sets without leaving the application.

#### Acceptance Criteria

1. THE System SHALL render department and designation list views using the existing `x-app-layout` Blade component, displaying each record's name alongside edit and delete action buttons.
2. THE System SHALL render department and designation create/edit forms with a single `name` text input, a submit button, and inline validation error display using the existing `x-input-error` component.
3. WHEN a delete action is triggered for a department or designation that has assigned employees, THE System SHALL display an error message to the user and not perform the deletion.
4. THE System SHALL display a success flash message after any successful create, update, or delete operation on departments or designations.

---

### Requirement 16: Input Validation — Security

**User Story:** As a developer, I want all form inputs validated server-side before persistence, so that invalid or malicious data cannot enter the database.

#### Acceptance Criteria

1. THE Validator SHALL strip or reject any HTML tags from `name`, `email`, and `phone` fields to prevent stored XSS.
2. THE System SHALL use Laravel Form Request classes (not inline controller validation) for all employee, department, and designation create and update operations.
3. WHEN a validation error occurs, THE System SHALL redirect back to the form with the validation errors and the previously submitted input values preserved so the user does not need to re-enter valid data.
4. THE System SHALL validate that `joining_date` is a valid calendar date in `Y-m-d` format.
