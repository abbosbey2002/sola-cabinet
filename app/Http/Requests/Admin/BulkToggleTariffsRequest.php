<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class BulkToggleTariffsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bulk_action' => ['required', 'string', 'in:enable,disable'],
            'tariff_ids' => ['required', 'array', 'min:1'],
            'tariff_ids.*' => ['integer'],
        ];
    }

    public function shouldEnable(): bool
    {
        return $this->string('bulk_action')->value() === 'enable';
    }

    /**
     * @return list<int>
     */
    public function tariffIds(): array
    {
        return array_map(intval(...), (array) $this->input('tariff_ids', []));
    }
}
