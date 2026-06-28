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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gmail_account_id')->constrained('gmail_accounts')->onUpdate('cascade')->onDelete('cascade');

            $table->string('gmail_message_id');

            $table->string('sender')->nullable();

            $table->string('subject')->nullable();

            $table->text('snippet')->nullable();
            $table->longText('body')->nullable();

            $table->boolean('is_read')->default(false);

            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
