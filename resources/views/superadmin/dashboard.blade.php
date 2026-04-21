<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ __('Platform Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Welcome message --}}
            <div class="bg-white rounded-xl shadow p-6">
                <p class="text-gray-700 text-lg">
                    Welcome back, <span class="font-semibold text-indigo-600">{{ Auth::user()->name }}</span>!
                    You have full platform access.
                </p>
            </div>

            {{-- Stats grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                {{-- Total Organizations --}}
                <div class="bg-indigo-600 rounded-xl p-6 shadow-lg flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-sm">Total Organizations</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['total_organizations'] }}</p>
                    </div>
                </div>

                {{-- Total Users --}}
                <div class="bg-indigo-600 rounded-xl p-6 shadow-lg flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <svg class="w-10 h-10 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-indigo-200 text-sm">Total Users</p>
                        <p class="text-white text-3xl font-bold">{{ $stats['total_users'] }}</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
