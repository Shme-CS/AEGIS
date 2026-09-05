<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateProjectRequest extends FormRequest
{
    /**
     * Determine if the authenticated user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Project::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'program_id' => [
                'required',
                'integer',
                'exists:programs,id',
            ],

            'title' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],

            'abstract' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'project_type' => [
                'required',
                'string',
                Rule::in([
                    'technical',
                    'research',
                    'mixed',
                ]),
            ],

            'academic_year' => [
                'required',
                'string',
                'max:20',
            ],

            'members' => [
                'nullable',
                'array',
                'max:10',
            ],

            'members.*' => [
                'integer',
                'distinct',
                'exists:users,id',
            ],
        ];
    }
}