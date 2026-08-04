@extends('layouts.app')

@section('title', __('menu.notifications') . ' | ' . __('auth.system_title'))
@section('page_title', __('menu.notifications'))

@section('content')
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
                <button type="button" id="mark-all-read-btn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-semibold shadow-md transition-all">
                    <i data-lucide="check-circle" class="w-4 h-4"></i>
                    {{ __('pages.mark_all_as_read') }}
                </button>
            @endif
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                <div class="notification-item group relative p-5 hover:bg-slate-50 transition-all {{ !$notification->is_read ? 'bg-blue-50/30 border-l-4 border-primary-500' : '' }}"
                     data-notification-id="{{ $notification->id }}"
                     data-is-read="{{ $notification->is_read ? 'true' : 'false' }}">
                    <div class="flex items-start gap-4">
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
                                            {{ $notification->created_at->format(app()->getLocale() === 'ja' ? 'Y年m月d日 H:i' : 'Y-m-d H:i') }}
                                        </span>
                                        @if($notification->read_at)
                                            <span class="flex items-center gap-1.5 read-at-time">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                {{ __('pages.read_at') }}: {{ $notification->read_at->format(app()->getLocale() === 'ja' ? 'Y年m月d日 H:i' : 'Y-m-d H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 flex-shrink-0">
                                    @if(!$notification->is_read)
                                        <button type="button"
                                                data-mark-read="{{ $notification->id }}"
                                                class="notification-read-btn inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-primary-50 hover:bg-primary-100 text-primary-700 hover:text-primary-800 border border-primary-200 hover:border-primary-300 text-[13px] font-medium transition-all shadow-sm">
                                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            <span>{{ __('pages.mark_as_read') }}</span>
                                        </button>
                                    @endif
                                    @if($notification->link)
                                        <a href="{{ $notification->link }}"
                                           data-mark-read-link="{{ $notification->id }}"
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
(function () {
    const routes = {
        read: @json(url('/notifications')),
        readAll: @json(route('notifications.read_all')),
    };
    const i18n = {
        processing: @json(__('pages.processing') . '...'),
        markAsRead: @json(__('pages.mark_as_read')),
        markAllAsRead: @json(__('pages.mark_all_as_read')),
        marked: @json(__('pages.notification_marked_as_read')),
        markedAll: @json(__('pages.all_notifications_marked_as_read')),
        errorOne: @json(__('pages.error_marking_notification')),
        errorAll: @json(__('pages.error_marking_notifications')),
        readAt: @json(__('pages.read_at')),
        newLabel: @json(__('pages.new')),
    };

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    function refreshIcons() {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function toast(message, type) {
        if (window.showToast) window.showToast(message, type);
    }

    function postJson(url) {
        const token = csrfToken();
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (token) {
            headers['X-CSRF-TOKEN'] = token;
            headers['X-XSRF-TOKEN'] = token;
        }
        return fetch(url, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
        }).then(async (response) => {
            let data = null;
            try { data = await response.json(); } catch (e) { /* ignore */ }
            if (!response.ok) {
                throw new Error((data && data.message) || ('HTTP ' + response.status));
            }
            return data || { success: true };
        });
    }

    function markItemReadUi(item) {
        if (!item) return;
        item.classList.remove('bg-blue-50/30', 'border-l-4', 'border-primary-500');
        item.dataset.isRead = 'true';

        const iconContainer = item.querySelector('.flex-shrink-0 .w-10.h-10');
        if (iconContainer) {
            iconContainer.className = 'w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center';
            iconContainer.innerHTML = '<i data-lucide="bell" class="w-5 h-5 text-slate-400"></i>';
        }

        item.querySelectorAll('.bg-primary-100.text-primary-700').forEach((badge) => {
            if ((badge.textContent || '').trim() === i18n.newLabel) badge.remove();
        });

        const readBtn = item.querySelector('.notification-read-btn');
        if (readBtn) readBtn.remove();

        const timeContainer = item.querySelector('.flex.items-center.gap-4');
        if (timeContainer && !timeContainer.querySelector('.read-at-time')) {
            const now = new Date();
            const timeStr = now.toLocaleString(document.documentElement.lang || undefined, {
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit',
            });
            const readAtSpan = document.createElement('span');
            readAtSpan.className = 'flex items-center gap-1.5 read-at-time text-emerald-600';
            readAtSpan.innerHTML = '<i data-lucide="check-circle" class="w-3.5 h-3.5"></i> '
                + i18n.readAt + ': ' + timeStr;
            timeContainer.appendChild(readAtSpan);
        }
    }

    function updateNotificationStats() {
        const unreadCount = document.querySelectorAll('.notification-item[data-is-read="false"]').length;
        const totalCount = document.querySelectorAll('.notification-item').length;
        const readCount = totalCount - unreadCount;
        const unreadLabel = @json(__('pages.unread_notifications'));
        const readLabel = @json(__('pages.read_notifications'));
        document.querySelectorAll('.ui-stat-card, [class*="stat"]').forEach((card) => {
            const label = (card.textContent || '');
            const num = card.querySelector('.text-2xl, .text-3xl, [class*="font-bold"]');
            if (!num) return;
            if (label.includes(unreadLabel)) num.textContent = String(unreadCount);
            if (label.includes(readLabel)) num.textContent = String(readCount);
        });

        if (unreadCount === 0) {
            const markAllBtn = document.getElementById('mark-all-read-btn');
            if (markAllBtn) markAllBtn.remove();
        }

        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (unreadCount <= 0) badge.remove();
            else badge.textContent = unreadCount > 9 ? '9+' : String(unreadCount);
        }
    }

    function markAsRead(notificationId, buttonElement, isLinkClick) {
        const item = document.querySelector('[data-notification-id="' + notificationId + '"]');
        if (item && item.dataset.isRead === 'true') return Promise.resolve();

        if (buttonElement) {
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i><span>' + i18n.processing + '</span>';
            refreshIcons();
        }

        return postJson(routes.read + '/' + notificationId + '/read')
            .then((data) => {
                if (!data.success) throw new Error('Mark read failed');
                markItemReadUi(item);
                updateNotificationStats();
                if (!isLinkClick) toast(i18n.marked, 'success');
                refreshIcons();
            })
            .catch((error) => {
                console.error('Error marking notification as read:', error);
                if (buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i><span>' + i18n.markAsRead + '</span>';
                    refreshIcons();
                }
                if (!isLinkClick) toast(i18n.errorOne, 'error');
            });
    }

    function markAllAsRead() {
        const btn = document.getElementById('mark-all-read-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> ' + i18n.processing;
            refreshIcons();
        }

        return postJson(routes.readAll)
            .then((data) => {
                if (!data.success) throw new Error('Mark all failed');
                document.querySelectorAll('.notification-item[data-is-read="false"]').forEach(markItemReadUi);
                updateNotificationStats();
                if (btn) btn.remove();
                toast(i18n.markedAll, 'success');
                refreshIcons();
            })
            .catch((error) => {
                console.error('Error marking all notifications as read:', error);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i> ' + i18n.markAllAsRead;
                    refreshIcons();
                }
                toast(i18n.errorAll, 'error');
            });
    }

    document.addEventListener('DOMContentLoaded', () => {
        refreshIcons();

        const markAllBtn = document.getElementById('mark-all-read-btn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', (e) => {
                e.preventDefault();
                markAllAsRead();
            });
        }

        document.querySelectorAll('[data-mark-read]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                markAsRead(btn.getAttribute('data-mark-read'), btn, false);
            });
        });

        document.querySelectorAll('[data-mark-read-link]').forEach((link) => {
            link.addEventListener('click', () => {
                // Fire-and-forget so navigation still happens
                markAsRead(link.getAttribute('data-mark-read-link'), null, true);
            });
        });
    });

    // Keep globals for any leftover inline callers
    window.markAsRead = markAsRead;
    window.markAllAsRead = markAllAsRead;
})();
</script>
@endpush
