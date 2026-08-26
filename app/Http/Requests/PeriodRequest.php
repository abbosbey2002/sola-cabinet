<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Period;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The "Период: с … по …" controls on the statistics and finance blocks.
 *
 * Both dates are required together — a half-filled range is a user mistake,
 * not something to guess at.
 */
final class PeriodRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'start' => ['required', 'date_format:Y-m-d'],
            'end' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function period(): Period
    {
        return Period::between($this->string('start')->value(), $this->string('end')->value());
    }
}
