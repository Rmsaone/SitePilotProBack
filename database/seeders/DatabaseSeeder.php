<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\InvoiceClient;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use App\Models\ProductLine;
use App\Models\Quote;
use App\Models\User;
use App\Models\Workforce;
use App\Models\Worksite;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────
        $admin = User::factory()->create([
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'demo@sitepilotpro.fr',
            'password' => 'Demo#2026',
        ]);

        // ───────────────────────────────────
        $clients = Client::factory(10)->create([
            'user_id' => fn () => $admin->id,
        ]);

        // ─────────────────────────────────
        $worksites = Worksite::factory(15)->create();

        $worksites->each(function (Worksite $worksite) use ($admin): void {
            $worksite->users()->attach(
                $admin->id
            );
        });

        // ─────────────────────────────────────
        $quotes = Quote::factory(20)->create([
            'client_id' => fn () => $clients->random()->id,
        ]);

        // ─────────────────────────── for quote
        $quotes->each(function (Quote $quote): void {
            ProductLine::factory(random_int(2, 8))->create([
                'quote_id' => $quote->id,
            ]);
        });

        $worksites->each(function (Worksite $worksite) use ($quotes): void {
            $worksite->quotes()->attach(
                $quotes->random(random_int(1, 3))->pluck('id')
            );
        });

        // ───────────────────────────
        $worksites->each(function (Worksite $worksite): void {
            InvoiceClient::factory()->create([
                'worksite_id' => $worksite->id,
                'client_id' => $worksite->client_id,
            ]);
        });

        // ──────────────────────
        $worksites->each(function (Worksite $worksite): void {
            InvoiceSupplier::factory(random_int(1, 3))->create([
                'worksite_id' => $worksite->id,
            ]);
        });

        // ────────────────────
        $worksites->each(function (Worksite $worksite): void {
            InvoiceSubcontractor::factory(random_int(1, 3))->create([
                'worksite_id' => $worksite->id,
            ]);
        });

        // ───────────────────────────
        $worksites->each(function (Worksite $worksite): void {
            InvoiceOther::factory(random_int(1, 2))->create([
                'worksite_id' => $worksite->id,
            ]);
        });

        // ──────────────────────────────
        $worksites->each(function (Worksite $worksite): void {
            Workforce::factory(random_int(2, 6))->create([
                'worksite_id' => $worksite->id,
            ]);
        });
    }
}
