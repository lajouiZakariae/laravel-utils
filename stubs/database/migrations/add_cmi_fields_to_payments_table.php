<?php

use App\Enums\CardBrandEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // change table name if your payments table is named differently
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('cmi_trans_id')->nullable()->after('transaction_id');
            $table->string('auth_code', 32)->nullable()->after('cmi_trans_id');
            $table->string('masked_pan', 32)->nullable()->after('auth_code');
            $table->string('proc_return_code', 8)->nullable()->after('masked_pan');
            $table->string('md_status', 4)->nullable()->after('proc_return_code');
            $table->text('error_message')->nullable()->after('md_status');
            $table->string('payment_type', 32)->nullable()->after('error_message');
            $table->enum('card_brand', [CardBrandEnum::MASTERCARD->value, CardBrandEnum::VISA->value])->nullable()->after('payment_type');
        });
    }

    public function down(): void
    {
        // change table name if your payments table is named differently
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'cmi_trans_id',
                'auth_code',
                'masked_pan',
                'proc_return_code',
                'md_status',
                'error_message',
                'payment_type',
                'card_brand',
            ]);

            $table->unsignedBigInteger('transaction_id')->nullable(false)->change();
        });
    }
};
