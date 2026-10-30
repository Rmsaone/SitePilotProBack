<?php

declare(strict_types=1);

namespace App\Actions\Client;

use App\Models\Client;
use Illuminate\Validation\ValidationException;

/**
 * A client with worksites or quotes is kept: deleting it would orphan them.
 */
final class DeleteClientAction
{
    /**
     * @throws ValidationException
     */
    public function __invoke(
        Client $client,
    ): void {
        if ($client->worksites()->exists() || $client->quotes()->exists()) {
            throw ValidationException::withMessages([
                'client' => 'Ce client a des chantiers ou des devis : il ne peut pas être supprimé.',
            ]);
        }

        $client->delete();
    }
}
