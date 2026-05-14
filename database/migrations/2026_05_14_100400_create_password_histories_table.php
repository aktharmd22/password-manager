<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained('credentials')->cascadeOnDelete();
            $table->text('old_password_encrypted');
            $table->timestamp('changed_at')->useCurrent();

            $table->index('credential_id');
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_histories');
    }
};
