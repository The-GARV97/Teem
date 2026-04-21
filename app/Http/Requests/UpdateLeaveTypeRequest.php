<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage-employees');
    }

    public function rules(): array
    {
        return [
            'name'     => [
                'required',
                'string',
                'max:100',
                Rule::unique('leave_types')
                    ->where('org_id', $this->user()->org_id)
                    ->ignore($this->route('leave_type')),
            ],
            'max_days' => ['required', 'integer', 'min:1'],
        ];
    }
}
