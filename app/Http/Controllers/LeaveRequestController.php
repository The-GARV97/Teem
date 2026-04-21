<?php

namespace App\Http\Controllers;

use App\Helpers\WorkingDaysHelper;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(private LeaveBalanceService $balanceService) {}

    public function index(): View
    {
        $user = auth()->user();

        if ($user->hasPermissionTo('approve-leave')) {
            $requests = LeaveRequest::with(['employee', 'leaveType'])
                ->orderByDesc('created_at')
                ->get();
        } else {
            $employee = Employee::where('user_id', $user->id)->firstOrFail();
            $requests = LeaveRequest::with(['leaveType'])
                ->where('employee_id', $employee->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('leave-requests.index', compact('requests'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $leaveTypes = LeaveType::orderBy('name')->get();
        $year = now()->year;

        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $balances = [];
        foreach ($leaveTypes as $leaveType) {
            $balance = $this->balanceService->getOrInit($user->org_id, $employee->id, $leaveType->id, $year);
            $balances[$leaveType->id] = $balance->used_days;
        }

        return view('leave-requests.create', compact('leaveTypes', 'balances'));
    }

    public function store(StoreLeaveRequestRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validated();
        $startDate = \Carbon\Carbon::parse($validated['start_date']);
        $endDate   = \Carbon\Carbon::parse($validated['end_date']);

        $totalDays = WorkingDaysHelper::count($startDate, $endDate);

        if ($totalDays === 0) {
            return back()->withInput()->withErrors([
                'start_date' => 'Selected dates contain no working days.',
            ]);
        }

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $year = $startDate->year;

        if (!$this->balanceService->hasSufficientBalance($user->org_id, $employee->id, $leaveType, $year, $totalDays)) {
            return back()->withInput()->withErrors([
                'leave_type_id' => 'Insufficient leave balance for the selected leave type.',
            ]);
        }

        $leaveRequest = LeaveRequest::create([
            'org_id'        => $user->org_id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => $validated['start_date'],
            'end_date'      => $validated['end_date'],
            'total_days'    => $totalDays,
            'reason'        => $validated['reason'],
            'status'        => 'pending',
        ]);

        $reviewers = \App\Models\User::where('org_id', $user->org_id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['Admin', 'Manager', 'SuperAdmin']))
            ->get();
        \Illuminate\Support\Facades\Notification::send($reviewers, new \App\Notifications\LeaveRequestSubmitted($leaveRequest->load('employee', 'leaveType')));

        return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted.');
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be approved.']);
        }

        $leaveRequest->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $year = $leaveRequest->start_date->year;
        $this->balanceService->increment(
            $leaveRequest->org_id,
            $leaveRequest->employee_id,
            $leaveRequest->leave_type_id,
            $year,
            $leaveRequest->total_days
        );

        $employeeUser = $leaveRequest->employee->user;
        if ($employeeUser) {
            $employeeUser->notify(new \App\Notifications\LeaveRequestReviewed($leaveRequest, 'approved'));
        }

        return back()->with('success', 'Leave request approved.');
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'Only pending requests can be rejected.']);
        }

        $leaveRequest->update([
            'status'           => 'rejected',
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $employeeUser = $leaveRequest->employee->user;
        if ($employeeUser) {
            $employeeUser->notify(new \App\Notifications\LeaveRequestReviewed($leaveRequest, 'rejected', $request->input('rejection_reason')));
        }

        return back()->with('success', 'Leave request rejected.');
    }
}
