<?php

namespace Match3;

class Game
{
    private Renderer $renderer;
    private Input $input;
    private Level $level;
    private Grid $grid;

    private int $cursorRow = 0;
    private int $cursorCol = 0;
    private int $selRow = -1;
    private int $selCol = -1;
    private int $score = 0;
    private int $validMoves = 0;
    private int $invalidMoves = 0;
    private int $movesUsed = 0;
    private string $preset;
    private string $mode;
    private int $startTime = 0;
    private bool $gameOver = false;

    public function __construct(string $preset = 'arrows', ?string $customBindings = null, string $mode = 'moves')
    {
        $this->preset = $preset;
        $this->mode = $mode;
        $this->renderer = new Renderer();

        $bindings = new KeyBindings($preset);

        if ($customBindings !== null) {
            $bindings->loadCustom($customBindings);
        }

        $this->input = new Input($bindings);
        $this->level = new Level(1);
        $this->grid = new Grid($this->level->getGemTypes());
    }

    public function run(): array
    {
        $this->startTime = hrtime(true);
        $lastRender = 0;

        while (true) {
            $now = hrtime(true);

            if ($this->mode === 'timer') {
                if ($now - $lastRender >= 200_000_000) {
                    $this->render();
                    $lastRender = $now;
                }

                if ($this->isTimeUp()) {
                    $this->gameOver = true;
                }
            } else {
                $this->render();
            }

            if ($this->gameOver) {
                break;
            }

            $timeout = ($this->mode === 'timer') ? 200_000 : null;
            $action = $this->input->getAction($timeout);

            if ($action === null) {
                continue;
            }

            if ($action === 'quit') {
                return [];
            }

            if (str_starts_with($action, 'click:')) {
                $this->handleClick($action);
                continue;
            }

            if ($action === 'select') {
                $this->handleSelect();
                continue;
            }

            if ($action === 'hint') {
                $this->handleHint();
                continue;
            }

            if ($action === 'leaderboard') {
                $this->handleLeaderboard();
                continue;
            }

            match ($action) {
                'up' => $this->cursorRow = max(0, $this->cursorRow - 1),
                'down' => $this->cursorRow = min(Grid::ROWS - 1, $this->cursorRow + 1),
                'left' => $this->cursorCol = max(0, $this->cursorCol - 1),
                'right' => $this->cursorCol = min(Grid::COLS - 1, $this->cursorCol + 1),
                default => null,
            };
        }

        return [
            'score' => $this->score,
            'level' => $this->level->getNumber(),
            'validMoves' => $this->validMoves,
            'invalidMoves' => $this->invalidMoves,
            'won' => $this->level->isComplete(['score' => $this->score]),
        ];
    }

    private function isTimeUp(): bool
    {
        if ($this->mode !== 'timer') {
            return false;
        }

        $elapsed = (int) ((hrtime(true) - $this->startTime) / 1_000_000_000);
        return $elapsed >= $this->level->getTimeLimit();
    }

    private function getTimeLeft(): int
    {
        $elapsed = (int) ((hrtime(true) - $this->startTime) / 1_000_000_000);
        return max(0, $this->level->getTimeLimit() - $elapsed);
    }

    private function render(): void
    {
        $hud = $this->buildHud();
        $footer = $this->buildFooter();
        echo $this->renderer->render($this->grid, $this->cursorRow, $this->cursorCol, $this->selRow, $this->selCol, [], $hud, $footer);
    }

    private function buildFooter(): string
    {
        $controls = match ($this->preset) {
            'arrows' => '←↑↓→',
            'wasd' => 'WASD',
            'hjkl' => 'HJKL',
            default => $this->preset,
        };

        return " Preset: {$this->preset}  |  Move: {$controls}  |  Select: Space  |  Hint: H/?  |  Quit: Q";
    }

    private function buildHud(): array
    {
        $hud = [
            'level' => $this->level->getNumber(),
            'score' => $this->score,
            'scoreGoal' => $this->level->getGoalTarget(0),
            'validMoves' => $this->validMoves,
            'invalidMoves' => $this->invalidMoves,
            'mode' => $this->mode,
        ];

        if ($this->mode === 'timer') {
            $hud['timeLeft'] = $this->getTimeLeft();
            $hud['timeTotal'] = $this->level->getTimeLimit();
        } else {
            $hud['movesLeft'] = $this->level->getMoveLimit() - $this->movesUsed;
            $hud['movesTotal'] = $this->level->getMoveLimit();
        }

        return $hud;
    }

    private function handleSelect(): void
    {
        if ($this->selRow === -1) {
            $this->selRow = $this->cursorRow;
            $this->selCol = $this->cursorCol;
        } elseif ($this->cursorRow === $this->selRow && $this->cursorCol === $this->selCol) {
            $this->selRow = -1;
            $this->selCol = -1;
        } elseif (abs($this->cursorRow - $this->selRow) + abs($this->cursorCol - $this->selCol) === 1) {
            $this->attemptSwap($this->selRow, $this->selCol, $this->cursorRow, $this->cursorCol);
            $this->selRow = -1;
            $this->selCol = -1;
        } else {
            $this->selRow = $this->cursorRow;
            $this->selCol = $this->cursorCol;
        }
    }

    private function handleClick(string $action): void
    {
        $parts = explode(':', $action);
        $termCol = (int) ($parts[1] ?? 0);
        $termRow = (int) ($parts[2] ?? 0);

        $col = intdiv($termCol - 2, 4);
        $row = intdiv($termRow - (2 + Renderer::HUD_LINES), 2);

        if ($row < 0 || $row >= Grid::ROWS || $col < 0 || $col >= Grid::COLS) {
            return;
        }

        $this->cursorRow = $row;
        $this->cursorCol = $col;

        if ($this->selRow === -1) {
            $this->selRow = $row;
            $this->selCol = $col;
            return;
        }

        if ($row === $this->selRow && $col === $this->selCol) {
            $this->selRow = -1;
            $this->selCol = -1;
            return;
        }

        $this->attemptSwap($this->selRow, $this->selCol, $this->cursorRow, $this->cursorCol);
        $this->selRow = -1;
        $this->selCol = -1;
    }

    private function handleHint(): void
    {
        $hint = $this->grid->findHint();

        if ($hint === null) {
            return;
        }

        $hud = $this->buildHud();
        $footer = $this->buildFooter();
        echo $this->renderer->render($this->grid, $this->cursorRow, $this->cursorCol, -1, -1, $hint, $hud, $footer);
        usleep(400000);
    }

    private function handleLeaderboard(): void
    {
        $board = new HighScoreBoard(mode: $this->mode);
        echo "\e[2J\e[H" . $board->render() . "\nPress any key to return...\n";

        while (true) {
            $action = $this->input->getAction();
            if ($action !== null) {
                break;
            }
        }
    }

    private function attemptSwap(int $r1, int $c1, int $r2, int $c2): void
    {
        if ($this->gameOver) {
            return;
        }

        if (!$this->grid->swap($r1, $c1, $r2, $c2)) {
            $this->invalidMoves++;
            return;
        }

        $this->validMoves++;
        $this->movesUsed++;

        $this->processCascade();

        if ($this->level->isComplete(['score' => $this->score])) {
            $next = $this->level->next();

            if ($next !== null) {
                $this->level = $next;
                $this->movesUsed = 0;
                $this->startTime = hrtime(true);
            } else {
                $this->gameOver = true;
            }
        } elseif (!$this->grid->hasValidMoves()) {
            $this->gameOver = true;
        } elseif ($this->mode === 'moves' && $this->movesUsed >= $this->level->getMoveLimit()) {
            $this->gameOver = true;
        }
    }

    private function processCascade(): void
    {
        $cascadeStep = 0;

        do {
            $matches = $this->grid->findMatches();

            if (empty($matches)) {
                break;
            }

            $stepScore = $this->scoreMatches($matches);
            $multiplier = 1 << $cascadeStep;
            $this->score += $stepScore * $multiplier;

            $hud = $this->buildHud();
            $footer = $this->buildFooter();

            for ($i = 0; $i < 3; $i++) {
                echo $this->renderer->render($this->grid, $this->cursorRow, $this->cursorCol, -1, -1, $matches, $hud, $footer);
                usleep(100000);
                echo $this->renderer->render($this->grid, $this->cursorRow, $this->cursorCol, -1, -1, [], $hud, $footer);
                usleep(100000);
            }

            $this->grid->removeMatches();
            $this->grid->applyGravity();
            $cascadeStep++;
        } while (true);
    }

    private function scoreMatches(array $matches): int
    {
        $set = [];

        foreach ($matches as [$r, $c]) {
            $set["$r,$c"] = true;
        }

        $visited = [];
        $total = 0;

        foreach ($matches as [$r, $c]) {
            if (isset($visited["$r,$c"])) {
                continue;
            }

            $queue = [[$r, $c]];
            $visited["$r,$c"] = true;
            $size = 0;

            while (!empty($queue)) {
                [$cr, $cc] = array_shift($queue);
                $size++;

                foreach ([[-1, 0], [1, 0], [0, -1], [0, 1]] as [$dr, $dc]) {
                    $nr = $cr + $dr;
                    $nc = $cc + $dc;

                    if (isset($set["$nr,$nc"]) && !isset($visited["$nr,$nc"])) {
                        $visited["$nr,$nc"] = true;
                        $queue[] = [$nr, $nc];
                    }
                }
            }

            $total += match (true) {
                $size >= 5 => 100,
                $size === 4 => 60,
                default => 30,
            };
        }

        return $total;
    }
}
