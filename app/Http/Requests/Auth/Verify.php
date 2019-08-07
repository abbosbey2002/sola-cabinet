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
        return [
            'login' => 'required',
            'password' => 'required'
        ];
    }


    public function getLogin(): string
    {
        return (string) $this->get('login');
    }

    public function getPassword(): string
    {
        return (string) $this->get('password');
    }
}
