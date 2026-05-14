<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title');
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            // Encrypted columns are stored as text — Laravel Crypt output is base64 and can
            // easily exceed a varchar limit, so we always use TEXT for ciphertext.
            $table->text('password_encrypted');
            $table->string('url', 2048)->nullable();
            $table->text('notes_encrypted')->nullable();
            $table->text('custom_fields_encrypted')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->json('tags')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category_id');
            $table->index('is_favorite');
            $table->index('title');
            $table->index('last_accessed_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credentials');
    }
};
