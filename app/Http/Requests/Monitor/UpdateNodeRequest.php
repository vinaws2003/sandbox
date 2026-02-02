<?php

namespace App\Http\Requests\Monitor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['synology', 'docker', 'galera', 'laravel_app'])],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['boolean'],
            'credentials' => ['nullable', 'array'],
            'credentials.username' => ['nullable', 'string', 'max:255'],
            'credentials.password' => ['nullable', 'string', 'max:255'],
            'credentials.database' => ['nullable', 'string', 'max:255'],
            'credentials.health_endpoint' => ['nullable', 'string', 'max:255'],
            'credentials.health_token' => ['nullable', 'string', 'max:255'],
        ];
    }
}
