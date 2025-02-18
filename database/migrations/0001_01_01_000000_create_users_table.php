<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Création de la table users
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('phone')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(false);

            $table->uuid('role_id'); // Change le type de role_id en uuid
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            $table->rememberToken();
            $table->timestamps();

            $table->string('email')->unique()->nullable();
        });


        // Table sessions pour les connexions des utilisateurs
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('otps');
        Schema::dropIfExists('users');
    }
};
