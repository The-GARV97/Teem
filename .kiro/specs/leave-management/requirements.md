# Requirements Document

## Introduction

The Leave Management System is Phase 4 of WorkForge SaaS. It enables employees to apply for leave, managers and admins to approve or reject requests, and the system to track leave balances per employee per leave type per year. All data is scoped by `org_id` within a single-database multi-tenant architecture built on Laravel 13, Breeze (Blade), and Tailwind CSS.

## Glossary

- **System**: The WorkForge Leave Management System
- **Leave_Type_Manager**: The subsystem responsible for creating, updating, and deleting leave types
- **Leave_Request_System**: The subsystem responsible for processing leave applications and approvals
- **Balance_Tracker**: The subsystem responsible for tracking and enforcing leave balance limits
- **Leave_Type**: A configurable category of leave (e.g. Casual, Sick, Paid) belonging to an organization, with a defined maximum number of days per year
- **Leave_Request**: A formal request submitted by an Employee for a specific Leave_Type covering a date range
- **Leave_Balance**: A record tracking how many days of a given Leave_Type an Employee has used in a given year
- **Employee**: A user with the `Employee` role who can apply for leave
- **Manager**: A user with the `Manager` role who can approve or reject leave requests
- **Admin**: A user with the `Admin` role who can manage leave types and approve or reject leave requests
- **Reviewer**: A Manager or Admin who acts on a Leave_Request
- **OrgScope**: The global Eloquent scope that automatically filters all queries by the authenticated user's `org_id`
- **Working_Days**: Calendar days between two dates, excluding Saturdays and Sundays
- **Status**: The current state of a Leave_Request — one of `pending`, `approved`, or `rejected`

---

## Requirements

### Requirement 1: Leave Type Management

**User Story:** As an Admin, I want to create, edit, and delete leave types with a maximum day limit, so that I can define what categories of leave are available to employees in my organization.

#### Acceptance Criteria

1. THE Leave_Type_Manager SHALL scope all leave type queries by the authenticated user's `org_id`.
2. WHEN an Admin submits a valid create-leave-type form with a unique name and a positive integer `max_days`, THE Leave_Type_Manager SHALL persist the Leave_Type record associated with the Admin's `org_id`.
3. WHEN an Admin submits an edit-leave-type form with valid data, THE Leave_Type_Manager SHALL update the Leave_Type record and reflect the changes immediately.
4. WHEN an Admin requests deletion of a Leave_Type that has no associated Leave_Requests, THE Leave_Type_Manager SHALL delete the Leave_Type record.
5. IF an Admin requests deletion of a Leave_Type that has one or more associated Leave_Requests, THEN THE Leave_Type_Manager SHALL reject the deletion and return a descriptive error message.
6. IF the leave type name is blank or `max_days` is not a positive integer, THEN THE Leave_Type_Manager SHALL return a validation error without persisting any data.
7. IF a user without the `manage-employees` permission attempts to create, edit, or delete a Leave_Type, THEN THE System SHALL return an HTTP 403 response.

---

### Requirement 2: Leave Application

**User Story:** As an Employee, I want to apply for leave by selecting a leave type, date range, and reason, so that my request can be reviewed by a Manager or Admin.

#### Acceptance Criteria

1. WHEN an Employee submits a valid leave application with a Leave_Type, `start_date`, `end_date`, and `reason`, THE Leave_Request_System SHALL create a Leave_Request with `status = pending` scoped to the Employee's `org_id`.
2. THE Leave_Request_System SHALL compute `total_days` as the count of Working_Days between `start_date` and `end_date`, inclusive.
3. IF `start_date` is after `end_date`, THEN THE Leave_Request_System SHALL return a validation error and SHALL NOT persist the Leave_Request.
4. IF the Employee's Leave_Balance for the selected Leave_Type in the current year has `used_days + total_days > max_days`, THEN THE Leave_Request_System SHALL return an insufficient-balance error and SHALL NOT persist the Leave_Request.
5. IF the selected Leave_Type does not belong to the Employee's `org_id`, THEN THE Leave_Request_System SHALL return a validation error.
6. IF a user without the `apply-leave` permission attempts to submit a leave application, THEN THE System SHALL return an HTTP 403 response.
7. THE Leave_Request_System SHALL prevent an Employee from submitting a leave application on behalf of another Employee.

---

### Requirement 3: Approval Workflow

**User Story:** As a Manager or Admin, I want to approve or reject pending leave requests with an optional rejection reason, so that employees are informed of the outcome.

#### Acceptance Criteria

1. WHEN a Reviewer approves a Leave_Request with `status = pending`, THE Leave_Request_System SHALL update the Leave_Request `status` to `approved`, record `reviewed_by` as the Reviewer's user ID, and record `reviewed_at` as the current timestamp.
2. WHEN a Reviewer rejects a Leave_Request with `status = pending`, THE Leave_Request_System SHALL update the Leave_Request `status` to `rejected`, record `reviewed_by`, `reviewed_at`, and persist the provided `rejection_reason`.
3. IF a Reviewer attempts to approve or reject a Leave_Request whose `status` is not `pending`, THEN THE Leave_Request_System SHALL return an error and SHALL NOT modify the Leave_Request.
4. IF a user without the `approve-leave` permission attempts to approve or reject a Leave_Request, THEN THE System SHALL return an HTTP 403 response.
5. THE Leave_Request_System SHALL scope all approval and rejection queries by the Reviewer's `org_id`, preventing cross-organization access.

---

### Requirement 4: Leave Balance Tracking

**User Story:** As the system, I want to automatically track and update leave balances when requests are approved, so that employees cannot exceed their annual leave entitlement.

#### Acceptance Criteria

1. WHEN a Leave_Request is approved, THE Balance_Tracker SHALL increment the Employee's Leave_Balance `used_days` for the corresponding Leave_Type and year by the Leave_Request's `total_days`.
2. WHEN a Leave_Request transitions from `approved` to `rejected` (i.e. a previously approved request is reversed), THE Balance_Tracker SHALL decrement the Employee's Leave_Balance `used_days` by the Leave_Request's `total_days`.
3. THE Balance_Tracker SHALL initialize a Leave_Balance record with `used_days = 0` for an Employee, Leave_Type, and year combination when no record exists.
4. THE Balance_Tracker SHALL enforce uniqueness on the combination of `org_id`, `employee_id`, `leave_type_id`, and `year` in the `leave_balances` table.
5. WHILE computing available days, THE Balance_Tracker SHALL calculate available days as `max_days - used_days` for the relevant Leave_Type and year.

---

### Requirement 5: Leave History

**User Story:** As an Employee, I want to view my own leave history with status badges, so that I can track the outcome of my requests. As a Manager or Admin, I want to view all leave requests in my organization, so that I can monitor leave activity.

#### Acceptance Criteria

1. WHEN an Employee accesses the leave history view, THE Leave_Request_System SHALL display only Leave_Requests belonging to that Employee, scoped by `org_id`.
2. WHEN a Manager or Admin accesses the leave history view, THE Leave_Request_System SHALL display all Leave_Requests within their `org_id`.
3. THE Leave_Request_System SHALL display each Leave_Request with a status badge indicating `pending`, `approved`, or `rejected`.
4. THE Leave_Request_System SHALL display the Leave_Type name, `start_date`, `end_date`, `total_days`, and `reason` for each Leave_Request in the history view.
5. WHEN a Leave_Request has `status = rejected`, THE Leave_Request_System SHALL display the `rejection_reason` alongside the Leave_Request in the history view.
6. THE Leave_Request_System SHALL scope all leave history queries by `org_id`, preventing cross-organization data exposure.

---

### Requirement 6: Dashboard Integration

**User Story:** As an Admin or Manager, I want to see pending, approved, and rejected leave counts on the dashboard, so that I have a quick overview of leave activity in my organization.

#### Acceptance Criteria

1. WHEN an Admin or Manager views the dashboard, THE System SHALL display the count of Leave_Requests with `status = pending` scoped to their `org_id`.
2. WHEN an Admin or Manager views the dashboard, THE System SHALL display the count of Leave_Requests with `status = approved` scoped to their `org_id`.
3. WHEN an Admin or Manager views the dashboard, THE System SHALL display the count of Leave_Requests with `status = rejected` scoped to their `org_id`.
4. WHEN an Employee views the employee dashboard, THE System SHALL display the count of that Employee's own Leave_Requests grouped by status.
5. THE System SHALL scope all dashboard leave counts by `org_id`.

---

### Requirement 7: Data Integrity and Security

**User Story:** As the system, I want to enforce data integrity and org isolation on all leave data, so that no organization can access or modify another organization's leave records.

#### Acceptance Criteria

1. THE System SHALL apply OrgScope to all queries on `leave_types`, `leave_requests`, and `leave_balances` tables.
2. IF a request references a `leave_type_id` or `employee_id` that does not belong to the authenticated user's `org_id`, THEN THE System SHALL return an HTTP 404 response.
3. THE System SHALL validate that `total_days` is always a positive integer before persisting a Leave_Request.
4. THE System SHALL store `start_date` and `end_date` as date values and SHALL NOT accept non-date input for these fields.
5. THE System SHALL enforce the `status` field as an enum restricted to `pending`, `approved`, and `rejected`.
