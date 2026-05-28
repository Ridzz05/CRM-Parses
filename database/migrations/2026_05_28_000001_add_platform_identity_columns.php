<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('platform')->default('telegram')->after('id')->index();
            $table->string('platform_user_id')->nullable()->after('platform')->index();
        });

        Schema::table('instructions', function (Blueprint $table) {
            $table->string('platform')->default('web')->after('source')->index();
            $table->string('platform_user_id')->nullable()->after('platform')->index();
            $table->string('platform_message_id')->nullable()->after('platform_user_id')->index();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('platform')->default('telegram')->after('customer_id')->index();
            $table->string('platform_user_id')->nullable()->after('platform')->index();
        });

        DB::table('customers')->update([
            'platform' => 'telegram',
            'platform_user_id' => DB::raw('telegram_user_id'),
        ]);

        DB::table('instructions')->update([
            'platform' => DB::raw('source'),
            'platform_user_id' => DB::raw('telegram_user_id'),
            'platform_message_id' => DB::raw('external_message_id'),
        ]);

        DB::table('transactions')->update([
            'platform' => 'telegram',
            'platform_user_id' => DB::raw('telegram_user_id'),
        ]);

        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['platform', 'platform_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['platform', 'platform_user_id']);
            $table->dropColumn(['platform', 'platform_user_id']);
        });

        Schema::table('instructions', function (Blueprint $table) {
            $table->dropColumn(['platform', 'platform_user_id', 'platform_message_id']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['platform', 'platform_user_id']);
        });
    }
};
