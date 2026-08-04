@extends('layouts.app')

@section('title', __('pages.delivery_locations_management') . ' | ' . config('app.name'))
@section('page_title', __('pages.delivery_locations_management'))

@section('content')
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('pages.delivery_locations_management') }}</h1>
                <p class="text-[14px] text-slate-600 mt-1.5">{{ __('pages.delivery_locations_management_description') }}</p>
            </div>
            <a href="{{ route('customer.masters.delivery-locations.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="plus" class="w-4 h-4"></i>
                {{ __('pages.new_registration') }}
            </a>
        </div>
    </div>

    {{-- Search Section - Right Aligned --}}
    <div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-2 text-slate-600">
            <i data-lucide="map-pin" class="w-5 h-5 text-slate-400"></i>
            <span class="text-[14px] font-medium">{{ __('pages.total') }}: <span class="font-semibold text-slate-900">{{ $locations->total() }}</span></span>
        </div>
        <form method="GET" action="{{ route('customer.masters.delivery-locations') }}" class="flex items-center gap-2">
                <x-per-page-field />
            <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"></i>
                <input type="text" name="q" value="{{ request('q') }}" 
                       placeholder="{{ __('pages.search_delivery_location_placeholder') }}"
                       class="w-64 pl-11 pr-3 py-2.5 rounded-lg border border-slate-300 text-[14px] focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 bg-white placeholder-slate-400 transition-all">
            </div>
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-primary-600 hover:bg-primary-700 text-white text-[14px] font-medium shadow-md transition-all">
                <i data-lucide="search" class="w-4 h-4"></i>
                {{ __('common.search') }}
            </button>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full text-[14px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.delivery_location_name') }}</th>
                        <th class="px-5 py-3.5 text-left font-semibold text-slate-700">{{ __('pages.note') }}</th>
                        <th class="px-5 py-3.5 text-right font-semibold text-slate-700">{{ __('pages.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($locations as $location)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-5 py-4 font-semibold text-slate-900">{{ $location->name }}</td>
                            <td class="px-5 py-4 text-slate-600">{{ $location->note ?? '-' }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('customer.masters.delivery-locations.edit', $location) }}"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 hover:border-slate-400 text-[13px] font-medium transition-all">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                        {{ __('common.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('customer.masters.delivery-locations.destroy', $location) }}" class="inline" data-ui-confirm="{{ __('pages.confirm_delete_delivery_location') }}" data-ui-confirm-title="{{ __('pages.confirm_action_title_delete') }}" data-ui-confirm-tone="danger" data-ui-confirm-ok="{{ __('common.delete') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 hover:border-red-400 text-[13px] font-medium transition-all">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            {{ __('common.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <i data-lucide="map-pin" class="w-12 h-12 text-slate-300"></i>
                                    <p class="text-slate-500 text-[14px]">{{ __('pages.no_delivery_locations_registered') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

            <x-table-pagination :paginator="$locations" />

    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
        setTimeout(() => {
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }, 100);
    }
    
    // Submit on Enter key
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                this.form.submit();
            }
        });
    }
});
</script>
@endpush
@endsection

