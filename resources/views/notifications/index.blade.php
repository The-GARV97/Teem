<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-xl overflow-hidden divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <div class="flex items-start gap-4 px-6 py-4 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50' }}">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-800">{{ $notification->data['message'] ?? 'Notification' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($notification->read_at)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Read</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">Unread</span>
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-indigo-600 hover:underline">Mark read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-gray-400">
                        No notifications yet.
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
