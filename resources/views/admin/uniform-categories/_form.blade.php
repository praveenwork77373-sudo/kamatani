@php
    $category = $category ?? null;
@endphp
<div>
    <label class="ui-label" for="name">{{ __('pages.name') }} <span class="text-red-500">*</span></label>
    <input id="name" type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required class="ui-input"
           placeholder="{{ __('pages.example_white_coat') }}">
    @error('name')<p class="ui-error">{{ $message }}</p>@enderror
</div>
<div>
    <label class="ui-label" for="size_scheme">{{ __('pages.size_scheme') }} <span class="text-red-500">*</span></label>
    <select id="size_scheme" name="size_scheme" required class="ui-select">
        <option value="letter" @selected(old('size_scheme', $category->size_scheme ?? 'letter') === 'letter')>{{ __('pages.size_scheme_letter') }}</option>
        <option value="shoe" @selected(old('size_scheme', $category->size_scheme ?? '') === 'shoe')>{{ __('pages.size_scheme_shoe') }}</option>
        <option value="none" @selected(old('size_scheme', $category->size_scheme ?? '') === 'none')>{{ __('pages.size_scheme_none') }}</option>
    </select>
    <p class="ui-hint mt-1">{{ __('pages.size_scheme_hint') }}</p>
    @error('size_scheme')<p class="ui-error">{{ $message }}</p>@enderror
</div>
<div>
    <label class="ui-label" for="employee_size_field">{{ __('pages.employee_size_field') }}</label>
    <select id="employee_size_field" name="employee_size_field" class="ui-select">
        <option value="">{{ __('pages.none') }}</option>
        <option value="top_size" @selected(old('employee_size_field', $category->employee_size_field ?? '') === 'top_size')>{{ __('pages.top_size_label') }}</option>
        <option value="bottom_size" @selected(old('employee_size_field', $category->employee_size_field ?? '') === 'bottom_size')>{{ __('pages.bottom_size_label') }}</option>
        <option value="shoe_size" @selected(old('employee_size_field', $category->employee_size_field ?? '') === 'shoe_size')>{{ __('pages.shoe_size_label') }}</option>
    </select>
    <p class="ui-hint mt-1">{{ __('pages.employee_size_field_hint') }}</p>
    @error('employee_size_field')<p class="ui-error">{{ $message }}</p>@enderror
</div>
<div>
    <label class="ui-label" for="sort_order">{{ __('pages.sort_order') }}</label>
    <input id="sort_order" type="number" min="0" max="9999" name="sort_order"
           value="{{ old('sort_order', $category->sort_order ?? 100) }}" class="ui-input">
    @error('sort_order')<p class="ui-error">{{ $message }}</p>@enderror
</div>
<label class="inline-flex items-center gap-2 text-[14px] text-slate-700">
    <input type="checkbox" name="is_active" value="1" class="ui-check" {{ old('is_active', $category->is_active ?? true) ? 'checked' : '' }}>
    有効
</label>
