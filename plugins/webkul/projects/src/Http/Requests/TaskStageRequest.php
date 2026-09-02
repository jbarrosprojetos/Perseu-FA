<?php

namespace Webkul\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $requiredRule = $isUpdate ? ['sometimes', 'required'] : ['required'];

        return [
            'name'        => [...$requiredRule, 'string', 'max:255'],
            'processo_id' => [...$requiredRule, 'integer', 'exists:projects_processos,id'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Task stage name.',
                'example'     => 'Backlog',
            ],
            'processo_id' => [
                'description' => 'Processo ID this stage belongs to.',
                'example'     => 1,
            ],
        ];
    }
}
