<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Activity\ActivityEvent;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * One browser-side event from resources/js/modules/activity.js: a table was
 * printed or searched, or a confirmation dialog was closed without confirming.
 *
 * Only the `ui.*` names of the closed catalogue are accepted, and only the
 * handful of fields each one needs — everything else is dropped, so a crafted
 * beacon cannot fill the journal with free text.
 */
final class ActivityBeaconRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'event' => ['required', 'string', Rule::in(ActivityEvent::browserValues())],
            'table' => ['nullable', 'string', Rule::in(['payments', 'traffic'])],
            'rows' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'query' => ['nullable', 'string', 'max:100'],
            'results' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'tariff_id' => ['nullable', 'integer', 'min:1'],
            'permit_id' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function event(): ActivityEvent
    {
        return ActivityEvent::from((string) $this->validated('event'));
    }

    /**
     * @return array<string, scalar|null>
     */
    public function meta(): array
    {
        $meta = [];

        foreach (['table', 'rows', 'query', 'results', 'tariff_id', 'permit_id'] as $field) {
            $value = $this->validated($field);

            if ($value !== null && $value !== '') {
                $meta[$field] = in_array($field, ['rows', 'results', 'tariff_id'], true) ? (int) $value : (string) $value;
            }
        }

        return $meta;
    }

    /**
     * A beacon has no page to redirect back to — the framework's default for a
     * failed form — so it gets a bare 422.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->noContent(422));
    }
}
