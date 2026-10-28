<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A private client (particulier) has no SIRET nor intra-community VAT number.
 * The unique indexes are kept: NULL values do not collide.
 */
return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('siret')->nullable()->change();
            $table->string('tva_intra')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('siret')->nullable(false)->change();
            $table->string('tva_intra')->nullable(false)->change();
        });
    }
};
