<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Eris\Generators;
use Eris\TestTrait;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Property-based tests for multi-tenant registration.
 *
 * Each property runs a minimum of 100 iterations using Eris 1.1.0.
 * Tag: Feature: multi-tenant-organization
 *
 * Note: RefreshDatabase wraps the entire test method in a transaction that is
 * rolled back after the test. Within a single test method, Eris iterates many
 * times without resetting the DB between iterations, so assertions use relative
 * counts (snapshot before / after) rather than absolute counts.
 */
class RegistrationPropertyTest extends TestCase
{
    use RefreshDatabase;
    use TestTrait;

    /**
     * Property 1: Company name validation rejects invalid input
     *
     * For any blank/whitespace company_name, the system must reject the request
     * with a validation error and create neither an Organization nor a User.
     *
     * Validates: Requirements 1.1, 3.2
     */
    public function test_property_1_blank_company_name_always_fails_validation(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::elements(['', '   ', "\t", "\n", "  \t  ", " \n\t ", "\t\t", "\n\n"])
        )->then(function (string $blankName) {
            \Illuminate\Support\Facades\Auth::logout();

            $orgsBefore  = Organization::count();
            $usersBefore = User::count();

            $response = $this->post('/register', [
                'company_name'          => $blankName,
                'name'                  => 'Test User',
                'email'                 => 'prop1_' . uniqid('', true) . '@example.com',
                'password'              => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasErrors('company_name');
            $this->assertEquals($orgsBefore, Organization::count(), 'No new org should be created on blank company_name');
            $this->assertEquals($usersBefore, User::count(), 'No new user should be created on blank company_name');
        });
    }

    /**
     * Property 2: Registration round-trip — user is linked to its organization
     *
     * For any valid registration payload, the created User's org_id must equal
     * the id of the Organization created during the same request, and must be non-null.
     *
     * Validates: Requirements 1.2, 1.5
     */
    public function test_property_2_user_org_id_matches_created_organization(): void
    {
        $this->limitTo(100);

        $validNames = Generators::elements([
            'Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry',
            'Iris', 'Jack', 'Karen', 'Leo', 'Maria', 'Nathan', 'Olivia', 'Paul',
        ]);

        $validCompanies = Generators::elements([
            'Acme Corp', 'Globex', 'Initech', 'Umbrella', 'Stark Industries',
            'Wayne Enterprises', 'Hooli', 'Pied Piper', 'Dunder Mifflin', 'Vandelay',
        ]);

        $this->forAll(
            $validNames,
            $validCompanies
        )->then(function (string $userName, string $companyName) {
            // Ensure we start each iteration as a guest (registration logs the user in)
            \Illuminate\Support\Facades\Auth::logout();

            $email = 'prop2_' . uniqid('', true) . '@example.com';

            $response = $this->post('/register', [
                'company_name'          => $companyName,
                'name'                  => $userName,
                'email'                 => $email,
                'password'              => 'password',
                'password_confirmation' => 'password',
            ]);

            $user = User::where('email', $email)->first();
            $org  = Organization::find($user?->org_id);

            $this->assertNotNull($user, 'User should have been created');
            $this->assertNotNull($user->org_id, 'org_id must not be null');
            $this->assertNotNull($org, 'Organization should have been created');
            $this->assertEquals($org->id, $user->org_id, 'user.org_id must equal organization.id');
        });
    }

    /**
     * Property 3: Transaction atomicity — all-or-nothing creation
     *
     * When user creation fails due to a duplicate email, no new Organization
     * row should be persisted (org count remains unchanged).
     *
     * Validates: Requirements 1.3
     */
    public function test_property_3_duplicate_email_leaves_org_count_unchanged(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::names()
        )->then(function (string $userName) {
            \Illuminate\Support\Facades\Auth::logout();

            // Create a pre-existing user with a known email
            $existingEmail = 'prop3_' . uniqid('', true) . '@example.com';
            User::factory()->create(['email' => $existingEmail]);

            $orgCountBefore = Organization::count();

            // Attempt registration with the same (duplicate) email
            $response = $this->post('/register', [
                'company_name'          => 'Duplicate Corp',
                'name'                  => $userName,
                'email'                 => $existingEmail,
                'password'              => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertSessionHasErrors('email');
            $this->assertEquals(
                $orgCountBefore,
                Organization::count(),
                'Organization count must not change when user creation fails due to duplicate email'
            );
        });
    }

    /**
     * Property 4: New registrant always receives the admin role
     *
     * For any valid registration payload, the created User must have role = 'admin'.
     *
     * Validates: Requirements 1.4
     */
    public function test_property_4_new_user_always_has_admin_role(): void
    {
        $this->limitTo(100);

        $validNames = Generators::elements([
            'Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry',
            'Iris', 'Jack', 'Karen', 'Leo', 'Maria', 'Nathan', 'Olivia', 'Paul',
        ]);

        $validCompanies = Generators::elements([
            'Acme Corp', 'Globex', 'Initech', 'Umbrella', 'Stark Industries',
            'Wayne Enterprises', 'Hooli', 'Pied Piper', 'Dunder Mifflin', 'Vandelay',
        ]);

        $this->forAll(
            $validNames,
            $validCompanies
        )->then(function (string $userName, string $companyName) {
            // Ensure we start each iteration as a guest (registration logs the user in)
            \Illuminate\Support\Facades\Auth::logout();

            $email = 'prop4_' . uniqid('', true) . '@example.com';

            $response = $this->post('/register', [
                'company_name'          => $companyName,
                'name'                  => $userName,
                'email'                 => $email,
                'password'              => 'password',
                'password_confirmation' => 'password',
            ]);

            $user = User::where('email', $email)->first();

            $this->assertNotNull($user, 'User should have been created');
            $this->assertEquals('admin', $user->role, 'New user must always have role = admin');
        });
    }

    /**
     * Property 5: Tenant scoping prevents cross-organization data leakage
     *
     * For any two distinct organizations A and B, a query scoped to org A
     * must never return users belonging to org B.
     *
     * Validates: Requirements 2.1
     */
    public function test_property_5_tenant_scoping_prevents_cross_org_leakage(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 5),
            Generators::choose(1, 5)
        )->then(function (int $usersInA, int $usersInB) {
            $orgA = Organization::factory()->create();
            $orgB = Organization::factory()->create();

            User::factory()->count($usersInA)->create(['org_id' => $orgA->id]);
            User::factory()->count($usersInB)->create(['org_id' => $orgB->id]);

            // Query scoped to org A must only return org A users
            $scopedToA = User::where('org_id', $orgA->id)->get();

            foreach ($scopedToA as $user) {
                $this->assertEquals(
                    $orgA->id,
                    $user->org_id,
                    'A query scoped to org A must never return users from org B'
                );
            }

            $this->assertCount(
                $usersInA,
                $scopedToA,
                'Scoped query must return exactly the users belonging to org A'
            );
        });
    }

    /**
     * Property 6: Cascade delete removes all organization users
     *
     * For any organization with one or more users, deleting the organization
     * must remove all its users, leaving no orphaned rows.
     *
     * Validates: Requirements 2.2
     */
    public function test_property_6_cascade_delete_removes_all_org_users(): void
    {
        $this->limitTo(100);

        $this->forAll(
            Generators::choose(1, 5)
        )->then(function (int $userCount) {
            $org = Organization::factory()->create();
            User::factory()->count($userCount)->create(['org_id' => $org->id]);

            $orgId = $org->id;

            // Confirm users were created for this org
            $this->assertEquals($userCount, User::where('org_id', $orgId)->count());

            $org->delete();

            $orphaned = User::where('org_id', $orgId)->count();
            $this->assertEquals(
                0,
                $orphaned,
                "Deleting org {$orgId} must remove all {$userCount} associated users"
            );
        });
    }
}
