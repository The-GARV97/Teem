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

        $stats = [
            'total_employees' => User::where('org_id', $orgId)->count(),
            'pending_leaves'  => $this->leaveCount($orgId, 'pending'),
            'approved_leaves' => $this->leaveCount($orgId, 'approved'),
            'rejected_leaves' => $this->leaveCount($orgId, 'rejected'),
        ];

        return view('dashboard', compact('stats'));
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
        ];

        return view('superadmin.dashboard', compact('stats'));
    }

    private function leaveCount(int $orgId, string $status): int
    {
        if (!Schema::hasTable('leave_requests')) {
            return 0;
        }

        return DB::table('leave_requests')
            ->where('org_id', $orgId)
            ->where('status', $status)
            ->count();
    }
}
