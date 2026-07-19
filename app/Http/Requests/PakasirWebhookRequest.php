<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PakasirWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'order_id' => ['required', 'string', 'max:100'],
            'project' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'completed_at' => ['nullable', 'date'],
        ];
    }
}
