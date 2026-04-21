<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-indigo-600 leading-tight">Admin Dashboard</h2>
            <span class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome --}}
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-800 rounded-xl shadow p-6 text-white">
                <p class="text-indigo-200 text-sm">Welcome back</p>
                <h3 class="text-2xl font-bold mt-1">{{ Auth::user()->name }}</h3>
                <p class="text-indigo-200 mt-1">Managing <span class="text-white font-semibold">{{ Auth::user()->organization->name ?? 'your organization' }}</span></p>
            </div>

            {{-- Stats grid --}}
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                @foreach ([
                    ['label' => 'Total Employees', 'value' => $stats['total_employees'], 'color' => 'bg-indigo-600', 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                    ['label' => 'Active', 'value' => $stats['active_employees'], 'color' => 'bg-green-600', 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['label' => 'Departments', 'value' => $stats['total_departments'], 'color' => 'bg-purple-600', 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                    ['label' => 'Pending Leaves', 'value' => $stats['pending_leaves'], 'color' => 'bg-yellow-500', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['label' => 'Approved', 'value' => $stats['approved_leaves'], 'color' => 'bg-teal-600', 'icon' => 'm4.5 12.75 6 6 9-13.5'],
                    ['label' => 'Rejected', 'value' => $stats['rejected_leaves'], 'color' => 'bg-red-500', 'icon' => 'M6 18 18 6M6 6l12 12'],
                ] as $card)
                <div class="{{ $card['color'] }} rounded-xl p-4 shadow flex flex-col gap-2">
                    <svg class="w-6 h-6 text-white opacity-80" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                    </svg>
                    <p class="text-white text-2xl font-bold">{{ $card['value'] }}</p>
                    <p class="text-white text-xs opacity-80">{{ $card['label'] }}</p>
                </div>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Pending Leave Requests --}}
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">Pending Leave Requests</h3>
                        <a href="{{ route('leave-requests.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($pendingRequests as $req)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $req->employee->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $req->leaveType->name ?? '—' }} · {{ $req->total_days }} day(s)</p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('leave-requests.approve', $req) }}">
                                    @csrf
                                    <button class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">Approve</button>
                                </form>
                                <a href="{{ route('leave-requests.index') }}" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded hover:bg-gray-200">View</a>
                            </div>
                        </div>
                        @empty
                        <div class="px-6 py-6 text-center text-sm text-gray-400">No pending requests</div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent Employees --}}
                <div class="bg-white rounded-xl shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-semibold text-gray-800">Recent Employees</h3>
                        <a href="{{ route('employees.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
                    </div>
                    <div class="divide-y divide-gray-100">
                        @forelse($recentEmployees as $emp)
                        <div class="px-6 py-3 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-800">
                                    <a href="{{ route('employees.show', $emp) }}" class="hover:text-indigo-600">{{ $emp->name }}</a>
                                </p>
                                <p class="text-xs text-gray-500">{{ $emp->designation->name ?? '—' }} · {{ $emp->department->name ?? '—' }}</p>
                            </div>
                            <x-status-badge :status="$emp->status" />
                        </div>
                        @empty
                        <div class="px-6 py-6 text-center text-sm text-gray-400">No employees yet</div>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- Quick Links --}}
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Employee
                    </a>
                    <a href="{{ route('departments.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                        Departments
                    </a>
                    <a href="{{ route('leave-types.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Leave Types
                    </a>
                    <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white text-sm rounded-lg hover:bg-yellow-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Leave Requests
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
