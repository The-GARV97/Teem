<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDesignationRequest;
use App\Http\Requests\UpdateDesignationRequest;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function index(): View
    {
        return view('designations.index', [
            'designations' => Designation::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('designations.create');
    }

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        Designation::create(array_merge(
            $request->validated(),
            ['org_id' => auth()->user()->org_id]
        ));

        return redirect()->route('designations.index')->with('success', 'Designation created.');
    }

    public function edit(Designation $designation): View
    {
        return view('designations.edit', compact('designation'));
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $designation->update($request->validated());

        return redirect()->route('designations.index')->with('success', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        if ($designation->employees()->exists()) {
            return back()->withErrors(['designation' => 'Cannot delete: employees are assigned to this designation.']);
        }

        $designation->delete();

        return redirect()->route('designations.index')->with('success', 'Designation deleted.');
    }
}
