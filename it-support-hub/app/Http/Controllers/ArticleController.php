<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use League\CommonMark\CommonMarkConverter;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['create','store','edit','update','destroy']);
    }

    public function index(Request $request)
    {
        $query = Article::query();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }

        $articles = $query->latest()->paginate(10)->withQueryString();

        return view('articles.index', compact('articles'));
    }

    public function create()
    {
        Gate::authorize('manage-articles');
        return view('articles.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-articles');

        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'category' => ['required','in:Hardware,Software,Network,Accounts'],
            'content' => ['required','string'],
        ]);

        Article::create($data);
        return redirect()->route('articles.index')->with('status','Article created');
    }

    public function show(Article $article)
    {
        $converter = new CommonMarkConverter();
        $html = $converter->convert($article->content)->getContent();
        return view('articles.show', compact('article','html'));
    }

    public function edit(Article $article)
    {
        Gate::authorize('manage-articles');
        return view('articles.edit', compact('article'));
    }

    public function update(Request $request, Article $article)
    {
        Gate::authorize('manage-articles');

        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'category' => ['required','in:Hardware,Software,Network,Accounts'],
            'content' => ['required','string'],
        ]);

        $article->update($data);
        return redirect()->route('articles.show', $article)->with('status','Article updated');
    }

    public function destroy(Article $article)
    {
        Gate::authorize('manage-articles');
        $article->delete();
        return redirect()->route('articles.index')->with('status','Article deleted');
    }
}



