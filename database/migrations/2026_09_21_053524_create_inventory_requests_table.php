<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_id')
                ->nullable()
                ->constrained('inventories')
                ->nullOnDelete();

            $table->string('requestor_name');
            $table->string('department_office');

            // Snapshots of the inventory information at the time of request.
            $table->string('property_no')->nullable();
            $table->string('item_name');
            $table->string('unit');

            $table->unsignedInteger('requested_quantity');

            $table->date('request_date');
            $table->dateTime('requested_at');

            $table->timestamps();

            $table->index([
                'request_date',
                'department_office'
            ]);

            $table->index('item_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_requests');
    }
};