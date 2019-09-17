<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class Login extends FormRequest
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
            'login' => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return (string) str_replace(['+', ' '], '', $this->get('login'));
    }
}
