<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32);
            $table->string('external_id');
            $table->json('payload');
            $table->boolean('signature_valid')->default(true);
            $table->string('status', 16)->default('pending');
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('status');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_events');
    }
};