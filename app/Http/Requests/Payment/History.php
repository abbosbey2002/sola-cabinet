<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class History extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'month' => 'required'
        ];
    }

    public function getPayMonth(): string
    {
        return $this->get('month');
    }
}
