<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('workforces', function (Blueprint $table): void {
            $table->decimal('hr_working', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('workforces', function (Blueprint $table): void {
            $table->integer('hr_working')->nullable()->change();
        });
    }
};
