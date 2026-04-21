<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Employee::with(['department', 'designation'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }

        $employees   = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function show(Employee $employee): View
    {
        $employee->load(['manager', 'department', 'designation']);

        return view('employees.show', compact('employee'));
    }

    public function create(): View
    {
        $departments  = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();
        $managers     = Employee::active()->orderBy('name')->get();

        return view('employees.create', compact('departments', 'designations', 'managers'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        Employee::create(array_merge(
            $request->validated(),
            ['org_id' => auth()->user()->org_id]
        ));

        return redirect()->route('employees.index')->with('success', 'Employee created.');
    }

    public function edit(Employee $employee): View
    {
        $departments  = Department::orderBy('name')->get();
        $designations = Designation::orderBy('name')->get();
        $managers     = Employee::active()->where('id', '!=', $employee->id)->orderBy('name')->get();

        return view('employees.edit', compact('employee', 'departments', 'designations', 'managers'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee deleted.');
    }
}
