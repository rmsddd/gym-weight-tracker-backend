<?php

namespace App\Http\Requests\Admin;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RolePermissionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // Present but possibly empty: a role may have every permission unticked
            'permissions' => 'present|array',
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionCatalog::names())],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
