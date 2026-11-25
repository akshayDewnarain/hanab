<?php

namespace Domain\Dashboard\DataTransferObjects;

/**
 * One automatic dashboard action. Completion is not stored: the action
 * disappears when the underlying count returns to zero.
 */
final class DashboardAction
{
    /**
     * @param  array<string, string>  $query
     */
    public function __construct(
        public readonly string $key,
        public readonly string $titleKey,
        public readonly string $descriptionKey,
        public readonly string $icon,
        public readonly int $count,
        public readonly string $priority,
        public readonly string $route,
        public readonly array $query,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'title_key' => $this->titleKey,
            'description_key' => $this->descriptionKey,
            'icon' => $this->icon,
            'count' => $this->count,
            'priority' => $this->priority,
            'target' => [
                'name' => $this->route,
                'query' => $this->query,
            ],
        ];
    }
}
