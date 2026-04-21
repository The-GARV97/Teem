<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(): View
    {
        $orgId = Auth::user()->org_id;

        // Superadmin has no org — redirect to their own dashboard
        if ($orgId === null) {
            return $this->superadmin();
        }

        $stats = [
            'total_employees'   => Employee::where('org_id', $orgId)->count(),
            'active_employees'  => Employee::where('org_id', $orgId)->where('status', 'active')->count(),
            'total_departments' => \App\Models\Department::where('org_id', $orgId)->count(),
            'pending_leaves'    => $this->leaveCount($orgId, 'pending'),
            'approved_leaves'   => $this->leaveCount($orgId, 'approved'),
            'rejected_leaves'   => $this->leaveCount($orgId, 'rejected'),
        ];

        // Recent pending leave requests
        $pendingRequests = Schema::hasTable('leave_requests')
            ? \App\Models\LeaveRequest::with(['employee', 'leaveType'])
                ->where('org_id', $orgId)
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get()
            : collect();

        // Recent employees
        $recentEmployees = Employee::where('org_id', $orgId)
            ->with(['department', 'designation'])
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('stats', 'pendingRequests', 'recentEmployees'));
    }

    public function employee(): View
    {
        $employee = Employee::where('user_id', auth()->id())->firstOrFail();

        $counts = LeaveRequest::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('employee.dashboard', compact('counts'));
    }

    public function manager(): View
    {
        return view('manager.dashboard');
    }

    public function superadmin(): View
    {
        $stats = [
            'total_organizations' => Organization::count(),
            'total_users'         => User::count(),
            'total_employees'     => Employee::count(),
            'pending_leaves'      => Schema::hasTable('leave_requests')
                ? DB::table('leave_requests')->where('status', 'pending')->count()
                : 0,
            'approved_leaves'     => Schema::hasTable('leave_requests')
                ? DB::table('leave_requests')->where('status', 'approved')->count()
                : 0,
        ];

        // Recent organizations
        $recentOrgs = Organization::withCount('users')->latest()->take(5)->get();

        return view('superadmin.dashboard', compact('stats', 'recentOrgs'));
    }

    private function leaveCount(?int $orgId, string $status): int
    {
        if ($orgId === null || !Schema::hasTable('leave_requests')) {
            return 0;
        }

        return DB::table('leave_requests')
            ->where('org_id', $orgId)
            ->where('status', $status)
            ->count();
    }
}
