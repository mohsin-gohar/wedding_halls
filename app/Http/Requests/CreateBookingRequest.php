<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|exists:tenants,id',
            'venue_id' => 'required|exists:venues,id',
            'event_date' => 'required|date|after_or_equal:today|date_format:Y-m-d',
            'slot_type' => 'required|in:AFTERNOON,EVENING,NIGHT',
            'guests_count' => [
                'required',
                'integer',
                'min:1',
                'max:500',
            ],
            'payment_method' => 'required|in:stripe,jazzcash,easypaisa,bank_transfer,cash',
            'guests_name' => 'sometimes|string|max:100',
            'special_requests' => 'sometimes|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.exists' => 'Selected tenant is not valid or accessible.',
            'venue_id.exists' => 'Selected venue is not valid or accessible.',
            'event_date.after_or_equal' => 'Event date must be today or in the future.',
            'event_date.date_format' => 'Please use YYYY-MM-DD format for the date.',
            'slot_type.in' => 'Invalid shift type. Choose: Afternoon, Evening, or Night.',
            'guests_count.min' => 'Guest count must be at least 1.',
            'guests_count.max' => 'Guest count exceeds maximum allowed.',
            'payment_method.in' => 'Invalid payment method selected.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_date' => $this->cleanDate($this->event_date),
            'guests_name' => $this->sanitizeString($this->guests_name ?? ''),
            'special_requests' => $this->sanitizeString($this->special_requests ?? ''),
        ]);
    }

    private function cleanDate(string $date): string
    {
        $date = trim($date);
        $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $carbon = \Carbon\Carbon::createFromFormat($format, $date);
            if ($carbon && \Carbon\Carbon::isValid()) {
                return \Carbon\Carbon::format('Y-m-d');
            }
        }
        return $date;
    }

    private function sanitizeString(string $str): string
    {
        $str = strip_tags($str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        $str = trim($str);
        $str = mb_substr($str, 0, 500, 'UTF-8');
        return $str;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new \Illuminate\Http\JsonResponse([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }
}