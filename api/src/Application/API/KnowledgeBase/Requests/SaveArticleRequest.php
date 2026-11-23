<?php

namespace Application\API\KnowledgeBase\Requests;

use Domain\KnowledgeBase\Models\KnowledgeArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [$this->isMethod('post') ? 'required' : 'sometimes', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'array'],
            'category_id' => ['nullable', 'integer', 'exists:knowledge_categories,id'],
            'tags' => ['array', 'max:12'],
            'tags.*' => ['string', 'max:40'],
            'visibility' => ['sometimes', Rule::in([
                KnowledgeArticle::VISIBILITY_PRIVATE,
                KnowledgeArticle::VISIBILITY_AUTHENTICATED,
                KnowledgeArticle::VISIBILITY_PUBLIC,
            ])],
            'featured' => ['sometimes', 'boolean'],
            'review_due_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:180'],
            'version' => [$this->isMethod('post') ? 'nullable' : 'required', 'integer', 'min:1'],
            'skill_ids' => ['array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
            'certificate_ids' => ['array'],
            'certificate_ids.*' => ['integer', 'exists:certificates,id'],
        ];
    }
}
