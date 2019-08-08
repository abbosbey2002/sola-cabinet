<?php

namespace App\Http\Requests\Tariff;

use Illuminate\Foundation\Http\FormRequest;

class Set extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'tariff_conndate' => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getTariffDate(): string
    {
        return (string) date('Y-m-d', strtotime($this->get('tariff_conndate')));
    }
}
