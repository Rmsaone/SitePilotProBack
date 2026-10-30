<?php

declare(strict_types=1);

namespace App\Actions\Worksite;

use App\Dto\WorksiteDto;
use App\Models\Worksite;
use Random\RandomException;

final class CreateWorksiteAction
{
    /**
     * @throws RandomException
     */
    public function __invoke(
        WorksiteDto $worksiteDto,
    ): Worksite {

        /** @var Worksite $worksite */
        $worksite = Worksite::query()->create([
            'client_id' => $worksiteDto->client_id,
            'code' => $worksiteDto->code ?? 'FR'.random_int(10000, 99999).'A',
            'name_worksite' => $worksiteDto->nameWorksite,
            'description' => $worksiteDto->description,
            'start_date' => $worksiteDto->startDate,
            'end_date' => $worksiteDto->endDate,
            'worksite_priority' => $worksiteDto->worksitePriority,
            'worksite_status' => $worksiteDto->worksiteStatus,
            'street' => $worksiteDto->street,
            'city' => $worksiteDto->city,
            'zip_code' => $worksiteDto->zip_code,
            'country' => $worksiteDto->country,
        ]);

        if ($worksiteDto->quoteId !== null) {
            $worksite->quotes()->attach($worksiteDto->quoteId);
        }

        if ($worksiteDto->userId !== null) {
            $worksite->users()->attach($worksiteDto->userId);
        }

        return $worksite;
    }
}
