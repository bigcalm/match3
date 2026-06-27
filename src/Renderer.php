<?php

namespace Match3;

class Renderer
{
    /** Number of HUD lines rendered above the grid. */
    public const int HUD_LINES = 4;

    public const string ANSI_ESC = "\e";
    public const string ANSI_HOME = "\e[H";
    public const string ANSI_CLEAR = "\e[J";
    public const string ANSI_CLEAR_HOME = "\e[H\e[J";
    public const string ANSI_CLEAR_ALL_HOME = "\e[2J\e[H";
    public const string ANSI_RESET = "\e[0m";
    public const string ANSI_BOLD = "\e[1m";
    public const string ANSI_DIM = "\e[2m";
    public const string ANSI_UNDERLINE = "\e[4m";
    public const string ANSI_REVERSE = "\e[7m";
    public const string ANSI_YELLOW = "\e[33m";

    private const array GEMS = ['♦', '♥', '♣', '♠', '●', '▲', '◆', '★'];

    private const array COLORS = [31, 32, 33, 34, 35, 36, 37, 90];

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

        $out = self::ANSI_CLEAR_HOME;

        $out .= $this->renderHud($hud);

        $out .= $this->border('┌', '┐', '┬') . "\n";

        for ($r = 0; $r < Grid::ROWS; $r++) {
            $out .= '│';
            for ($c = 0; $c < Grid::COLS; $c++) {
                $gem = $grid->getCell($r, $c);

                if ($gem === -1) {
                    $out .= '   │';
                    continue;
                }

                $special = $grid->getSpecial($r, $c);
                $color = self::COLORS[$gem];
                $symbol = self::GEMS[$gem];

                if ($special === Grid::HYPERCUBE) {
                    $symbol = 'H';
                } elseif ($special === Grid::BOMB) {
                    $symbol = 'B';
                }

                $sel = $r === $cursorRow && $c === $cursorCol;
                $spc = $special === Grid::STRIPED_H || $special === Grid::STRIPED_V;
                $bomb = $special === Grid::BOMB;

                if (isset($highlightSet["$r,$c"])) {
                    $out .= " " . self::ANSI_BOLD . self::ANSI_REVERSE . "\e[{$color}m{$symbol}" . self::ANSI_RESET . " │";
                } elseif ($sel) {
                    $out .= " " . self::ANSI_REVERSE . "\e[{$color}m{$symbol}" . self::ANSI_RESET . " │";
                } elseif ($r === $selectedRow && $c === $selectedCol) {
                    $out .= " " . self::ANSI_UNDERLINE . "\e[{$color}m{$symbol}" . self::ANSI_RESET . " │";
                } elseif ($spc) {
                    $out .= " " . self::ANSI_UNDERLINE . "\e[{$color}m{$symbol}" . self::ANSI_RESET . " │";
                } elseif ($bomb) {
                    $out .= " " . self::ANSI_BOLD . "\e[{$color}m{$symbol}" . self::ANSI_RESET . " │";
                } else {
                    $out .= " \e[{$color}m{$symbol}" . self::ANSI_RESET . " │";
                }
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

        $pct = $scoreGoal > 0 ? min(1.0, $score / $scoreGoal) : 0.0;
        $barWidth = 20;
        $filled = (int) round($pct * $barWidth);
        $bar = str_repeat('▓', $filled) . str_repeat('░', $barWidth - $filled);
        $out .= sprintf(" Goal: Score          %s  %d/%d\n", $bar, $score, $scoreGoal);

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
