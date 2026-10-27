<?php

declare(strict_types=1);

namespace App\Actions\Worksite;

use App\Dto\WorksiteDto;
use App\Models\Worksite;
use Random\RandomException;

final class UpdateWorksiteAction
{
    /**
     * @throws RandomException
     */
    public function __invoke(
        Worksite $worksite,
        WorksiteDto $worksiteDto,
    ): Worksite {
        $worksite->update([
            'client_id' => $worksiteDto->client_id,
            'code' => $worksiteDto->code ?? $worksite->code ?? 'FR'.random_int(10000, 99999).'A',
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
            $worksite->quotes()->syncWithoutDetaching([$worksiteDto->quoteId]);
        }

        if ($worksiteDto->userId !== null) {
            $worksite->users()->sync([$worksiteDto->userId]);
        }

        return $worksite->refresh();
    }
}
