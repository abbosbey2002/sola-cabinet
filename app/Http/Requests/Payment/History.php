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
            'pay_month' => 'nullable'
        ];
    }

    public function getPayMonth(): string
    {
        return (string) $this->get('pay_month') ? $this->get("pay_month") : date('Y-m', time());
    }
}
