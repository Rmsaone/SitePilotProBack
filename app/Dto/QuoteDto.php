<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enums\QuoteStatus;
use App\Enums\TvaRate;
use App\Http\Requests\QuoteRequest;
use Illuminate\Support\Carbon;

final readonly class QuoteDto
{
    /**
     * @param  list<ProductLineDto>  $lines
     */
    public function __construct(
        public int $clientId,
        public ?int $worksiteId,
        public Carbon $issueDate,
        public Carbon $validityDate,
        public QuoteStatus $quoteStatus,
        public TvaRate $tvaRate,
        public array $lines,
    ) {
    }

    /**
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $issueDate = Carbon::parse($data['issue_date']);

        /** @var list<array<string,mixed>> $lines */
        $lines = array_values((array) $data['lines']);

        return new self(
            clientId: (int) $data['client_id'],
            worksiteId: isset($data['worksite_id']) ? (int) $data['worksite_id'] : null,
            issueDate: $issueDate,
            validityDate: isset($data['validity_date'])
                ? Carbon::parse($data['validity_date'])
                : $issueDate->copy()->addMonths(QuoteRequest::MIN_VALIDITY_MONTHS),
            quoteStatus: QuoteStatus::from((string) $data['quote_status']),
            tvaRate: TvaRate::from((string) $data['tva_rate']),
            lines: array_map(ProductLineDto::fromArray(...), $lines),
        );
    }
}
