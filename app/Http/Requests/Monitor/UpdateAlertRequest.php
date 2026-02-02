<?php

namespace App\Http\Requests\Monitor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'node_id' => ['nullable', 'exists:nodes,id'],
            'metric_type' => ['required', 'string', 'max:100'],
            'condition' => ['required', Rule::in(['gt', 'gte', 'lt', 'lte', 'eq', 'neq'])],
            'threshold' => ['required', 'numeric'],
            'notification_channel' => ['required', Rule::in(['mail', 'slack', 'database'])],
            'notification_target' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'cooldown_minutes' => ['integer', 'min:1', 'max:1440'],
        ];
    }
}
