<?php

namespace Domain\Employees\DataTransferObjects;

use Domain\Support\DataTransferObjects\BaseData;

class SkillData extends BaseData
{
    public function __construct(
        public string $name,
        public string $category,
        public bool $is_active,
        public ?int $id = null,
        public ?string $code = null,
        public ?string $description = null,
        public ?string $label_background_color = null,
        public ?string $label_text_color = null,
        public ?string $label_border_color = null,
    ) {}
}
