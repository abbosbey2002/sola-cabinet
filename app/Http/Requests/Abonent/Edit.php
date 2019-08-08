<?php

namespace App\Http\Requests\Abonent;

use Illuminate\Foundation\Http\FormRequest;

class Edit extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'phone' => 'required',
            'email' => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return (string) $this->get('phone');
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return (string) $this->get('email');
    }

}
