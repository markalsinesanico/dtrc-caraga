<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Current Inventory
        |--------------------------------------------------------------------------
        */
        Schema::table('inventories', function (Blueprint $table) {
            $table->longText('image')->nullable()->change();
        });

        /*
        |--------------------------------------------------------------------------
        | Registration History
        |--------------------------------------------------------------------------
        |
        | The history also stores an image snapshot, so it needs the same
        | capacity as the current inventory table.
        |
        */
        if (Schema::hasTable('inventory_registration_histories')) {
            Schema::table('inventory_registration_histories', function (Blueprint $table) {
                $table->longText('image')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->text('image')->nullable()->change();
        });

        if (Schema::hasTable('inventory_registration_histories')) {
            Schema::table('inventory_registration_histories', function (Blueprint $table) {
                $table->text('image')->nullable()->change();
            });
        }
    }
};