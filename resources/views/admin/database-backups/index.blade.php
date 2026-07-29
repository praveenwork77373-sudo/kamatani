@extends('layouts.app')

@section('title', __('menu.database_management') . ' | ' . config('app.name'))
@section('page_title', __('menu.database_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('menu.database_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.database_backups_description') }}</p>
            </div>
            <form action="{{ route('admin.database-backups.store') }}" method="POST">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all"
                        onclick="return confirm(@json(__('pages.backup_create_confirm')))">
                    <i data-lucide="hard-drive-download" class="w-4 h-4"></i>
                    {{ __('pages.backup_now') }}
                </button>
            </form>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <span class="text-[14px] text-emerald-800 font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                <span class="text-[14px] text-red-800 font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
            <ul class="list-disc list-inside text-[14px] text-red-800">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
        {{-- Schedule settings --}}
        <div class="xl:col-span-1">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-primary-50 to-blue-50">
                    <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="calendar-clock" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.backup_schedule_settings') }}
                    </h2>
                </div>
                <div class="p-4">
                    <form action="{{ route('admin.database-backups.schedule') }}" method="POST" class="space-y-4" id="scheduleForm">
                        @csrf
                        <div class="flex items-center justify-between gap-3 p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div>
                                <p class="text-[14px] font-semibold text-slate-800">{{ __('pages.backup_auto_enabled') }}</p>
                                <p class="text-[12px] text-slate-500 mt-0.5">{{ __('pages.backup_auto_enabled_hint') }}</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enabled" value="1" class="sr-only peer" @checked(old('enabled', $settings->enabled))>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.backup_frequency') }}</label>
                            <select name="frequency" id="frequency"
                                    class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                <option value="daily" @selected(old('frequency', $settings->frequency) === 'daily')>{{ __('pages.backup_freq_daily') }}</option>
                                <option value="weekly" @selected(old('frequency', $settings->frequency) === 'weekly')>{{ __('pages.backup_freq_weekly') }}</option>
                                <option value="monthly" @selected(old('frequency', $settings->frequency) === 'monthly')>{{ __('pages.backup_freq_monthly') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.backup_time') }}</label>
                            <input type="time" name="time" value="{{ old('time', $settings->time ?: '02:00') }}"
                                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white"
                                   required>
                        </div>

                        <div id="dayOfWeekWrap" class="{{ old('frequency', $settings->frequency) === 'weekly' ? '' : 'hidden' }}">
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.backup_day_of_week') }}</label>
                            <select name="day_of_week"
                                    class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                @foreach([
                                    0 => __('pages.weekday_sun'),
                                    1 => __('pages.weekday_mon'),
                                    2 => __('pages.weekday_tue'),
                                    3 => __('pages.weekday_wed'),
                                    4 => __('pages.weekday_thu'),
                                    5 => __('pages.weekday_fri'),
                                    6 => __('pages.weekday_sat'),
                                ] as $dow => $label)
                                    <option value="{{ $dow }}" @selected((int) old('day_of_week', $settings->day_of_week ?? 0) === $dow)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div id="dayOfMonthWrap" class="{{ old('frequency', $settings->frequency) === 'monthly' ? '' : 'hidden' }}">
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.backup_day_of_month') }}</label>
                            <select name="day_of_month"
                                    class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white">
                                @for($d = 1; $d <= 28; $d++)
                                    <option value="{{ $d }}" @selected((int) old('day_of_month', $settings->day_of_month ?? 1) === $d)>{{ $d }}</option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.backup_retain_days') }}</label>
                            <input type="number" name="retain_days" min="1" max="365"
                                   value="{{ old('retain_days', $settings->retain_days) }}"
                                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white"
                                   required>
                            <p class="text-[12px] text-slate-500 mt-1">{{ __('pages.backup_retain_days_hint') }}</p>
                        </div>

                        @if($settings->last_run_at)
                            <p class="text-[12px] text-slate-500 flex items-center gap-1.5">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                {{ __('pages.backup_last_run') }}: {{ $settings->last_run_at->format('Y-m-d H:i') }}
                            </p>
                        @endif

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-[14px] font-medium transition-all">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            {{ __('pages.backup_save_schedule') }}
                        </button>
                    </form>
                    <p class="text-[11px] text-slate-400 mt-3 leading-relaxed">{{ __('pages.backup_scheduler_hint') }}</p>
                </div>
            </div>
        </div>

        {{-- Backup list --}}
        <div class="xl:col-span-2">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-gradient-to-r from-slate-50 to-blue-50">
                    <h2 class="text-[15px] font-semibold text-slate-900 flex items-center gap-2">
                        <i data-lucide="archive" class="w-4 h-4 text-primary-600"></i>
                        {{ __('pages.backup_list') }}
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-[12px] font-semibold text-slate-600 uppercase tracking-wider">{{ __('pages.backup_filename') }}</th>
                                <th class="px-4 py-3 text-left text-[12px] font-semibold text-slate-600 uppercase tracking-wider">{{ __('pages.backup_type') }}</th>
                                <th class="px-4 py-3 text-left text-[12px] font-semibold text-slate-600 uppercase tracking-wider">{{ __('pages.backup_size') }}</th>
                                <th class="px-4 py-3 text-left text-[12px] font-semibold text-slate-600 uppercase tracking-wider">{{ __('pages.backup_status') }}</th>
                                <th class="px-4 py-3 text-left text-[12px] font-semibold text-slate-600 uppercase tracking-wider">{{ __('pages.backup_created_at') }}</th>
                                <th class="px-4 py-3 text-right text-[12px] font-semibold text-slate-600 uppercase tracking-wider">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($backups as $backup)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="text-[13px] font-medium text-slate-900 break-all">{{ $backup->filename }}</div>
                                        @if($backup->error_message)
                                            <div class="text-[11px] text-red-600 mt-1 line-clamp-2">{{ $backup->error_message }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($backup->type === 'scheduled')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 text-[11px] font-semibold">
                                                <i data-lucide="timer" class="w-3 h-3"></i>
                                                {{ __('pages.backup_type_scheduled') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 text-[11px] font-semibold">
                                                <i data-lucide="hand" class="w-3 h-3"></i>
                                                {{ __('pages.backup_type_manual') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[13px] text-slate-700">{{ $backup->humanSize() }}</td>
                                    <td class="px-4 py-3">
                                        @if($backup->status === 'success')
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">{{ __('pages.backup_status_success') }}</span>
                                        @elseif($backup->status === 'failed')
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-[11px] font-semibold">{{ __('pages.backup_status_failed') }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[11px] font-semibold">{{ __('pages.backup_status_pending') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[13px] text-slate-600 whitespace-nowrap">
                                        {{ $backup->created_at?->format('Y-m-d H:i') }}
                                        @if($backup->createdBy)
                                            <div class="text-[11px] text-slate-400">{{ $backup->createdBy->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($backup->status === 'success')
                                                <a href="{{ route('admin.database-backups.download', $backup) }}"
                                                   class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-all"
                                                   title="{{ __('pages.backup_download') }}">
                                                    <i data-lucide="download" class="w-4 h-4"></i>
                                                </a>
                                                <button type="button"
                                                        onclick="openRestoreModal({{ $backup->id }}, @js($backup->filename))"
                                                        class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 transition-all"
                                                        title="{{ __('pages.backup_restore') }}">
                                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                                </button>
                                            @endif
                                            <form action="{{ route('admin.database-backups.destroy', $backup) }}" method="POST"
                                                  onsubmit="return confirm(@json(__('pages.backup_delete_confirm')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center h-8 w-8 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 transition-all"
                                                        title="{{ __('pages.delete') }}">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2 text-slate-400">
                                            <i data-lucide="database-backup" class="w-10 h-10"></i>
                                            <p class="text-[14px]">{{ __('pages.backup_empty') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($backups->hasPages())
                    <div class="px-4 py-3 border-t border-slate-200">
                        {{ $backups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Restore confirmation modal --}}
    <div id="restoreModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/50" onclick="closeRestoreModal()"></div>
        <div class="relative mx-auto mt-24 w-full max-w-md px-4">
            <div class="bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-amber-50">
                    <h3 class="text-[15px] font-semibold text-amber-900 flex items-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        {{ __('pages.backup_restore') }}
                    </h3>
                </div>
                <form id="restoreForm" method="POST" class="p-4 space-y-4">
                    @csrf
                    <p class="text-[13px] text-slate-700 leading-relaxed">{{ __('pages.backup_restore_warning') }}</p>
                    <p class="text-[13px] font-medium text-slate-900" id="restoreFilename"></p>
                    <div>
                        <label class="block text-[13px] font-semibold text-slate-700 mb-2">{{ __('pages.backup_restore_type_restore') }}</label>
                        <input type="text" name="confirm" autocomplete="off" placeholder="RESTORE"
                               class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20"
                               required>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button type="button" onclick="closeRestoreModal()"
                                class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-[14px] hover:bg-slate-50">
                            {{ __('pages.cancel') }}
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-[14px] font-medium">
                            {{ __('pages.backup_restore_execute') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const frequency = document.getElementById('frequency');
        const dayOfWeekWrap = document.getElementById('dayOfWeekWrap');
        const dayOfMonthWrap = document.getElementById('dayOfMonthWrap');

        function syncFrequencyFields() {
            const value = frequency.value;
            dayOfWeekWrap.classList.toggle('hidden', value !== 'weekly');
            dayOfMonthWrap.classList.toggle('hidden', value !== 'monthly');
        }

        if (frequency) {
            frequency.addEventListener('change', syncFrequencyFields);
            syncFrequencyFields();
        }

        window.openRestoreModal = function (id, filename) {
            const modal = document.getElementById('restoreModal');
            const form = document.getElementById('restoreForm');
            document.getElementById('restoreFilename').textContent = filename;
            form.action = `/admin/database-backups/${id}/restore`;
            form.querySelector('[name="confirm"]').value = '';
            modal.classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        };

        window.closeRestoreModal = function () {
            document.getElementById('restoreModal').classList.add('hidden');
        };
    })();
</script>
@endpush
