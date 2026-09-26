<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normalized_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('subject')->nullable();
            $table->string('contact_value')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normalized_events');
    }
};
