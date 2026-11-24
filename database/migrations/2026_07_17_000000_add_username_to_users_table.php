<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });

            // Set default username for existing admin or any existing users (database-agnostic)
            DB::table('users')->where('email', 'admin@admin.com')->update(['username' => 'admin']);

            $users = DB::table('users')->whereNull('username')->select('id', 'email')->get();
            foreach ($users as $user) {
                $base = Str::of((string) $user->email)->before('@')->slug('_')->value() ?: 'user';
                $candidate = $base;
                $suffix = 1;
                while (DB::table('users')->where('username', $candidate)->exists()) {
                    $candidate = "{$base}_{$suffix}";
                    $suffix++;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $candidate]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }
};
