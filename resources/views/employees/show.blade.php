<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ $employee->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Card --}}
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Employee Details</h3>
                    <div class="flex gap-2">
                        @can('update', $employee)
                            <a href="{{ route('employees.edit', $employee) }}"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                                Edit
                            </a>
                        @endcan
                        @can('delete', $employee)
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                onsubmit="return confirm('Delete this employee?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition ease-in-out duration-150">
                                    Delete
                                </button>
                            </form>
                        @endcan
                    </div>
                </div>

                <dl class="divide-y divide-gray-100">
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $employee->name }}</dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $employee->email }}</dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $employee->phone ?? '—' }}</dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $employee->department->name ?? '—' }}</dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Designation</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $employee->designation->name ?? '—' }}</dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Manager</dt>
                        <dd class="text-sm text-gray-900 col-span-2">
                            @if ($employee->manager)
                                <a href="{{ route('employees.show', $employee->manager) }}"
                                    class="text-indigo-600 hover:text-indigo-900">
                                    {{ $employee->manager->name }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Joining Date</dt>
                        <dd class="text-sm text-gray-900 col-span-2">
                            {{ $employee->joining_date ? \Carbon\Carbon::parse($employee->joining_date)->format('d M Y') : '—' }}
                        </dd>
                    </div>
                    <div class="px-6 py-4 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="col-span-2">
                            <x-status-badge :status="$employee->status" />
                        </dd>
                    </div>
                </dl>
            </div>

            <div>
                <a href="{{ route('employees.index') }}"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                    &larr; Back to Employees
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
