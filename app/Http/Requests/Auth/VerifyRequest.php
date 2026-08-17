<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:10'],
        ];
    }

    public function code(): string
    {
        return (string) $this->input('code');
    }
}
