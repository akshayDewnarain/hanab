<?php

namespace Domain\Support\Transfer;

/**
 * How one catalog model may be matched and imported.
 * Matching keys are the columns the database already treats as unique together.
 */
final class TransferProfile
{
    /**
     * @param  list<string>  $match
     * @param  list<string>  $strategies
     * @param  list<string>  $policies
     * @param  list<string>  $clearable
     */
    public function __construct(
        public array $match,
        public array $strategies,
        public string $defaultStrategy,
        public array $policies,
        public string $defaultPolicy,
        public bool $partial,
        public ?string $routeParameter = null,
        public array $clearable = [],
    ) {}

    /**
     * @return array{strategies: list<string>, default_strategy: string, policies: list<string>, default_policy: string, partial: bool}
     */
    public function describe(): array
    {
        return [
            'strategies' => $this->strategies,
            'default_strategy' => $this->defaultStrategy,
            'policies' => $this->policies,
            'default_policy' => $this->defaultPolicy,
            'partial' => $this->partial,
        ];
    }
}
