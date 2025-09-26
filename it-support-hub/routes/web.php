<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('articles.index');
});

Route::resource('articles', ArticleController::class)->only([
    'index','show','create','store','edit','update','destroy'
]);

require __DIR__.'/auth.php';



