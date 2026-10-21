<?php

namespace App\Http\Requests\Admin;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Creating a role or renaming one: the same rules, the role being renamed aside. */
class RoleNameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:30',
                Rule::unique('roles', 'name')->ignore($this->route('role')),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (strcasecmp((string) $value, PermissionCatalog::ROOT) === 0) {
                        $fail('This name is reserved.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name the role.',
            'name.min' => 'A role name has at least 2 characters.',
            'name.max' => 'A role name has at most 30 characters.',
            'name.unique' => 'A role with this name already exists.',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
