<?php

declare(strict_types=1);

namespace Tests\Unit\Quotes;

use App\Actions\Quote\CreateQuoteAction;
use App\Actions\Quote\SyncQuoteLinesAction;
use App\Actions\Quote\UpdateQuoteStatusAction;
use App\Dto\ProductLineDto;
use App\Dto\QuoteDto;
use App\Enums\QuoteStatus;
use App\Enums\TvaRate;
use App\Enums\Unit;
use App\Models\Client;
use Cknow\Money\Money;
use Database\Factories\ClientFactory;
use Database\Factories\QuoteFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @internal
 */
#[CoversClass(CreateQuoteAction::class)]
#[CoversClass(SyncQuoteLinesAction::class)]
#[CoversClass(UpdateQuoteStatusAction::class)]
#[CoversClass(QuoteDto::class)]
#[CoversClass(ProductLineDto::class)]
final class QuoteActionsTest extends TestCase
{
    use DatabaseTransactions;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = ClientFactory::new()->createOne();
    }

    private function line(float $quantity, float $price, ?string $category = null): ProductLineDto
    {
        return new ProductLineDto(
            category: $category,
            description: 'Poste',
            quantity: $quantity,
            unit: Unit::Unite,
            unitPriceHt: Money::EUR((int) round($price * 100)),
        );
    }

    #[TestDox(
        'Given product lines and a VAT rate
        When I call SyncQuoteLinesAction
        Then the quote totals should be recomputed'
    )]
    public function test_should_recompute_totals(): void
    {
        $quote = QuoteFactory::new()->createOne(['client_id' => $this->client->id]);

        $result = (new SyncQuoteLinesAction())($quote, [
            $this->line(2, 100),
            $this->line(1.5, 10),
        ], TvaRate::Taux5_5);

        $this->assertSame('21500', $result->subtotal_ht->getAmount());
        $this->assertSame('1183', $result->tva_amount->getAmount());
        $this->assertSame('22683', $result->total_ttc->getAmount());
        $this->assertSame(2, $result->productLines()->count());
    }

    #[TestDox(
        'Given an empty category label
        When I build a ProductLineDto
        Then the category should be null'
    )]
    public function test_should_normalize_empty_category(): void
    {
        $dto = ProductLineDto::fromArray([
            'category' => '  ',
            'description' => 'Étude',
            'quantity' => '1',
            'unit' => 'flat_rate',
            'unit_price_ht' => '4800.5',
        ]);

        $this->assertNull($dto->category);
        $this->assertSame('480050', $dto->unitPriceHt->getAmount());
    }

    #[TestDox(
        'Given a QuoteDto without validity date
        When I build it from an array
        Then the validity should default to three months after issue'
    )]
    public function test_should_default_validity_to_three_months(): void
    {
        $dto = QuoteDto::fromArray([
            'client_id' => $this->client->id,
            'issue_date' => '2026-10-28',
            'quote_status' => 'draft',
            'tva_rate' => 'taux20',
            'lines' => [['description' => 'Étude', 'quantity' => 1, 'unit' => 'flat_rate', 'unit_price_ht' => 10]],
        ]);

        $this->assertSame('2027-01-28', $dto->validityDate->toDateString());
    }

    #[TestDox(
        'Given an existing quote numbered for the year
        When I call CreateQuoteAction
        Then the next number should follow the sequence'
    )]
    public function test_should_generate_next_number(): void
    {
        QuoteFactory::new()->createOne(['client_id' => $this->client->id, 'quote_number' => 'DEV-2031-007']);

        $quote = app(CreateQuoteAction::class)(new QuoteDto(
            clientId: $this->client->id,
            worksiteId: null,
            issueDate: Carbon::parse('2031-02-01'),
            validityDate: Carbon::parse('2031-05-01'),
            quoteStatus: QuoteStatus::Draft,
            tvaRate: TvaRate::Taux20,
            lines: [$this->line(1, 100)],
        ));

        $this->assertSame('DEV-2031-008', $quote->quote_number);
    }

    #[TestDox(
        'Given a quote
        When I call UpdateQuoteStatusAction
        Then the status should be persisted'
    )]
    public function test_should_update_status(): void
    {
        $quote = QuoteFactory::new()->createOne(['client_id' => $this->client->id, 'quote_status' => QuoteStatus::Send]);

        $result = (new UpdateQuoteStatusAction())($quote, QuoteStatus::Decline);

        $this->assertSame(QuoteStatus::Decline, $result->quote_status);
    }
}
