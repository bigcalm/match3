<?php

namespace Match3;

class Level
{
    private const TABLE = [
        1 => ['gemTypes' => 7, 'moveLimit' => 40, 'timeLimit' => 120, 'goals' => [['type' => 'score', 'target' => 200]]],
        2 => ['gemTypes' => 7, 'moveLimit' => 38, 'timeLimit' => 114, 'goals' => [['type' => 'score', 'target' => 300]]],
        3 => ['gemTypes' => 7, 'moveLimit' => 36, 'timeLimit' => 108, 'goals' => [['type' => 'score', 'target' => 400]]],
        4 => ['gemTypes' => 7, 'moveLimit' => 34, 'timeLimit' => 102, 'goals' => [['type' => 'score', 'target' => 500]]],
        5 => ['gemTypes' => 7, 'moveLimit' => 32, 'timeLimit' => 96, 'goals' => [['type' => 'score', 'target' => 650]]],
        6 => ['gemTypes' => 6, 'moveLimit' => 32, 'timeLimit' => 90, 'goals' => [['type' => 'score', 'target' => 800]]],
        7 => ['gemTypes' => 6, 'moveLimit' => 30, 'timeLimit' => 84, 'goals' => [['type' => 'score', 'target' => 1000]]],
        8 => ['gemTypes' => 6, 'moveLimit' => 28, 'timeLimit' => 78, 'goals' => [['type' => 'score', 'target' => 1200]]],
        9 => ['gemTypes' => 6, 'moveLimit' => 26, 'timeLimit' => 72, 'goals' => [['type' => 'score', 'target' => 1400]]],
        10 => ['gemTypes' => 6, 'moveLimit' => 24, 'timeLimit' => 66, 'goals' => [['type' => 'score', 'target' => 1600]]],
        11 => ['gemTypes' => 5, 'moveLimit' => 26, 'timeLimit' => 65, 'goals' => [['type' => 'score', 'target' => 1800]]],
        12 => ['gemTypes' => 5, 'moveLimit' => 24, 'timeLimit' => 60, 'goals' => [['type' => 'score', 'target' => 2000]]],
        13 => ['gemTypes' => 5, 'moveLimit' => 22, 'timeLimit' => 55, 'goals' => [['type' => 'score', 'target' => 2500]]],
        14 => ['gemTypes' => 5, 'moveLimit' => 20, 'timeLimit' => 50, 'goals' => [['type' => 'score', 'target' => 3000]]],
        15 => ['gemTypes' => 5, 'moveLimit' => 18, 'timeLimit' => 45, 'goals' => [['type' => 'score', 'target' => 3500]]],
        16 => ['gemTypes' => 5, 'moveLimit' => 18, 'timeLimit' => 45, 'goals' => [['type' => 'score', 'target' => 4000]]],
        17 => ['gemTypes' => 5, 'moveLimit' => 16, 'timeLimit' => 40, 'goals' => [['type' => 'score', 'target' => 4500]]],
        18 => ['gemTypes' => 5, 'moveLimit' => 15, 'timeLimit' => 38, 'goals' => [['type' => 'score', 'target' => 5000]]],
        19 => ['gemTypes' => 5, 'moveLimit' => 14, 'timeLimit' => 35, 'goals' => [['type' => 'score', 'target' => 6000]]],
        20 => ['gemTypes' => 5, 'moveLimit' => 12, 'timeLimit' => 30, 'goals' => [['type' => 'score', 'target' => 8000]]],
    ];

    private int $number;
    private array $def;

    public function __construct(int $number = 1)
    {
        $this->number = min(max($number, 1), self::count());
        $this->def = self::TABLE[$this->number];
    }

    public static function count(): int
    {
        return count(self::TABLE);
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function getGemTypes(): int
    {
        return $this->def['gemTypes'];
    }

    public function getMoveLimit(): int
    {
        return $this->def['moveLimit'];
    }

    public function getTimeLimit(): int
    {
        return $this->def['timeLimit'];
    }

    public function getGoals(): array
    {
        return $this->def['goals'];
    }

    public function isComplete(array $state): bool
    {
        foreach ($this->def['goals'] as $goal) {
            if (!$this->isGoalMet($goal, $state)) {
                return false;
            }
        }

        return true;
    }

    public function getGoalDescription(int $index): string
    {
        $goal = $this->def['goals'][$index] ?? null;

        if ($goal === null) {
            return '';
        }

        return match ($goal['type']) {
            'score' => "Score: {$goal['target']} pts",
            default => '',
        };
    }

    public function getGoalProgress(int $index, array $state): int
    {
        $goal = $this->def['goals'][$index] ?? null;

        if ($goal === null) {
            return 0;
        }

        return match ($goal['type']) {
            'score' => min($state['score'] ?? 0, $goal['target']),
            default => 0,
        };
    }

    public function getGoalTarget(int $index): int
    {
        $goal = $this->def['goals'][$index] ?? null;
        return $goal['target'] ?? 0;
    }

    public function next(): ?self
    {
        $next = $this->number + 1;

        if ($next > self::count()) {
            return null;
        }

        return new self($next);
    }

    private function isGoalMet(array $goal, array $state): bool
    {
        return match ($goal['type']) {
            'score' => ($state['score'] ?? 0) >= $goal['target'],
            default => false,
        };
    }
}
