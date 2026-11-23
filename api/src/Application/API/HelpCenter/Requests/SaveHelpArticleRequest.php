<?php

namespace Application\API\HelpCenter\Requests;

use Domain\HelpCenter\Models\HelpArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHelpArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('POST') && ! str_contains($this->path(), '/publish');

        return [
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => [$creating ? 'required' : 'sometimes', 'array'],
            'category_id' => ['nullable', 'integer', 'exists:help_categories,id'],
            'keywords' => ['nullable', 'string', 'max:240'],
            'visibility' => ['sometimes', 'string', Rule::in([
                HelpArticle::VISIBILITY_PRIVATE,
                HelpArticle::VISIBILITY_AUTHENTICATED,
                HelpArticle::VISIBILITY_PUBLIC,
            ])],
            'context_key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9]+(\.[a-z0-9-]+)*$/'],
            'audience_permission' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9 _-]+$/'],
            'feature_path' => ['nullable', 'string', 'max:180'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'is_getting_started' => ['sometimes', 'boolean'],
            'review_due_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:180'],
            'version' => [$creating ? 'sometimes' : 'required', 'integer', 'min:1'],
        ];
    }
}
