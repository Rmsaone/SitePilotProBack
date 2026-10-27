<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\QuoteStatus;
use App\Enums\TvaRate;
use App\Enums\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Override;
use Throwable;

class QuoteRequest extends FormRequest
{
    /** A quote must stay valid at least this many months after its issue date. */
    public const int MIN_VALIDITY_MONTHS = 3;

    /**
     * @return array<string, array<int,mixed>|ValidationRule>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',
            ],
            'worksite_id' => [
                'nullable',
                'integer',
                Rule::exists('worksites', 'id')
                    ->where('client_id', $this->integer('client_id')),
            ],
            'issue_date' => [
                'required',
                'date',
            ],
            'validity_date' => [
                'nullable',
                'date',
            ],
            'quote_status' => [
                'required',
                Rule::enum(QuoteStatus::class),
            ],
            'tva_rate' => [
                'required',
                Rule::enum(TvaRate::class),
            ],
            'lines' => [
                'required',
                'array',
                'min:1',
                'max:200',
            ],
            'lines.*.category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'lines.*.description' => [
                'required',
                'string',
                'max:255',
            ],
            'lines.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999',
            ],
            'lines.*.unit' => [
                'required',
                Rule::enum(Unit::class),
            ],
            'lines.*.unit_price_ht' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator):void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['issue_date', 'validity_date']) || ! $this->filled('validity_date')) {
                    return;
                }

                try {
                    $minimum = Carbon::parse((string) $this->input('issue_date'))->addMonths(self::MIN_VALIDITY_MONTHS);
                    $validity = Carbon::parse((string) $this->input('validity_date'));
                } catch (Throwable) {
                    return;
                }

                if ($validity->lt($minimum->startOfDay())) {
                    $validator->errors()->add(
                        'validity_date',
                        sprintf('La date de validité doit être au moins %d mois après la date d\'émission (%s).', self::MIN_VALIDITY_MONTHS, $minimum->format('d/m/Y'))
                    );
                }
            },
        ];
    }

    /**
     * @return array<string,string>
     */
    #[Override]
    public function attributes(): array
    {
        return [
            'client_id' => 'Client lié',
            'worksite_id' => 'Chantier lié',
            'issue_date' => "Date d'émission",
            'validity_date' => 'Date de validité',
            'quote_status' => 'Statut',
            'tva_rate' => 'Taux TVA',
            'lines' => 'Lignes du devis',
            'lines.*.category' => 'Catégorie',
            'lines.*.description' => 'Description',
            'lines.*.quantity' => 'Quantité',
            'lines.*.unit' => 'Unité',
            'lines.*.unit_price_ht' => 'Prix unitaire HT',
        ];
    }
}
