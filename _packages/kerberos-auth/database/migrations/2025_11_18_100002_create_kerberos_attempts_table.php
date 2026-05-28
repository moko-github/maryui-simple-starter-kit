<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kerberos_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('kerberos');
            $table->enum('result', ['success', 'no_role', 'unknown_user']);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('attempted_at')->useCurrent();

            $table->index(['kerberos', 'attempted_at']);
            $table->index('attempted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kerberos_attempts');
    }
};
