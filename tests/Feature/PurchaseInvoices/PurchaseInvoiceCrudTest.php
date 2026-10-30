<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseInvoices;

use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use App\Models\Worksite;
use Database\Factories\InvoiceOtherFactory;
use Database\Factories\InvoiceSubcontractorFactory;
use Database\Factories\InvoiceSupplierFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Supplier, subcontractor and other invoices share the same API contract.
 */
class PurchaseInvoiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAsUser();
        $this->worksite = WorksiteFactory::new()->createOne();
    }

    /**
     * @return array<string, array{string, string, class-string<Factory>}>
     */
    public static function endpoints(): array
    {
        return [
            'supplier' => ['/api/v1/invoice_suppliers', 'invoice_suppliers', InvoiceSupplierFactory::class],
            'subcontractor' => ['/api/v1/invoice_subcontractors', 'invoice_subcontractors', InvoiceSubcontractorFactory::class],
            'other' => ['/api/v1/invoice_others', 'invoice_others', InvoiceOtherFactory::class],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'worksite_id' => $this->worksite->id,
            'delivery_date' => '2026-10-05',
            'number_invoice' => 'F261005',
            'invoice_description' => 'CASTORAMA',
            'purchase_price' => 46.5,
            'cost_price' => 59.8,
        ], $overrides);
    }

    /**
     * @param  class-string<Factory>  $factory
     */
    #[DataProvider('endpoints')]
    public function test_should_create_invoice_with_prices_in_cents(string $url, string $table, string $factory): void
    {
        $this->postJson($url, $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.purchase_price.amount', '4650')
            ->assertJsonPath('data.cost_price.amount', '5980')
            ->assertJsonPath('data.worksite_id', $this->worksite->id);

        $this->assertDatabaseHas($table, [
            'worksite_id' => $this->worksite->id,
            'number_invoice' => 'F261005',
            'purchase_price' => 4650,
            'cost_price' => 5980,
        ]);
    }

    /**
     * @param  class-string<Factory>  $factory
     */
    #[DataProvider('endpoints')]
    public function test_should_validate_invoice_payload(string $url, string $table, string $factory): void
    {
        $this->postJson($url, $this->payload([
            'worksite_id' => 999999,
            'purchase_price' => -1,
            'number_invoice' => '',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['worksite_id', 'purchase_price', 'number_invoice']);
    }

    /**
     * @param  class-string<Factory>  $factory
     */
    #[DataProvider('endpoints')]
    public function test_should_update_invoice(string $url, string $table, string $factory): void
    {
        /** @var InvoiceOther|InvoiceSubcontractor|InvoiceSupplier $invoice */
        $invoice = $factory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $this->putJson("{$url}/{$invoice->id}", $this->payload(['invoice_description' => 'PROLIANS']))
            ->assertOk()
            ->assertJsonPath('data.invoice_description', 'PROLIANS');

        $this->assertDatabaseHas($table, ['id' => $invoice->id, 'invoice_description' => 'PROLIANS']);
    }

    /**
     * @param  class-string<Factory>  $factory
     */
    #[DataProvider('endpoints')]
    public function test_should_delete_invoice(string $url, string $table, string $factory): void
    {
        /** @var InvoiceOther|InvoiceSubcontractor|InvoiceSupplier $invoice */
        $invoice = $factory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $this->deleteJson("{$url}/{$invoice->id}")->assertNoContent();

        $this->assertSoftDeleted($table, ['id' => $invoice->id]);
    }

    /**
     * @param  class-string<Factory>  $factory
     */
    #[DataProvider('endpoints')]
    public function test_should_filter_invoices_by_worksite(string $url, string $table, string $factory): void
    {
        $factory::new()->count(2)->create(['worksite_id' => $this->worksite->id]);
        $factory::new()->createOne();

        $this->getJson("{$url}?worksite_id={$this->worksite->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @param  class-string<Factory>  $factory
     */
    #[DataProvider('endpoints')]
    public function test_should_return_404_for_unknown_invoice(string $url, string $table, string $factory): void
    {
        $this->putJson("{$url}/999999", $this->payload())->assertNotFound();
        $this->deleteJson("{$url}/999999")->assertNotFound();
    }
}
