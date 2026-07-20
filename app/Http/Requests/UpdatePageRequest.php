<?php

namespace App\Http\Requests;

use App\Enums\PageLayout;
use App\Enums\PageStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('page')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'nullable', 'string', 'max:120'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'layout' => ['sometimes', Rule::enum(PageLayout::class)],
            'status' => ['sometimes', Rule::enum(PageStatus::class)],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:160'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:300'],
            'theme' => ['sometimes', 'nullable', 'array'],
            'theme.presetId' => ['nullable', 'string', 'max:255'],
            'theme.mode' => ['nullable', 'string', 'in:light,dark,auto'],
            'theme.tokens' => ['nullable', 'array'],
        ];
    }
}
