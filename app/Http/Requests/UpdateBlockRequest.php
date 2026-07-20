<?php

namespace App\Http\Requests;

use App\Blocks\BlockTypeRegistry;
use App\Enums\BlockSize;
use App\Models\Block;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('block')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Block $block */
        $block = $this->route('block');

        $rules = [
            'size' => ['sometimes', Rule::enum(BlockSize::class)],
            'isVisible' => ['sometimes', 'boolean'],
        ];

        // Solo se validan los campos de `data` si el request los envía
        // (los toggles de tamaño/visibilidad no incluyen `data`).
        if ($this->has('data')) {
            $rules = array_merge($rules, BlockTypeRegistry::rules($block->type));
        }

        return $rules;
    }
}
