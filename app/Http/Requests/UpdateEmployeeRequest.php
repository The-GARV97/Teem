<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name'  => strip_tags($this->name ?? ''),
            'email' => strip_tags($this->email ?? ''),
            'phone' => $this->phone !== null ? strip_tags($this->phone) : null,
        ]);
    }

    public function rules(): array
    {
        $orgId = auth()->user()->org_id;

        return [
            'name'           => ['required', 'string', 'max:255'],
            'email'          => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees')->where('org_id', $orgId)->ignore($this->employee->id),
            ],
            'phone'          => ['nullable', 'string', 'max:20'],
            'department_id'  => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where('org_id', $orgId),
            ],
            'designation_id' => [
                'required',
                'integer',
                Rule::exists('designations', 'id')->where('org_id', $orgId),
            ],
            'manager_id'     => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('org_id', $orgId),
            ],
            'user_id'        => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('org_id', $orgId),
            ],
            'joining_date'   => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'status'         => ['nullable', 'in:active,inactive'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            if ($this->manager_id && (int) $this->manager_id === $this->employee->id) {
                $v->errors()->add('manager_id', 'An employee cannot be their own manager.');
            }
        });
    }
}
