<?php

namespace App\Http\Requests\Admin;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Root is never handed out from the app
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('guard_name', 'web'),
                Rule::notIn([PermissionCatalog::ROOT]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.exists' => 'This role does not exist.',
            'role.not_in' => 'This role cannot be given from here.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
