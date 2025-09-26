@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">{{ $article->title }}</h1>
        <div class="flex items-center gap-2">
            <span class="text-xs px-2 py-1 rounded bg-gray-100">{{ $article->category }}</span>
            @can('manage-articles')
                <a href="{{ route('articles.edit',$article) }}" class="px-3 py-1 bg-amber-600 text-white rounded">Edit</a>
                <form method="POST" action="{{ route('articles.destroy',$article) }}" onsubmit="return confirm('Delete this article?')">
                    @csrf
                    @method('DELETE')
                    <button class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded shadow p-6 prose max-w-none">
        {!! $html !!}
    </div>
</div>
@endsection



