@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6">
    <div class="bg-white rounded shadow p-6">
        <h1 class="text-xl font-semibold mb-4">New Article</h1>
        <form method="POST" action="{{ route('articles.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium">Title</label>
                <input name="title" type="text" value="{{ old('title') }}" class="mt-1 block w-full rounded border px-3 py-2" required />
                @error('title')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Category</label>
                <select name="category" class="mt-1 block w-full rounded border px-3 py-2" required>
                    @foreach (['Hardware','Software','Network','Accounts'] as $c)
                        <option value="{{ $c }}" @selected(old('category')===$c)>{{ $c }}</option>
                    @endforeach
                </select>
                @error('category')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Content (Markdown)</label>
                <textarea name="content" rows="14" class="mt-1 block w-full rounded border px-3 py-2" required>{{ old('content') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">Supports Markdown syntax.</p>
                @error('content')<div class="text-sm text-red-600 mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="flex items-center gap-2">
                <button class="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
                <a href="{{ route('articles.index') }}" class="px-4 py-2 rounded border">Cancel</a>
            </div>
        </form>
    </div>
    </div>
@endsection



