<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $this->syncSpatieRole($user);

        return redirect($this->redirectForRole($user));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Sync the user's legacy role string to a Spatie role if not already assigned.
     */
    private function syncSpatieRole(User $user): void
    {
        if ($user->roles()->exists()) {
            return;
        }

        $map = [
            'superadmin' => 'SuperAdmin',
            'admin'      => 'Admin',
            'manager'    => 'Manager',
            'member'     => 'Employee',
        ];

        $spatieName = $map[$user->role] ?? null;

        if ($spatieName !== null) {
            $user->assignRole($spatieName);
        }
    }

    /**
     * Determine the redirect URL based on the user's role.
     */
    private function redirectForRole(User $user): string
    {
        return match (true) {
            $user->role === 'superadmin'    => '/superadmin/dashboard',
            $user->hasRole('Admin')         => '/dashboard',
            $user->hasRole('Manager')       => '/manager/dashboard',
            $user->hasRole('Employee')      => '/employee/dashboard',
            default                         => '/dashboard',
        };
    }
}
