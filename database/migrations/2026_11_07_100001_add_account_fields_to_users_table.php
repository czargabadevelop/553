<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_status', 32)->default('active')->after('role')->index();
            $table->timestamp('suspended_at')->nullable()->after('account_status');
            $table->string('suspended_reason')->nullable()->after('suspended_at');
            $table->timestamp('last_login_at')->nullable()->after('suspended_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_status', 'suspended_at', 'suspended_reason', 'last_login_at']);
        });
    }
};
