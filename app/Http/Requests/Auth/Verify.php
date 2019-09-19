<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Verify extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->isMethod('get')) {
            return [];
        }

        return [
            'code' => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return (string) $this->get('code');
    }
}
