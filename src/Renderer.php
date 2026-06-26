<?php

namespace Match3;

class Renderer
{
    /** Number of HUD lines rendered above the grid. */
    public const int HUD_LINES = 4;

    private const array GEMS = ['♦', '♥', '♣', '♠', '●', '▲', '◆', '★'];

    private const array COLORS = [31, 32, 33, 34, 35, 36, 37, 90];

    private function gemStyle(int $gem, int $special, int $color, bool $highlight, bool $cursor, bool $selected): string
    {
        $symbol = self::GEMS[$gem];

        if ($special === Grid::HYPERCUBE) {
            $symbol = '✦';
        }

        $ansi = '';

        if ($highlight) {
            $ansi .= "\e[1m\e[7m";
        } elseif ($cursor) {
            $ansi .= "\e[7m";
        } elseif ($selected) {
            $ansi .= "\e[4m";
        }

        if ($special === Grid::STRIPED_H || $special === Grid::STRIPED_V) {
            $ansi .= "\e[4m";
        } elseif ($special === Grid::BOMB) {
            $ansi .= "\e[1m";
        }

        $ansi .= "\e[{$color}m{$symbol}\e[0m";
        return " {$ansi} │";
    }

    public function render(
        Grid $grid,
        int $cursorRow = 0,
        int $cursorCol = 0,
        int $selectedRow = -1,
        int $selectedCol = -1,
        array $highlights = [],
        array $hud = [],
        string $footer = '',
    ): string {
        $highlightSet = [];

        foreach ($highlights as $pos) {
            $highlightSet["{$pos[0]},{$pos[1]}"] = true;
        }

        $out = "\e[H\e[J";

        $out .= $this->renderHud($hud);

        $out .= $this->border('┌', '┐', '┬') . "\n";

        for ($r = 0; $r < Grid::ROWS; $r++) {
            $out .= '│';
            for ($c = 0; $c < Grid::COLS; $c++) {
                $gem = $grid->getCell($r, $c);
                $color = self::COLORS[$gem];

                $out .= $this->gemStyle(
                    $gem,
                    $grid->getSpecial($r, $c),
                    $color,
                    isset($highlightSet["$r,$c"]),
                    $r === $cursorRow && $c === $cursorCol,
                    $r === $selectedRow && $c === $selectedCol,
                );
            }
            $out .= "\n";
            if ($r < Grid::ROWS - 1) {
                $out .= $this->border('├', '┤', '┼') . "\n";
            }
        }

        $out .= $this->border('└', '┘', '┴') . "\n";

        if ($footer !== '') {
            $out .= $footer . "\n";
        }

        return $out;
    }

    private function renderHud(array $hud): string
    {
        $level = $hud['level'] ?? 1;
        $score = $hud['score'] ?? 0;
        $scoreGoal = $hud['scoreGoal'] ?? 0;
        $validMoves = $hud['validMoves'] ?? 0;
        $invalidMoves = $hud['invalidMoves'] ?? 0;
        $mode = $hud['mode'] ?? 'moves';

        $out = '';

        $out .= sprintf(" Level %-3d               Score: %d/%d\n", $level, $score, $scoreGoal);

        if ($mode === 'timer') {
            $timeLeft = $hud['timeLeft'] ?? 0;
            $timeTotal = $hud['timeTotal'] ?? 1;
            $min = intdiv($timeLeft, 60);
            $sec = $timeLeft % 60;
            $out .= sprintf(" Time: %d:%02d/%-10d   Valid: %d  Invalid: %d\n", $min, $sec, $timeTotal, $validMoves, $invalidMoves);
        } else {
            $movesLeft = $hud['movesLeft'] ?? 0;
            $movesTotal = $hud['movesTotal'] ?? 0;
            $out .= sprintf(" Moves: %d/%-10d   Valid: %d  Invalid: %d\n", $movesLeft, $movesTotal, $validMoves, $invalidMoves);
        }

        if ($scoreGoal > 0) {
            $pct = min(1.0, $score / $scoreGoal);
            $barWidth = 20;
            $filled = (int) round($pct * $barWidth);
            $bar = str_repeat('▓', $filled) . str_repeat('░', $barWidth - $filled);
            $out .= sprintf(" Goal: Score          %s  %d/%d\n", $bar, $score, $scoreGoal);
        }

        $out .= "\n";

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
