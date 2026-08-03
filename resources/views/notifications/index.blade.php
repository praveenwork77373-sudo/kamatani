@extends('layouts.app')

@section('title', __('menu.notifications') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.notifications'))

@section('content')
    {{-- Statistics Cards --}}
    @php
        $unreadCount = $notifications->where('is_read', false)->count();
        $totalCount = $notifications->total();
        $readCount = $totalCount - $unreadCount;
    @endphp

    <x-stat-grid :cols="3">
        <x-stat-card tone="blue" icon="bell" :value="$totalCount" :label="__('pages.total_notifications')" />
        <x-stat-card tone="amber" icon="bell-ring" :value="$unreadCount" :label="__('pages.unread_notifications')" />
        <x-stat-card tone="emerald" icon="check-circle" :value="$readCount" :label="__('pages.read_notifications')" />
    </x-stat-grid>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <i data-lucide="bell" class="w-6 h-6 text-primary-600"></i>
                    {{ __('menu.notifications') }}
                </h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.notifications_description') }}</p>
            </div>
            @if($unreadCount > 0)
                <button type="button" id="mark-all-read-btn" onclick="markAllAsRead()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    {{ __('pages.mark_all_as_read') }}
                </button>
            @endif
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                <div class="notification-item group relative p-5 hover:bg-slate-50 transition-all {{ !$notification->is_read ? 'bg-blue-50/30 border-l-4 border-primary-500' : '' }}" 
                     data-notification-id="{{ $notification->id }}"
                     data-is-read="{{ $notification->is_read ? 'true' : 'false' }}">
                    <div class="flex items-start gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 mt-1">
                            @if(!$notification->is_read)
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <i data-lucide="bell-ring" class="w-5 h-5 text-primary-600"></i>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                                    <i data-lucide="bell" class="w-5 h-5 text-slate-400"></i>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-[15px] font-semibold text-slate-900 mb-1.5 flex items-center gap-2">
                                        {{ $notification->title }}
                                        @if(!$notification->is_read)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-100 text-primary-700">
                                                {{ __('pages.new') }}
                                            </span>
                                        @endif
                                    </h3>
                                    <p class="text-[14px] text-slate-600 leading-relaxed mb-3">{{ $notification->message }}</p>
                                    <div class="flex items-center gap-4 text-[12px] text-slate-500">
                                        <span class="flex items-center gap-1.5">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                            {{ $notification->created_at->format('Y年m月d日 H:i') }}
                                        </span>
                                        @if($notification->read_at)
                                            <span class="flex items-center gap-1.5">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.read_at') }}: {{ $notification->read_at->format('Y年m月d日 H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                {{-- Actions --}}
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if(!$notification->is_read)
                                        <button type="button" 
                                                onclick="markAsRead({{ $notification->id }}, this)"
                                                class="notification-read-btn inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 hover:text-primary-800 border border-primary-200 hover:border-primary-300 text-[13px] font-medium transition-all shadow-sm">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            <span>{{ __('pages.mark_as_read') }}</span>
                                        </button>
                                    @endif
                                    @if($notification->link)
                                        <a href="{{ $notification->link }}" 
                                           onclick="markAsRead({{ $notification->id }}, null, true)"
                                           class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 hover:border-slate-300 text-[13px] font-medium transition-all shadow-sm">
                                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                            <span>{{ __('pages.view_details') }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-16 text-center">
                    <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="bell-off" class="w-10 h-10 text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ __('pages.no_notifications') }}</h3>
                    <p class="text-[14px] text-slate-500">{{ __('pages.no_notifications_description') }}</p>
                </div>
            @endforelse
        </div>

        <x-table-pagination :paginator="$notifications" />
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

function markAsRead(notificationId, buttonElement = null, isLinkClick = false) {
    // If already read, don't do anything
    const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
    if (notificationItem && notificationItem.dataset.isRead === 'true') {
        return;
    }
    
    // Disable button and show loading state
    if (buttonElement) {
        buttonElement.disabled = true;
        buttonElement.innerHTML = `
            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            <span>{{ __('pages.processing') }}...</span>
        `;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    fetch(`{{ url('/notifications') }}/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update UI
            if (notificationItem) {
                notificationItem.classList.remove('bg-blue-50/30', 'border-l-4', 'border-primary-500');
                notificationItem.dataset.isRead = 'true';
                
                // Update icon
                const iconContainer = notificationItem.querySelector('.flex-shrink-0 .w-10.h-10');
                if (iconContainer) {
                    iconContainer.className = 'w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center';
                    iconContainer.innerHTML = '<i data-lucide="bell" class="w-5 h-5 text-slate-400"></i>';
                }
                
                // Remove "new" badge
                const newBadge = notificationItem.querySelector('.bg-primary-100.text-primary-700');
                if (newBadge && newBadge.textContent.trim().includes('{{ __('pages.new') }}')) {
                    newBadge.remove();
                }
                
                // Hide read button
                const readBtn = notificationItem.querySelector('.notification-read-btn');
                if (readBtn) {
                    readBtn.remove();
                }
                
                // Update read at timestamp
                const timeContainer = notificationItem.querySelector('.flex.items-center.gap-4');
                if (timeContainer && !timeContainer.querySelector('.read-at-time')) {
                    const now = new Date();
                    const timeStr = now.toLocaleString('ja-JP', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    const readAtSpan = document.createElement('span');
                    readAtSpan.className = 'flex items-center gap-1.5 read-at-time text-emerald-600';
                    readAtSpan.innerHTML = `
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        {{ __('pages.read_at') }}: ${timeStr}
                    `;
                    timeContainer.appendChild(readAtSpan);
                }
            }
            
            // Update statistics
            updateNotificationStats();
            
            // Show success message
            if (window.showToast) {
                window.showToast('{{ __('pages.notification_marked_as_read') }}', 'success');
            }
            
            // Re-initialize icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        
        // Re-enable button on error
        if (buttonElement) {
            buttonElement.disabled = false;
            buttonElement.innerHTML = `
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>{{ __('pages.mark_as_read') }}</span>
            `;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        
        if (window.showToast) {
            window.showToast('{{ __('pages.error_marking_notification') }}', 'error');
        }
    });
}

function markAllAsRead() {
    const btn = document.getElementById('mark-all-read-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `
            <i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i>
            {{ __('pages.processing') }}...
        `;
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
    
    fetch('{{ url('/notifications/read-all') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all notification items
            document.querySelectorAll('.notification-item[data-is-read="false"]').forEach(item => {
                item.classList.remove('bg-blue-50/30', 'border-l-4', 'border-primary-500');
                item.dataset.isRead = 'true';
                
                // Update icon
                const iconContainer = item.querySelector('.flex-shrink-0 .w-10.h-10');
                if (iconContainer) {
                    iconContainer.className = 'w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center';
                    iconContainer.innerHTML = '<i data-lucide="bell" class="w-5 h-5 text-slate-400"></i>';
                }
                
                // Remove "new" badge
                const newBadge = item.querySelector('.bg-primary-100.text-primary-700');
                if (newBadge && newBadge.textContent.trim().includes('{{ __('pages.new') }}')) {
                    newBadge.remove();
                }
                
                // Remove read button
                const readBtn = item.querySelector('.notification-read-btn');
                if (readBtn) {
                    readBtn.remove();
                }
                
                // Add read at timestamp
                const timeContainer = item.querySelector('.flex.items-center.gap-4');
                if (timeContainer && !timeContainer.querySelector('.read-at-time')) {
                    const now = new Date();
                    const timeStr = now.toLocaleString('ja-JP', { 
                        year: 'numeric', 
                        month: '2-digit', 
                        day: '2-digit', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    const readAtSpan = document.createElement('span');
                    readAtSpan.className = 'flex items-center gap-1.5 read-at-time text-emerald-600';
                    readAtSpan.innerHTML = `
                        <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                        {{ __('pages.read_at') }}: ${timeStr}
                    `;
                    timeContainer.appendChild(readAtSpan);
                }
            });
            
            // Update statistics
            updateNotificationStats();
            
            // Hide mark all button
            if (btn) {
                btn.remove();
            }
            
            // Show success message
            if (window.showToast) {
                window.showToast('{{ __('pages.all_notifications_marked_as_read') }}', 'success');
            }
            
            // Re-initialize icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    })
    .catch(error => {
        console.error('Error marking all notifications as read:', error);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = `
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                {{ __('pages.mark_all_as_read') }}
            `;
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
        if (window.showToast) {
            window.showToast('{{ __('pages.error_marking_notifications') }}', 'error');
        }
    });
}

function updateNotificationStats() {
    // Update unread count in statistics card
    const unreadItems = document.querySelectorAll('.notification-item[data-is-read="false"]');
    const unreadCount = unreadItems.length;
    const totalCount = document.querySelectorAll('.notification-item').length;
    const readCount = totalCount - unreadCount;
    
    // Update unread card
    const unreadCard = document.querySelector('.from-amber-50');
    if (unreadCard) {
        const countElement = unreadCard.querySelector('.text-2xl');
        if (countElement) {
            countElement.textContent = unreadCount.toLocaleString();
        }
    }
    
    // Update read card
    const readCard = document.querySelector('.from-green-50');
    if (readCard) {
        const countElement = readCard.querySelector('.text-2xl');
        if (countElement) {
            countElement.textContent = readCount.toLocaleString();
        }
    }
    
    // Hide mark all button if no unread notifications
    if (unreadCount === 0) {
        const markAllBtn = document.getElementById('mark-all-read-btn');
        if (markAllBtn) {
            markAllBtn.remove();
        }
    }
}
</script>
@endpush
