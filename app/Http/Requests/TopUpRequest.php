<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The amount a subscriber wants to add to their balance via iWon.
 *
 * The floor (1 000 so'm) matches the smoke-test amount in iWon's own
 * reference doc (docs/api/iwon-api.md) — the smallest figure ever confirmed
 * to actually clear their form. Neither iWon nor the client has confirmed a
 * real minimum or maximum, so the ceiling here is a sanity guard against a
 * fat-fingered or scripted absurd amount, not a business rule.
 */
final class TopUpRequest extends FormRequest
{
    private const MIN_SOM = 1_000;

    private const MAX_SOM = 50_000_000;

    /**
     * The amount field is masked client-side into "10 000" so it reads as a
     * sum at a glance (resources/js/modules/amount-mask.js) — spaces have to
     * come back out before `numeric` gets to judge it, or every masked
     * submission would fail validation on its own formatting. Runs the same
     * with JS off: a plain "10000" has no spaces to strip.
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        // A crafted `amount[]=1` array input would warn on the (string)
        // cast below instead of just failing `numeric` cleanly — sidestep
        // it rather than let a malformed request make noise in the logs.
        $this->merge([
            'amount' => is_string($amount) ? str_replace(' ', '', $amount) : $amount,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:'.self::MIN_SOM, 'max:'.self::MAX_SOM],
        ];
    }

    public function amount(): float
    {
        return $this->float('amount');
    }

    /**
     * The form this validates renders in two places: the full /topup page,
     * and the quick-top-up modal x-pay-card opens on Home and Payments. The
     * framework default (redirect back to wherever the request came from)
     * would send a failure opened from the modal back to Home or Payments —
     * pages that don't render this form's error/old-input state at all, so
     * the subscriber would see no explanation. Always landing on the real
     * page keeps that guarantee regardless of which copy was submitted.
     */
    protected function getRedirectUrl(): string
    {
        return route('topup');
    }
}
