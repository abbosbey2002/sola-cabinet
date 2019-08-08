<?php

namespace App\Http\Requests\Traffic;

use Illuminate\Foundation\Http\FormRequest;

class Detail extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'detail_month' => 'nullable'
        ];
    }

    public function getMonth(): string
    {
        return (string) $this->get('detail_month') ? $this->get('detail_month') : date('Y-m', time());
    }
}
