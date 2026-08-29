<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builds the redirect URL for iWon Business's hosted card form.
 *
 * There is no API call here — iWon's own integration is a plain browser GET,
 * unsigned, with no server-to-server step (see docs/api/iwon-api.md). This
 * class only has to get three things right: the amount in tiyin (never
 * float-truncated), the billing account under the key iWon reads
 * (config('iwon.account_param')), and a unique `additional_id` — the one
 * safety net against a duplicate/replayed redirect, since there is no
 * callback to deduplicate on iWon's side.
 */
final class IwonCheckout
{
    public function __construct(
        private readonly string $frameUrl,
        private readonly ?string $serviceId,
        private readonly string $accountParam,
        private readonly string $currency,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('iwon.frame_url'),
            config('iwon.service_id'),
            (string) config('iwon.account_param'),
            (string) config('iwon.currency'),
        );
    }

    /**
     * @param  float  $amountSom  Whole so'm, whatever the subscriber typed —
     *                            never tiyin, never a string billing sent.
     */
    public function redirectUrl(float $amountSom, string $accountId, string $returnUrl): IwonRedirect
    {
        // (int) alone truncates: 1200.00 * 100 can land on 119999.999… in
        // float and lose a tiyin. round() first, per iWon's own documented
        // gotcha.
        $amountTiyin = (int) round($amountSom * 100);

        // The only replay guard this integration has — iWon never calls
        // back, so uniqueness is ours to guarantee, not theirs to check.
        // Digits only, 17 max: 13-digit millisecond timestamp + 4 random.
        $additionalId = sprintf('%d%04d', (int) (microtime(true) * 1_000), random_int(0, 9_999));

        $transactionParams = ['additional_id' => $additionalId];

        // Set last: the billing account must never be the field a timestamp
        // collision or a future key happens to overwrite.
        $transactionParams[$this->accountParam] = $accountId;

        $query = http_build_query([
            'amount' => $amountTiyin,
            'currency' => $this->currency,
            'serviceId' => $this->serviceId,
            'returnUrl' => $returnUrl,
            'transactionParams' => json_encode($transactionParams, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);

        return new IwonRedirect(rtrim($this->frameUrl, '/').'/?'.$query, $additionalId);
    }
}
