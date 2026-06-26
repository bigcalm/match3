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

    public function swap(int $r1, int $c1, int $r2, int $c2): bool
    {
        if (abs($r1 - $r2) + abs($c1 - $c2) !== 1) {
            return false;
        }

        $this->swapCells($r1, $c1, $r2, $c2);

        if (empty($this->findMatches())) {
            $this->swapCells($r1, $c1, $r2, $c2);
            return false;
        }

        return true;
    }

    public function removeMatches(): array
    {
        $matches = $this->findMatches();

        if (empty($matches)) {
            return [];
        }

        foreach ($matches as [$r, $c]) {
            $this->grid[$r][$c] = -1;
        }

        return $matches;
    }

    public function applyGravity(): void
    {
        for ($c = 0; $c < self::COLS; $c++) {
            $write = self::ROWS - 1;

            for ($r = self::ROWS - 1; $r >= 0; $r--) {
                if ($this->grid[$r][$c] !== -1) {
                    $this->grid[$write][$c] = $this->grid[$r][$c];
                    $write--;
                }
            }

            for ($r = $write; $r >= 0; $r--) {
                $this->grid[$r][$c] = random_int(0, self::GEM_TYPES - 1);
            }
        }
    }

    public function cascade(): int
    {
        $steps = 0;

        while (!empty($this->findMatches())) {
            $this->removeMatches();
            $this->applyGravity();
            $steps++;
        }

        return $steps;
    }

    private function swapCells(int $r1, int $c1, int $r2, int $c2): void
    {
        $temp = $this->grid[$r1][$c1];
        $this->grid[$r1][$c1] = $this->grid[$r2][$c2];
        $this->grid[$r2][$c2] = $temp;
    }
}
