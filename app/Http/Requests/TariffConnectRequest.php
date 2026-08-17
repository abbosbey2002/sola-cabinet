<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * "Tarifni o'zgartirish" — the one form in the cabinet that spends money.
 *
 * It used to be a GET link (/tariffs/connect/{id}/{type}), which meant no CSRF
 * token: any page the subscriber visited while signed in could redirect to it
 * and switch their tariff. It is a POST form now, and the two values that used
 * to be route segments are body fields — so they are validated here instead of
 * by a route constraint.
 *
 * "timing" is a closed set of two. Anything else used to fall through a switch
 * and reach billing with an undefined date.
 */
final class TariffConnectRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tariff' => ['required', 'integer', 'min:1'],
            'timing' => ['required', 'string', 'in:now,month'],
        ];
    }

    public function tariffId(): int
    {
        return $this->integer('tariff');
    }

    public function timing(): string
    {
        return $this->string('timing')->value();
    }
}
