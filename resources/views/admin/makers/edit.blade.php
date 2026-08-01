@extends('layouts.app')

@section('title', 'メーカー編集')
@section('page_title', 'メーカー編集')

@section('content')
<div class="max-w-xl mx-auto sm:mx-0">
    <div class="ui-page-header">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">メーカー編集</h1>
            <p class="text-[13px] text-slate-500 mt-1">メーカーコードと名称を更新します</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.makers.update', $maker) }}" class="ui-card ui-form p-4 sm:p-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="ui-label" for="code">メーカーコード</label>
            <input id="code" type="text" name="code" value="{{ old('code', $maker->code) }}" required class="ui-input">
            @error('code')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="ui-label" for="name">メーカー名</label>
            <input id="name" type="text" name="name" value="{{ old('name', $maker->name) }}" required class="ui-input">
            @error('name')<p class="ui-error">{{ $message }}</p>@enderror
        </div>
        <label class="inline-flex items-center gap-2 text-[14px] text-slate-700">
            <input type="checkbox" name="is_active" value="1" class="ui-check" {{ old('is_active', $maker->is_active) ? 'checked' : '' }}>
            有効
        </label>
        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
            <a href="{{ route('admin.makers.index') }}" class="ui-btn ui-btn-secondary">戻る</a>
            <button type="submit" class="ui-btn ui-btn-primary">更新</button>
        </div>
    </form>
</div>
@endsection
