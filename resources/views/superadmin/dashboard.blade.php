<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-indigo-600 leading-tight">Platform Dashboard — Teem</h2>
            <span class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome --}}
            <div class="bg-gradient-to-r from-indigo-700 to-purple-800 rounded-xl shadow p-6 text-white">
                <p class="text-indigo-200 text-sm">Platform Administrator</p>
                <h3 class="text-2xl font-bold mt-1">{{ Auth::user()->name }}</h3>
                <p class="text-indigo-200 mt-1">Full platform access — all organizations visible</p>
            </div>

            {{-- Stats grid --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach ([
                    ['label' => 'Organizations', 'value' => $stats['total_organizations'], 'color' => 'bg-indigo-600', 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21'],
                    ['label' => 'Total Users', 'value' => $stats['total_users'], 'color' => 'bg-purple-600', 'icon' => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                    ['label' => 'Total Employees', 'value' => $stats['total_employees'], 'color' => 'bg-teal-600', 'icon' => 'M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z'],
                    ['label' => 'Pending Leaves', 'value' => $stats['pending_leaves'], 'color' => 'bg-yellow-500', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ['label' => 'Approved Leaves', 'value' => $stats['approved_leaves'], 'color' => 'bg-green-600', 'icon' => 'm4.5 12.75 6 6 9-13.5'],
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

            {{-- Recent Organizations --}}
            <div class="bg-white rounded-xl shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-800">Recent Organizations</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organization</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($recentOrgs as $org)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $org->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $org->users_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $org->created_at->format('M j, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="px-6 py-6 text-center text-sm text-gray-400">No organizations yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white rounded-xl shadow p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0Z"/></svg>
                        All Employees
                    </a>
                    <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 text-white text-sm rounded-lg hover:bg-yellow-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Leave Requests
                    </a>
                    <a href="{{ route('departments.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                        Departments
                    </a>
                    <a href="{{ route('leave-types.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Leave Types
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
