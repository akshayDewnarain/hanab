<?php

namespace Domain\Support\Actions;

use Domain\Auth\Models\User;

/**
 * @method execute
 */
abstract class BaseAction
{
    protected ?User $user = null;

    public static function make(): static
    {
        return new static;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
