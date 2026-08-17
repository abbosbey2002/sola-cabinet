<?php

declare(strict_types=1);

namespace App\Services\Sola;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use LogicException;

/**
 * One answer from the SOLA API.
 *
 * Implements ArrayAccess over the ['status' => int, 'body' => array] shape the
 * Blade templates have always been handed, so the view layer keeps working
 * unchanged while controllers get a typed object.
 *
 * @implements ArrayAccess<string, int|array<string, mixed>>
 * @implements Arrayable<string, int|array<string, mixed>>
 */
final class SolaResponse implements Arrayable, ArrayAccess
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public readonly int $status,
        public readonly array $body,
    ) {}

    public function successful(): bool
    {
        return $this->status === 200;
    }

    public function failed(): bool
    {
        return ! $this->successful();
    }

    /**
     * Read a value out of the body using "dot" notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->body, $key, $default);
    }

    /**
     * The API's own error code, used to look up a translated message.
     */
    public function errorCode(): ?int
    {
        $code = $this->get('code');

        return is_numeric($code) ? (int) $code : null;
    }

    /**
     * The API's human-readable error, when it bothered to send one.
     */
    public function errorMessage(): ?string
    {
        $message = $this->get('errMsg');

        return is_string($message) && $message !== '' ? $message : null;
    }

    /**
     * @return array{status: int, body: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['status' => $this->status, 'body' => $this->body];
    }

    public function offsetExists(mixed $offset): bool
    {
        return $offset === 'status' || $offset === 'body';
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'status' => $this->status,
            'body' => $this->body,
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('A SolaResponse is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('A SolaResponse is immutable.');
    }
}
