<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->isMethod('get')) {
            return [];
        }

        return [
            'login' => ['required', 'string', 'max:20'],
        ];
    }

    /**
     * The phone as the API wants it: digits only, no "+" and no spaces.
     */
    public function phone(): string
    {
        return str_replace(['+', ' '], '', (string) $this->input('login'));
    }
}
