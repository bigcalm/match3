<?php

namespace Match3;

class Renderer
{
    private const array GEMS = ['♦', '♥', '♣', '♠', '●', '▲', '◆', '★'];

    private const array COLORS = [31, 32, 33, 34, 35, 36, 37, 90];

    public function render(
        Grid $grid,
        int $cursorRow = 0,
        int $cursorCol = 0,
        int $selectedRow = -1,
        int $selectedCol = -1,
        array $highlights = [],
    ): string {
        $highlightSet = [];

        foreach ($highlights as $pos) {
            $highlightSet["{$pos[0]},{$pos[1]}"] = true;
        }

        $out = "\e[H\e[J";

        $out .= $this->border('┌', '┐', '┬') . "\n";

        for ($r = 0; $r < Grid::ROWS; $r++) {
            $out .= '│';
            for ($c = 0; $c < Grid::COLS; $c++) {
                $gem = $grid->getCell($r, $c);
                $color = self::COLORS[$gem];
                $symbol = self::GEMS[$gem];

                if (isset($highlightSet["$r,$c"])) {
                    $out .= " \e[1m\e[7m\e[{$color}m{$symbol}\e[0m │";
                } elseif ($r === $cursorRow && $c === $cursorCol) {
                    $out .= " \e[7m\e[{$color}m{$symbol}\e[0m │";
                } elseif ($r === $selectedRow && $c === $selectedCol) {
                    $out .= " \e[4m\e[{$color}m{$symbol}\e[0m │";
                } else {
                    $out .= " \e[{$color}m{$symbol}\e[0m │";
                }
            }
            $out .= "\n";
            if ($r < Grid::ROWS - 1) {
                $out .= $this->border('├', '┤', '┼') . "\n";
            }
        }

        $out .= $this->border('└', '┘', '┴') . "\n";

        return $out;
    }

    private function border(string $left, string $right, string $tee): string
    {
        $segments = [];
        for ($c = 0; $c < Grid::COLS; $c++) {
            $segments[] = '───';
        }
        return $left . implode($tee, $segments) . $right;
    }
}
