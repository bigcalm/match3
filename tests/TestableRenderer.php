<?php

namespace Match3\Tests;

use Match3\Grid;
use Match3\Renderer;

class TestableRenderer extends Renderer
{
    public array $lastRender = [];

    public function render(
        Grid $grid,
        int $cursorRow = 0,
        int $cursorCol = 0,
        int $selectedRow = -1,
        int $selectedCol = -1,
        array $highlights = [],
        array $hud = [],
        string $footer = '',
        ?string $splash = null,
    ): string {
        $this->lastRender = [
            'cursorRow' => $cursorRow,
            'cursorCol' => $cursorCol,
            'selectedRow' => $selectedRow,
            'selectedCol' => $selectedCol,
            'highlights' => $highlights,
            'hud' => $hud,
            'footer' => $footer,
            'splash' => $splash,
        ];

        return '';
    }
}
