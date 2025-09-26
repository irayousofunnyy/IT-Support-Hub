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
            $table->longText('content');
            $table->timestamps();
            $table->index(['title', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};



