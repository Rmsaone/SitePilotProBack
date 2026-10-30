<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseInvoices;

use App\Actions\PurchaseInvoice\CreatePurchaseInvoiceAction;
use App\Actions\PurchaseInvoice\UpdatePurchaseInvoiceAction;
use App\Dto\PurchaseInvoiceDto;
use App\Models\InvoiceOther;
use App\Models\InvoiceSubcontractor;
use App\Models\InvoiceSupplier;
use App\Models\Worksite;
use Database\Factories\InvoiceSupplierFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(CreatePurchaseInvoiceAction::class)]
#[CoversClass(UpdatePurchaseInvoiceAction::class)]
#[CoversClass(PurchaseInvoiceDto::class)]
final class PurchaseInvoiceActionsTest extends TestCase
{
    use DatabaseTransactions;

    private Worksite $worksite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->worksite = WorksiteFactory::new()->createOne();
    }

    private function makeDto(array $overrides = []): PurchaseInvoiceDto
    {
        return PurchaseInvoiceDto::fromArray(array_merge([
            'worksite_id' => $this->worksite->id,
            'delivery_date' => '2026-10-05',
            'number_invoice' => 'F261005',
            'invoice_description' => 'ADF',
            'purchase_price' => '490.00',
            'cost_price' => '637.10',
        ], $overrides));
    }

    #[TestDox(
        'Given prices in euros
        When I build a PurchaseInvoiceDto
        Then they should be converted to cents'
    )]
    public function test_should_convert_prices_to_cents(): void
    {
        $dto = $this->makeDto();

        $this->assertSame('49000', $dto->purchasePrice->getAmount());
        $this->assertSame('63710', $dto->costPrice->getAmount());
    }

    #[TestDox(
        'Given each purchase invoice model
        When I call CreatePurchaseInvoiceAction
        Then an invoice of that model should be persisted'
    )]
    public function test_should_create_invoice_for_each_model(): void
    {
        foreach ([InvoiceSupplier::class, InvoiceSubcontractor::class, InvoiceOther::class] as $modelClass) {
            $invoice = (new CreatePurchaseInvoiceAction())($modelClass, $this->makeDto());

            $this->assertInstanceOf($modelClass, $invoice);
            $this->assertSame($this->worksite->id, $invoice->worksite_id);
        }
    }

    #[TestDox(
        'Given an existing invoice
        When I call UpdatePurchaseInvoiceAction
        Then the new values should be persisted'
    )]
    public function test_should_update_invoice(): void
    {
        $invoice = InvoiceSupplierFactory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $result = (new UpdatePurchaseInvoiceAction())($invoice, $this->makeDto(['invoice_description' => 'JOLY']));

        $this->assertSame('JOLY', $result->invoice_description);
        $this->assertSame('49000', $result->purchase_price->getAmount());
    }
}
