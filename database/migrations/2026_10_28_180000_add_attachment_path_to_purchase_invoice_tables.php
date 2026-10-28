<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /** @var list<string> */
    private array $tables = ['invoice_suppliers', 'invoice_subcontractors', 'invoice_others'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('attachment_path')->nullable()->after('cost_price');
                $table->string('attachment_name')->nullable()->after('attachment_path');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['attachment_path', 'attachment_name']);
            });
        }
    }
};
