<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-indigo-600 leading-tight">
            {{ __('Leave Requests') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('status'))
                <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
                    {{ $errors->first('status') }}
                </div>
            @endif

            @if (auth()->user()->hasPermissionTo('apply-leave'))
                <div class="flex justify-end">
                    <a href="{{ route('leave-requests.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
                        Apply for Leave
                    </a>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if (auth()->user()->hasPermissionTo('approve-leave'))
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Leave Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($requests as $leaveRequest)
                            <tr class="hover:bg-gray-50">
                                @if (auth()->user()->hasPermissionTo('approve-leave'))
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $leaveRequest->employee->name ?? '—' }}
                                    </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $leaveRequest->leaveType->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $leaveRequest->start_date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $leaveRequest->end_date->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $leaveRequest->total_days }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                    {{ $leaveRequest->reason }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <x-status-badge :status="$leaveRequest->status" />
                                    @if ($leaveRequest->status === 'rejected' && $leaveRequest->rejection_reason)
                                        <p class="text-xs text-gray-500 mt-1">{{ $leaveRequest->rejection_reason }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-y-2">
                                    @if (auth()->user()->hasPermissionTo('approve-leave') && $leaveRequest->status === 'pending')
                                        <form method="POST" action="{{ route('leave-requests.approve', $leaveRequest) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="text-green-600 hover:text-green-900 font-medium">Approve</button>
                                        </form>

                                        <span class="text-gray-300">|</span>

                                        <details class="inline-block">
                                            <summary class="text-red-600 hover:text-red-900 font-medium cursor-pointer list-none">Reject</summary>
                                            <div class="mt-2 p-3 bg-gray-50 rounded border border-gray-200 min-w-64">
                                                <form method="POST" action="{{ route('leave-requests.reject', $leaveRequest) }}" class="space-y-2">
                                                    @csrf
                                                    <textarea name="rejection_reason" rows="2"
                                                        placeholder="Reason for rejection (optional)"
                                                        class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                                    <button type="submit"
                                                        class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                                        Confirm Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">No leave requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
