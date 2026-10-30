<?php

declare(strict_types=1);

namespace Tests\Feature\PurchaseInvoices;

use App\Models\InvoiceSupplier;
use App\Models\Worksite;
use Database\Factories\InvoiceSupplierFactory;
use Database\Factories\WorksiteFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseInvoiceAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private Worksite $worksite;

    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->disk = Storage::fake('local');
        $this->actingAsUser();
        $this->worksite = WorksiteFactory::new()->createOne();
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
            'purchase_price' => 46,
            'cost_price' => 59.8,
        ], $overrides);
    }

    public function test_should_store_and_download_the_scanned_invoice(): void
    {
        $response = $this->post('/api/v1/invoice_suppliers', $this->payload([
            'attachment' => UploadedFile::fake()->create('facture.pdf', 120, 'application/pdf'),
        ]), ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('data.has_attachment', true)
            ->assertJsonPath('data.attachment_name', 'facture.pdf');

        /** @var InvoiceSupplier $invoice */
        $invoice = InvoiceSupplier::query()->firstOrFail();
        $this->disk->assertExists((string) $invoice->attachment_path);

        $this->get("/api/v1/invoice_suppliers/{$invoice->id}/attachment")
            ->assertOk()
            ->assertDownload('facture.pdf');
    }

    public function test_should_replace_the_previous_attachment(): void
    {
        $invoice = InvoiceSupplierFactory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $this->post("/api/v1/invoice_suppliers/{$invoice->id}", $this->payload([
            '_method' => 'PUT',
            'attachment' => UploadedFile::fake()->image('ticket.jpg'),
        ]), ['Accept' => 'application/json'])->assertOk();
        $first = (string) $invoice->refresh()->attachment_path;

        $this->post("/api/v1/invoice_suppliers/{$invoice->id}", $this->payload([
            '_method' => 'PUT',
            'attachment' => UploadedFile::fake()->create('facture.pdf', 50, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertOk();

        $this->disk->assertMissing($first);
        $this->disk->assertExists((string) $invoice->refresh()->attachment_path);
    }

    public function test_should_reject_unsupported_files(): void
    {
        $this->post('/api/v1/invoice_suppliers', $this->payload([
            'attachment' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('attachment');
    }

    public function test_should_return_404_when_there_is_no_attachment(): void
    {
        $invoice = InvoiceSupplierFactory::new()->createOne(['worksite_id' => $this->worksite->id]);

        $this->getJson("/api/v1/invoice_suppliers/{$invoice->id}/attachment")->assertNotFound();
    }
}
