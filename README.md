<<<<<<< HEAD
# IT Support Hub — Laravel + MySQL + Tailwind

An internal Knowledge Base (KB) for IT support.

- Users (Staff) can view/search articles.
- IT Admins can create, edit, delete articles.
- Articles: title, category, content (Markdown), timestamps.
- Auth via Laravel Breeze (Blade).
- Roles: `staff` (view/search), `admin` (manage).

---

## 1) Prerequisites

- PHP 8.2+
- Composer
- MySQL 8+ (or MariaDB)
- Node.js 18+ and npm
- Git

Verify:

```bash
php -v
composer -V
mysql --version
node -v
npm -v
```

---

## 2) Create Project and Install Auth

```bash
# Create a new Laravel project
composer create-project laravel/laravel it-support-hub
cd it-support-hub

# Breeze for auth (Blade stack)
composer require laravel/breeze --dev
php artisan breeze:install blade --dark

# Frontend deps and build
npm install
npm run build    # or: npm run dev

# CommonMark Markdown renderer
composer require league/commonmark
```

---

## 3) Configure Database

Create a MySQL database (example):

```sql
CREATE DATABASE it_support_hub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'it_hub'@'%' IDENTIFIED BY 'strongpassword';
GRANT ALL PRIVILEGES ON it_support_hub.* TO 'it_hub'@'%';
FLUSH PRIVILEGES;
```

Copy `.env` from example and configure DB:

```env
APP_NAME="IT Support Hub"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=it_support_hub
DB_USERNAME=it_hub
DB_PASSWORD=strongpassword

SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Generate key:

```bash
php artisan key:generate
```

---

## 4) Add Roles to Users

Update the default users table migration or create a new migration to add a `role` column.

Create migration:

```bash
php artisan make:migration add_role_to_users_table --table=users
```

Migration file contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('staff')->after('email'); // roles: staff, admin
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

Update `app/Models/User.php` to include `role` in fillable:

```php
<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
```

---

## 5) Create Articles Table

```bash
php artisan make:model Article -m
```

Migration `database/migrations/xxxx_xx_xx_xxxxxx_create_articles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['Hardware','Software','Network','Accounts']);
            $table->longText('content'); // Markdown
            $table->timestamps();
            $table->index(['title', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

Model `app/Models/Article.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'category', 'content',
    ];
}
```

Run migrations:

```bash
php artisan migrate
```

---

## 6) Authorization (Gates/Policy)

Use a simple Gate based on `role`.

`app/Providers/AuthServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\\Models\\Model' => 'App\\Policies\\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-articles', function ($user) {
            return $user->role === 'admin';
        });
    }
}
```

Blade helper (optional) to show admin controls:

```blade
@can('manage-articles')
    <!-- admin-only UI -->
@endcan
```

---

## 7) Controller with Search and Markdown Rendering

```bash
php artisan make:controller ArticleController --resource
```

`app/Http/Controllers/ArticleController.php`:

```php
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
```

---

## 8) Routes

`routes/web.php`:

```php
<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () { return redirect()->route('articles.index'); });

Route::resource('articles', ArticleController::class)->only([
    'index','show','create','store','edit','update','destroy'
]);

require __DIR__.'/auth.php';
```

Note: `create/store/edit/update/destroy` are protected by the controller middleware and Gate.

---

## 9) Blade Views (Tailwind + Breeze)

Create Blade files under `resources/views/articles/`.

`resources/views/articles/index.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Knowledge Base
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('articles.index') }}" class="mb-4 flex gap-3">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search articles..."
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" />
                    <select name="category" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">All Categories</option>
                        @foreach (['Hardware','Software','Network','Accounts'] as $c)
                            <option value="{{ $c }}" @selected(request('category')===$c)>{{ $c }}</option>
                        @endforeach
                    </select>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-md">Search</button>
                </form>

                @can('manage-articles')
                    <a href="{{ route('articles.create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md">New Article</a>
                @endcan

                <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($articles as $article)
                        <div class="py-4">
                            <div class="flex items-center justify-between">
                                <a href="{{ route('articles.show',$article) }}" class="text-lg font-semibold text-blue-600 dark:text-blue-400">{{ $article->title }}</a>
                                <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700">{{ $article->category }}</span>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Updated {{ $article->updated_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">No articles found.</p>
                    @endforelse
                </div>

                <div class="mt-4">{{ $articles->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
```

`resources/views/articles/show.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $article->title }}
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-1 rounded bg-gray-100 dark:bg-gray-700">{{ $article->category }}</span>
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
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 prose dark:prose-invert max-w-none">
                {!! $html !!}
            </div>
        </div>
    </div>
</x-app-layout>
```

`resources/views/articles/create.blade.php` and `resources/views/articles/edit.blade.php` (edit pre-fills values):

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($article) ? 'Edit Article' : 'New Article' }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ isset($article) ? route('articles.update',$article) : route('articles.store') }}" class="space-y-4">
                    @csrf
                    @if(isset($article))
                        @method('PUT')
                    @endif

                    <div>
                        <x-input-label for="title" value="Title" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title', $article->title ?? '') }}" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category" value="Category" />
                        <select id="category" name="category" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                            @foreach (['Hardware','Software','Network','Accounts'] as $c)
                                <option value="{{ $c }}" @selected(old('category', $article->category ?? '')===$c)>{{ $c }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="content" value="Content (Markdown)" />
                        <textarea id="content" name="content" rows="14" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>{{ old('content', $article->content ?? '') }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Supports Markdown. Use headings, lists, code blocks, links, etc.</p>
                        <x-input-error :messages="$errors->get('content')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <x-primary-button>{{ isset($article) ? 'Update' : 'Create' }}</x-primary-button>
                        <a href="{{ route('articles.index') }}" class="px-4 py-2 rounded-md border dark:border-gray-700">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
```

In `routes/web.php`, share both create/edit views with the same template by returning `view('articles.create', compact('article'))` when editing.

`app/Providers/AppServiceProvider.php` (optional: Tailwind Typography)

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // You can put global view composers or configs here
    }
}
```

Install Tailwind Typography for nicer article rendering (optional):

```bash
npm install -D @tailwindcss/typography
```

`tailwind.config.js` add plugin:

```js
import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'

export default {
  darkMode: 'class',
  content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Figtree', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [forms, typography],
}
```

Rebuild assets:

```bash
npm run build    # or: npm run dev
```

---

## 10) Seed Admin User and Sample Articles

```bash
php artisan make:seeder AdminUserSeeder
php artisan make:seeder SampleArticlesSeeder
```

`database/seeders/AdminUserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'IT Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );
    }
}
```

`database/seeders/SampleArticlesSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class SampleArticlesSeeder extends Seeder
{
    public function run(): void
    {
        Article::firstOrCreate([
            'title' => 'Resetting Your Password',
        ], [
            'category' => 'Accounts',
            'content' => """## Resetting Your Password

1. Go to the password reset page.
2. Enter your company email.
3. Check your inbox for the reset link.

If you still cannot log in, contact IT support.
""",
        ]);

        Article::firstOrCreate([
            'title' => 'Wi-Fi Troubleshooting Guide',
        ], [
            'category' => 'Network',
            'content' => """## Wi-Fi Troubleshooting

- Ensure airplane mode is off.
- Toggle Wi‑Fi off/on.
- Forget and reconnect to `CorpNet`.
- Reboot your device.

If issues persist, open a ticket with IT.
""",
        ]);
    }
}
```

Register seeders in `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SampleArticlesSeeder::class,
        ]);
    }
}
```

Run seeders:

```bash
php artisan db:seed
```

---

## 11) Run the App

```bash
# Migrate if you haven’t
php artisan migrate

# Serve Laravel
php artisan serve
# http://127.0.0.1:8000

# Build assets (in another terminal)
npm run dev
```

Login with:
- Email: `admin@example.com`
- Password: `password`

You can then create additional users via registration; they default to `staff` role and can only view/search.

---

## 12) Directory Summary (Key Files)

- `app/Models/Article.php`
- `app/Http/Controllers/ArticleController.php`
- `app/Providers/AuthServiceProvider.php`
- `database/migrations/*_create_articles_table.php`
- `database/migrations/*_add_role_to_users_table.php`
- `resources/views/articles/index.blade.php`
- `resources/views/articles/show.blade.php`
- `resources/views/articles/create.blade.php` (also used for edit)
- `routes/web.php`

---

## Notes

- Articles are stored in Markdown and rendered to HTML using `league/commonmark` in the `show` view.
- Search uses a simple `LIKE` on `title` and `content` with category filter and pagination.
- Role-based controls are enforced by Gate `manage-articles` and middleware for auth-only actions.
- Tailwind + Breeze provides a clean, responsive UI with dark mode support.

=======
# IT-Support-Hub
a system where user can learn troubleshooting and IT admin will incahrge in the content management 
>>>>>>> 131f454b6aeb1a51ff0d90a39ac2fa999c138a03
