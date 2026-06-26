<?php

namespace Match3;

class Grid
{
    public const ROWS = 8;
    public const COLS = 8;
    public const GEM_TYPES = 7;

    private array $grid = [];

    public function __construct()
    {
        $this->fillRandom();
    }

    public function fillRandom(): void
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                $this->grid[$r][$c] = random_int(0, self::GEM_TYPES - 1);
            }
        }

        $this->resolvePreExistingMatches();
    }

    private function resolvePreExistingMatches(): void
    {
        while ($matches = $this->findMatches()) {
            foreach ($matches as [$r, $c]) {
                $this->grid[$r][$c] = random_int(0, self::GEM_TYPES - 1);
            }
        }
    }

    public function findMatches(): array
    {
        $matched = [];

        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS - 2; $c++) {
                $gem = $this->grid[$r][$c];
                if ($this->grid[$r][$c + 1] === $gem && $this->grid[$r][$c + 2] === $gem) {
                    $end = $c + 2;
                    while ($end + 1 < self::COLS && $this->grid[$r][$end + 1] === $gem) {
                        $end++;
                    }
                    for ($i = $c; $i <= $end; $i++) {
                        $matched["$r,$i"] = [$r, $i];
                    }
                    $c = $end;
                }
            }
        }

        for ($c = 0; $c < self::COLS; $c++) {
            for ($r = 0; $r < self::ROWS - 2; $r++) {
                $gem = $this->grid[$r][$c];
                if ($this->grid[$r + 1][$c] === $gem && $this->grid[$r + 2][$c] === $gem) {
                    $end = $r + 2;
                    while ($end + 1 < self::ROWS && $this->grid[$end + 1][$c] === $gem) {
                        $end++;
                    }
                    for ($i = $r; $i <= $end; $i++) {
                        $matched["$i,$c"] = [$i, $c];
                    }
                    $r = $end;
                }
            }
        }

        return array_values($matched);
    }

    public function getCell(int $row, int $col): int
    {
        return $this->grid[$row][$col];
    }

    public function setCell(int $row, int $col, int $gem): void
    {
        $this->grid[$row][$col] = $gem;
    }
}
