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
        Schema::table('emails', function (Blueprint $table) {
            // Tipe email Adobe: submission_update (statistik submission),
            // earnings_report (laporan penjualan harian), atau null (lainnya)
            $table->string('email_type', 32)->nullable()->after('snippet');

            // Submission stats (untuk email "Updates from your Adobe Stock submission")
            $table->unsignedInteger('accepted_count')->nullable()->after('email_type');
            $table->unsignedInteger('pending_count')->nullable()->after('accepted_count');
            $table->unsignedInteger('rejected_count')->nullable()->after('pending_count');

            // Earnings (untuk email "Daily Earnings Report")
            $table->decimal('earnings_amount', 12, 4)->nullable()->after('rejected_count');
            $table->string('earnings_currency', 8)->nullable()->after('earnings_amount');

            // Index untuk mempercepat agregasi per akun
            $table->index(['gmail_account_id', 'email_type']);
            $table->index(['gmail_account_id', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropIndex(['gmail_account_id', 'email_type']);
            $table->dropIndex(['gmail_account_id', 'received_at']);

            $table->dropColumn([
                'email_type',
                'accepted_count',
                'pending_count',
                'rejected_count',
                'earnings_amount',
                'earnings_currency',
            ]);
        });
    }
};