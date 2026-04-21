<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'company_name' => 'Test Company',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_creates_organization_and_links_user(): void
    {
        $this->post('/register', [
            'company_name' => 'Acme Corp',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseCount('organizations', 1);
        $this->assertDatabaseCount('users', 1);

        $org = Organization::first();
        $user = User::first();

        $this->assertEquals($org->id, $user->org_id);
        $this->assertEquals('admin', $user->role);
    }

    public function test_registration_requires_company_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('company_name');
        $this->assertDatabaseCount('organizations', 0);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_whitespace_only_company_name(): void
    {
        $response = $this->post('/register', [
            'company_name' => '   ',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('company_name');
    }

    public function test_registration_accepts_company_name_of_255_characters(): void
    {
        $response = $this->post('/register', [
            'company_name' => str_repeat('a', 255),
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionDoesntHaveErrors('company_name');
        $this->assertDatabaseCount('organizations', 1);
    }

    public function test_registration_rejects_company_name_of_256_characters(): void
    {
        $response = $this->post('/register', [
            'company_name' => str_repeat('a', 256),
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('company_name');
    }

    public function test_registration_with_duplicate_email_does_not_create_organization(): void
    {
        $existingUser = User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->post('/register', [
            'company_name' => 'New Corp',
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Only the organization from the factory setup (none, since factory doesn't create org by default)
        // The existing user was created via factory; no new org should be created
        $this->assertDatabaseCount('organizations', 1);
        $response->assertSessionHasErrors('email');
    }

    public function test_register_form_renders_company_name_field(): void
    {
        $response = $this->get('/register');

        $response->assertSee('company_name');
    }
}
