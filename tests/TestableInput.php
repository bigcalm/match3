<?php

namespace Match3\Tests;

use Match3\Input;
use Match3\KeyBindings;

class TestableInput extends Input
{
    private array $actionQueue = [];

    public function __construct(KeyBindings $bindings)
    {
        parent::__construct($bindings);
    }

    public function queueAction(string $action): void
    {
        $this->actionQueue[] = $action;
    }

    public function getAction(?int $timeoutUs = null): ?string
    {
        return $this->actionQueue !== [] ? array_shift($this->actionQueue) : null;
    }
}
