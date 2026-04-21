<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ __('Employee Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white rounded-xl shadow p-6">
                <p class="text-gray-700 text-lg">
                    Welcome back, <span class="font-semibold text-indigo-600">{{ Auth::user()->name }}</span>!
                </p>
            </div>

            {{-- Leave counts --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 shadow-sm flex items-center space-x-4">
                    <div>
                        <p class="text-yellow-700 text-sm font-medium">Pending Leaves</p>
                        <p class="text-yellow-900 text-3xl font-bold">{{ $counts['pending'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="bg-green-50 border border-green-200 rounded-xl p-6 shadow-sm flex items-center space-x-4">
                    <div>
                        <p class="text-green-700 text-sm font-medium">Approved Leaves</p>
                        <p class="text-green-900 text-3xl font-bold">{{ $counts['approved'] ?? 0 }}</p>
                    </div>
                </div>

                <div class="bg-red-50 border border-red-200 rounded-xl p-6 shadow-sm flex items-center space-x-4">
                    <div>
                        <p class="text-red-700 text-sm font-medium">Rejected Leaves</p>
                        <p class="text-red-900 text-3xl font-bold">{{ $counts['rejected'] ?? 0 }}</p>
                    </div>
                </div>

            </div>

            @if (auth()->user()->hasPermissionTo('apply-leave'))
                <div class="flex">
                    <a href="{{ route('leave-requests.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                        View My Leave Requests
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
