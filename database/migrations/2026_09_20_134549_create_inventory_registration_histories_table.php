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
        Schema::create('inventory_registration_histories', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Reference to the current inventory item
            |--------------------------------------------------------------------------
            |
            | nullable + nullOnDelete is intentional.
            | If the current inventory record is deleted, the historical
            | registration must remain.
            |
            */
            $table->foreignId('inventory_id')
                ->nullable()
                ->constrained('inventories')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Historical Snapshot
            |--------------------------------------------------------------------------
            |
            | These values are copied when the item is first registered.
            | They are NOT linked to the current inventory values.
            |
            */
            $table->string('property_no');
            $table->string('name');
            $table->string('category');
            $table->string('unit');

            // IMPORTANT: This is the original received/registered quantity.
            // It must never be changed when current inventory quantity changes.
            $table->unsignedInteger('registered_qty');

            $table->unsignedInteger('reorder_level')->default(1);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->text('image')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Historical Registration Date
            |--------------------------------------------------------------------------
            |
            | registered_date is used by the calendar.
            | registered_at keeps the exact registration timestamp.
            |
            */
            $table->date('registered_date')->index();
            $table->timestamp('registered_at')->index();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */
            $table->index(['registered_date', 'id']);
            $table->index('property_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_registration_histories');
    }
};