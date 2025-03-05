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
        Schema::create('event_schedule_yearlies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_schedule_recurring_id')->constrained('event_schedules_recurrings')->onDelete('cascade');
            $table->integer('day')->check('day > 0 and day < 32');
            $table->integer('month')->check('month > 0 and month < 13');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_schedule_yearlies');
    }
};
