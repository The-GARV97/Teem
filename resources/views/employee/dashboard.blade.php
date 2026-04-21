<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ __('Employee Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow p-6">
                <p class="text-gray-700 text-lg">
                    Welcome back, <span class="font-semibold text-indigo-600">{{ Auth::user()->name }}</span>!
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
