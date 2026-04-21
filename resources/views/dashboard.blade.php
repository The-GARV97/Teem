<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome message --}}
            <div class="bg-white rounded-xl shadow p-6">
                <p class="text-gray-700 text-lg">
                    Welcome back, <span class="font-semibold text-indigo-600">{{ Auth::user()->name }}</span>!
                    You are managing
                    <span class="font-semibold text-indigo-600">{{ Auth::user()->organization->name ?? 'your organization' }}</span>.
                </p>
            </div>

            {{-- Stats grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

                {{-- Total Employees --}}
                <div class="bg-indigo-600 rounded-xl p-6 shadow-lg flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-sm">Total Employees</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['total_employees'] }}</p>
                    </div>
                </div>

                {{-- Pending Leaves --}}
                <div class="bg-indigo-600 rounded-xl p-6 shadow-lg flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-sm">Pending Leaves</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['pending_leaves'] }}</p>
                    </div>
                </div>

                {{-- Approved Leaves --}}
                <div class="bg-indigo-600 rounded-xl p-6 shadow-lg flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-sm">Approved Leaves</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['approved_leaves'] }}</p>
                    </div>
                </div>

                {{-- Rejected Leaves --}}
                <div class="bg-indigo-600 rounded-xl p-6 shadow-lg flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-sm">Rejected Leaves</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['rejected_leaves'] }}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
