# Implementation Plan: Multi-Tenant Organization

## Overview

Introduce single-database multi-tenancy by adding an `organizations` table, linking every user to an organization via `org_id`, and updating the registration flow to create both atomically. Uses Laravel 13, Breeze (Blade), PHPUnit, and Eris for property-based tests.

## Tasks

- [x] 1. Create database migrations
  - [x] 1.1 Create `create_organizations_table` migration
    - Generate migration file: `php artisan make:migration create_organizations_table`
    - Add `id`, `name` (string, NOT NULL), and `timestamps()` columns
    - _Requirements: 1.2_

  - [x] 1.2 Create `add_org_id_and_role_to_users_table` migration
    - Generate migration file: `php artisan make:migration add_org_id_and_role_to_users_table`
    - Add `org_id` as `foreignId()->constrained('organizations')->cascadeOnDelete()` (NOT NULL)
    - Add `role` as `string()->default('admin')`
    - This migration must run after the organizations migration
    - _Requirements: 1.2, 1.4, 2.2_

- [x] 2. Create and update Eloquent models
  - [x] 2.1 Create `Organization` Eloquent model
    - Create `app/Models/Organization.php`
    - Add `HasMany` relationship to `User` via `users()`
    - Set `#[Fillable(['name'])]` attribute
    - _Requirements: 1.2_

  - [x] 2.2 Update `User` model
    - Add `BelongsTo` relationship to `Organization` via `organization()` using `org_id` foreign key
    - Update `#[Fillable]` attribute to include `org_id` and `role`
    - _Requirements: 1.2, 1.4_

  - [ ]* 2.3 Write unit tests for model relationships and fillable
    - Assert `Organization::users()` returns a `HasMany` instance
    - Assert `User::organization()` returns a `BelongsTo` instance
    - Assert `User` fillable includes `org_id` and `role`
    - _Requirements: 1.2, 1.4_

- [x] 3. Update registration controller
  - [x] 3.1 Update `RegisteredUserController::store` to handle organization creation
    - Add `use App\Models\Organization` and `use Illuminate\Support\Facades\DB` imports
    - Add `company_name` validation rule: `['required', 'string', 'max:255']`
    - Wrap `Organization::create` and `User::create` in `DB::transaction()`
    - Set `org_id` from the newly created organization's `id`
    - Set `role` to `'admin'` on the user
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 4. Update registration view
  - [x] 4.1 Add `company_name` field to `register.blade.php`
    - Insert the `company_name` input block as the first field in the form, above the `name` field
    - Use `<x-input-label>`, `<x-text-input>`, and `<x-input-error>` Breeze components
    - Set `autofocus` on the `company_name` field and remove it from the `name` field
    - Bind `:value="old('company_name')"` for repopulation on validation failure
    - _Requirements: 1.1_

- [x] 5. Checkpoint — run migrations and smoke-test the registration form
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Write PHPUnit feature tests
  - [x] 6.1 Extend `tests/Feature/Auth/RegistrationTest.php` with multi-tenant scenarios
    - Test: successful registration creates one `Organization` and one `User`, `user.org_id == organization.id`, `user.role == 'admin'`
    - Test: registration with empty `company_name` returns 422 validation error, no org or user created
    - Test: registration with whitespace-only `company_name` is rejected
    - Test: `company_name` of exactly 255 characters is accepted; 256 characters is rejected
    - Test: registration with duplicate email returns validation error and no org is created
    - Test: register form renders a `company_name` input field
    - Use `RefreshDatabase` trait on the test class
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 3.2_

- [x] 7. Write Eris property-based tests
  - [x] 7.1 Create `tests/Feature/Auth/RegistrationPropertyTest.php` and install Eris
    - Require `giorgiosironi/eris` via composer (dev dependency) if not already present
    - Create the test class extending `TestCase` with `use Eris\TestTrait` and `RefreshDatabase`
    - Configure minimum 100 iterations per property
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.2_

  - [ ]* 7.2 Write property test for Property 1: company name validation rejects invalid input
    - **Property 1: Company name validation rejects invalid input**
    - Generate arbitrary blank/whitespace `company_name` strings using Eris generators
    - Assert HTTP 302 redirect back with validation errors, 0 organizations and 0 users created
    - **Validates: Requirements 1.1, 3.2**

  - [ ]* 7.3 Write property test for Property 2: registration round-trip user-org linkage
    - **Property 2: Registration round-trip — user is linked to its organization**
    - Generate valid registration payloads with arbitrary names, emails, and company names
    - Assert `user.org_id == organization.id` and `user.org_id` is non-null after registration
    - **Validates: Requirements 1.2, 1.5**

  - [ ]* 7.4 Write property test for Property 3: transaction atomicity
    - **Property 3: Transaction atomicity — all-or-nothing creation**
    - Simulate user creation failure by injecting a duplicate email after org creation
    - Assert org count is unchanged (no orphaned organization row persisted)
    - **Validates: Requirements 1.3**

  - [ ]* 7.5 Write property test for Property 4: new registrant always receives admin role
    - **Property 4: New registrant always receives the admin role**
    - Generate valid registration payloads with arbitrary inputs
    - Assert every created `User` has `role == 'admin'`
    - **Validates: Requirements 1.4**

  - [ ]* 7.6 Write property test for Property 5: tenant scoping prevents cross-org data leakage
    - **Property 5: Tenant scoping prevents cross-organization data leakage**
    - Generate N organizations each with M associated users
    - Assert a query scoped to org A (`where('org_id', $orgA->id)`) never returns users belonging to org B
    - **Validates: Requirements 2.1**

  - [ ]* 7.7 Write property test for Property 6: cascade delete removes all organization users
    - **Property 6: Cascade delete removes all organization users**
    - Generate an organization with a random number of users (1–20)
    - Delete the organization and assert no `users` rows remain with that `org_id`
    - **Validates: Requirements 2.2**

- [x] 8. Final checkpoint — ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Run `php artisan migrate:fresh` after creating both migrations to verify schema integrity
- Eris must be installed before running property tests: `composer require --dev giorgiosironi/eris`
- Property tests reference properties defined in `design.md` under "Correctness Properties"
- The `DB::transaction` in the controller is the sole enforcement point for atomicity (Property 3)
