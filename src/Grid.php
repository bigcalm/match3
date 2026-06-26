<?php

namespace Match3;

class Grid
{
    public const ROWS = 8;
    public const COLS = 8;

    public const int NONE = 0;
    public const int STRIPED_H = 1;
    public const int STRIPED_V = 2;
    public const int BOMB = 3;
    public const int HYPERCUBE = 4;

    private int $gemTypes;
    private array $grid = [];
    private array $special = [];

    public function __construct(int $gemTypes = 7)
    {
        $this->gemTypes = $gemTypes;
        $this->fillRandom();
    }

    public function fillRandom(): void
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                $this->grid[$r][$c] = random_int(0, $this->gemTypes - 1);
                $this->special[$r][$c] = self::NONE;
            }
        }

        $this->resolvePreExistingMatches();
    }

    private function resolvePreExistingMatches(): void
    {
        $maxIterations = 100;

        for ($i = 0; $i < $maxIterations; $i++) {
            $matches = $this->findMatches();

            if (empty($matches)) {
                return;
            }

            foreach ($matches as [$r, $c]) {
                $this->grid[$r][$c] = random_int(0, $this->gemTypes - 1);
            }
        }

        $this->fillWithoutMatches();

        if (!empty($this->findMatches())) {
            $this->fillAlternating();
        }
    }

    private function fillWithoutMatches(): void
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                $original = $this->grid[$r][$c];

                for ($try = 0; $try < $this->gemTypes; $try++) {
                    $this->grid[$r][$c] = ($original + $try) % $this->gemTypes;

                    $hasHorizontal = $c >= 2
                        && $this->grid[$r][$c] === $this->grid[$r][$c - 1]
                        && $this->grid[$r][$c] === $this->grid[$r][$c - 2];

                    $hasVertical = $r >= 2
                        && $this->grid[$r][$c] === $this->grid[$r - 1][$c]
                        && $this->grid[$r][$c] === $this->grid[$r - 2][$c];

                    if (!$hasHorizontal && !$hasVertical) {
                        break;
                    }
                }
            }
        }
    }

    private function fillAlternating(): void
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                $this->grid[$r][$c] = ($r + $c) % max(2, $this->gemTypes);
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

    public function getSpecial(int $row, int $col): int
    {
        return $this->special[$row][$col] ?? self::NONE;
    }

    public function setSpecial(int $row, int $col, int $type): void
    {
        $this->special[$row][$col] = $type;
    }

    public function swap(int $r1, int $c1, int $r2, int $c2): bool
    {
        if (abs($r1 - $r2) + abs($c1 - $c2) !== 1) {
            return false;
        }

        $h1 = $this->special[$r1][$c1] === self::HYPERCUBE;
        $h2 = $this->special[$r2][$c2] === self::HYPERCUBE;

        if ($h1 || $h2) {
            return $this->hypercubeSwap($r1, $c1, $r2, $c2, $h1);
        }

        $this->swapCells($r1, $c1, $r2, $c2);

        if (empty($this->findMatches())) {
            $this->swapCells($r1, $c1, $r2, $c2);
            return false;
        }

        return true;
    }

    private function hypercubeSwap(int $r1, int $c1, int $r2, int $c2, bool $hcFirst): bool
    {
        [$hr, $hc, $tr, $tc] = $hcFirst ? [$r1, $c1, $r2, $c2] : [$r2, $c2, $r1, $c1];

        $targetType = $this->grid[$tr][$tc];
        $this->special[$hr][$hc] = self::NONE;
        $this->special[$tr][$tc] = self::NONE;

        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                if ($this->grid[$r][$c] === $targetType) {
                    $this->grid[$r][$c] = -1;
                    $this->special[$r][$c] = self::NONE;
                }
            }
        }

        $this->applyGravity();

        return true;
    }

    public function removeMatches(?array $keepPositions = null): array
    {
        $matches = $this->findMatches();

        if (empty($matches)) {
            return [];
        }

        $keep = [];

        if ($keepPositions !== null) {
            foreach ($keepPositions as [$r, $c]) {
                $keep["$r,$c"] = true;
            }
        }

        foreach ($matches as [$r, $c]) {
            if (isset($keep["$r,$c"])) {
                continue;
            }
            $this->grid[$r][$c] = -1;
            $this->special[$r][$c] = self::NONE;
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
                    $this->special[$write][$c] = $this->special[$r][$c];
                    $write--;
                }
            }

            for ($r = $write; $r >= 0; $r--) {
                $this->grid[$r][$c] = random_int(0, $this->gemTypes - 1);
                $this->special[$r][$c] = self::NONE;
            }
        }
    }

    public function groupMatches(array $positions): array
    {
        $set = [];

        foreach ($positions as [$r, $c]) {
            $set["$r,$c"] = [$r, $c];
        }

        $groups = [];
        $visited = [];

        foreach ($set as $key => $pos) {
            if (isset($visited[$key])) {
                continue;
            }

            $group = [];
            $queue = [$pos];

            while (!empty($queue)) {
                [$cr, $cc] = array_shift($queue);
                $ck = "$cr,$cc";

                if (isset($visited[$ck])) {
                    continue;
                }

                $visited[$ck] = true;
                $group[] = [$cr, $cc];

                foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
                    $nr = $cr + $dr;
                    $nc = $cc + $dc;
                    $nk = "$nr,$nc";

                    if (isset($set[$nk]) && !isset($visited[$nk])) {
                        $queue[] = [$nr, $nc];
                    }
                }
            }

            $groups[] = $group;
        }

        return $groups;
    }

    public function createSpecial(array $group): array
    {
        $size = count($group);
        $pos = $group[array_key_first($group)];
        [$r, $c] = $pos;

        $type = match (true) {
            $size >= 6 => self::HYPERCUBE,
            $size >= 5 => self::BOMB,
            default => $this->determineStripedDirection($group),
        };

        $this->special[$r][$c] = $type;

        return [$r, $c];
    }

    private function determineStripedDirection(array $group): int
    {
        $rows = array_unique(array_map(fn(array $p): int => $p[0], $group));
        return count($rows) === 1 ? self::STRIPED_H : self::STRIPED_V;
    }

    public function activateSpecial(int $r, int $c): array
    {
        $type = $this->special[$r][$c];

        if ($type === self::NONE) {
            return [];
        }

        $this->special[$r][$c] = self::NONE;
        $cleared = [];
        $gem = $this->grid[$r][$c];

        if ($type === self::STRIPED_H) {
            for ($col = 0; $col < self::COLS; $col++) {
                $cleared["$r,$col"] = [$r, $col];
            }
        } elseif ($type === self::STRIPED_V) {
            for ($row = 0; $row < self::ROWS; $row++) {
                $cleared["$row,$c"] = [$row, $c];
            }
        } elseif ($type === self::BOMB) {
            for ($dr = -1; $dr <= 1; $dr++) {
                for ($dc = -1; $dc <= 1; $dc++) {
                    $nr = $r + $dr;
                    $nc = $c + $dc;
                    if ($nr >= 0 && $nr < self::ROWS && $nc >= 0 && $nc < self::COLS) {
                        $cleared["$nr,$nc"] = [$nr, $nc];
                    }
                }
            }
        } elseif ($type === self::HYPERCUBE) {
            for ($row = 0; $row < self::ROWS; $row++) {
                for ($col = 0; $col < self::COLS; $col++) {
                    if ($this->grid[$row][$col] === $gem) {
                        $cleared["$row,$col"] = [$row, $col];
                    }
                }
            }
        }

        return array_values($cleared);
    }

    public function removeCells(array $positions): void
    {
        foreach ($positions as [$r, $c]) {
            $this->grid[$r][$c] = -1;
            $this->special[$r][$c] = self::NONE;
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

    public function hasValidMoves(): bool
    {
        return $this->findHint() !== null;
    }

    public function findHint(): ?array
    {
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                if ($c + 1 < self::COLS && $this->swapWouldMatch($r, $c, $r, $c + 1)) {
                    return [[$r, $c], [$r, $c + 1]];
                }

                if ($r + 1 < self::ROWS && $this->swapWouldMatch($r, $c, $r + 1, $c)) {
                    return [[$r, $c], [$r + 1, $c]];
                }
            }
        }

        return null;
    }

    private function swapWouldMatch(int $r1, int $c1, int $r2, int $c2): bool
    {
        $this->swapCells($r1, $c1, $r2, $c2);
        $hasMatch = !empty($this->findMatches());
        $this->swapCells($r1, $c1, $r2, $c2);
        return $hasMatch;
    }

    private function swapCells(int $r1, int $c1, int $r2, int $c2): void
    {
        $temp = $this->grid[$r1][$c1];
        $this->grid[$r1][$c1] = $this->grid[$r2][$c2];
        $this->grid[$r2][$c2] = $temp;

        $tempSp = $this->special[$r1][$c1];
        $this->special[$r1][$c1] = $this->special[$r2][$c2];
        $this->special[$r2][$c2] = $tempSp;
    }
}
