<?php

namespace Domain\Support\Singletons;

class LocationContext
{
    /**
     * Initialize the LocationContext with an optional location ID.
     *
     * @param  int|null  $locationId  The initial location ID.
     */
    public function __construct(
        private ?int $locationId = null
    ) {}

    /**
     * Get the current location context.
     *
     * @return int|null The current location ID, or null if no context is set.
     */
    public function getLocationKey(): ?int
    {
        return $this->locationId;
    }

    /**
     * Set the current location context.
     *
     * @param  int|null  $locationId  The location ID to set, or null to clear the context.
     */
    public function setLocationKey(?int $locationId): void
    {
        $this->locationId = $locationId;
    }

    /**
     * Execute a callback with a temporary location context.
     *
     * @param  int|null  $id  The temporary location ID to set.
     * @param  callable  $callback  The callback to execute.
     * @return mixed The result of the callback execution.
     */
    public function callback(?int $id, callable $callback): mixed
    {
        $previous = $this->locationId;
        $this->locationId = $id;

        try {
            return $callback();
        } finally {
            $this->locationId = $previous;
        }
    }
}
