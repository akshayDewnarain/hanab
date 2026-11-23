<?php

namespace Domain\Messages\Http\Requests\Concerns;

use Domain\Messages\Support\MessageDocument;

trait ValidatesMessageContent
{
    protected function prepareForValidation(): void
    {
        $body = $this->input('body');
        if (! is_string($body) || ! MessageDocument::looksLikeDocument($body)) {
            return;
        }

        $normalized = MessageDocument::normalize($body);
        if (is_string($normalized)) {
            $this->merge(['body' => $normalized]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function messageContentRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }
                if (MessageDocument::looksLikeDocument($value) && MessageDocument::normalize($value) === null) {
                    $fail('The message content is not valid.');

                    return;
                }
                $text = MessageDocument::plainText($value);
                if ($text === '') {
                    $fail('The message content is required.');
                }
                if (mb_strlen($text) > MessageDocument::MAX_TEXT) {
                    $fail('The message content is too long.');
                }
            }],
        ];
    }
}
