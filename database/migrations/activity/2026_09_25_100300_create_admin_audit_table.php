<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who in the admin panel looked at what. The activity journal holds
 * subscribers' phones and names; reading them must leave a trace of its own.
 *
 * The admin's username and role are copied in rather than joined: `admins`
 * lives on the SQLite connection, and a deleted admin's history must survive.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return config('activity.connection');
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create('admin_audit', function (Blueprint $table): void {
            $table->id();
            $table->timestamp('created_at');

            $table->unsignedBigInteger('admin_id');
            $table->string('admin_username', 255);
            $table->string('admin_role', 16);

            $table->string('action', 64);
            $table->string('method', 8);
            $table->string('path', 255);
            $table->jsonb('query')->nullable();
            $table->string('subject_account_id', 32)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();

            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->index('created_at');
            $table->index(['admin_id', 'created_at']);
            $table->index('subject_account_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists('admin_audit');
    }
};
