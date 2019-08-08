<?php

namespace App\Http\Requests\Wifi;

use Illuminate\Foundation\Http\FormRequest;

class Password extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'curr_password' => 'required',
            'new_password' => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getCurrentPassword(): string
    {
        return (string) $this->get('curr_password');
    }

    /**
     * @return string
     */
    public function getNewPassword(): string
    {
        return (string) $this->get('new_password');
    }
}
