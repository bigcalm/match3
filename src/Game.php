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
    private string $mouseMode = 'drag';
    private int $startTime = 0;
    private int $elapsedSeconds = 0;
    private int $maxCascade = 0;
    private int $maxClear = 0;
    private bool $gameOver = false;

    public static bool $disableAnimations = false;

    private const int TIMER_RENDER_INTERVAL_NS = 200_000_000;
    private const int INPUT_TIMEOUT_US = 200_000;
    private const int HINT_DISPLAY_US = 400_000;
    private const int CASCADE_GAP_US = 300_000;
    private const int CASCADE_SETTLE_US = 200_000;
    private const int FLASH_ON_US = 250_000;
    private const int FLASH_OFF_US = 200_000;
    private const int SPLASH_DISPLAY_US = 1_200_000;
    private const int CLICK_COL_OFFSET = 2;
    private const int CLICK_COL_STRIDE = 4;
    private const int CLICK_ROW_OFFSET = 2;
    private const int CLICK_ROW_STRIDE = 2;

    public function __construct(string $preset = 'arrows', ?string $customBindings = null, string $mode = 'moves', string $mouseMode = 'drag')
    {
        $this->preset = $preset;
        $this->mode = $mode;
        $this->mouseMode = $mouseMode;
        $this->renderer = new Renderer();

        $bindings = new KeyBindings($preset);

        if ($customBindings !== null) {
            $bindings->loadCustom($customBindings);
        }

        $this->input = new Input($bindings);
        $this->input->setMouseMode($this->mouseMode);
        $this->level = new Level(1);
        $this->grid = new Grid($this->level->getGemTypes());
    }

    public static function play(string $preset = 'arrows', ?string $customBindings = null, string $mode = 'moves', string $mouseMode = 'drag'): void
    {
        Input::enableMouseTracking();

        $game = new self($preset, $customBindings, $mode, $mouseMode);
        $result = $game->run();

        Input::restoreTerminal();

        if (empty($result)) {
            return;
        }

        echo Renderer::ANSI_CLEAR_ALL_HOME;

        if ($result['won']) {
            echo Renderer::renderTitleBox('YOU WIN!') . "\n";
        } else {
            echo Renderer::renderTitleBox('GAME OVER') . "\n";
        }

        $min = intdiv($result['timePlayed'], 60);
        $sec = $result['timePlayed'] % 60;
        echo "  Score       {$result['score']}\n";
        echo "  Level       {$result['level']}\n";
        echo "  Moves       {$result['validMoves']} valid, {$result['invalidMoves']} invalid\n";

        if ($mode === 'moves') {
            $min = intdiv($result['timePlayed'], 60);
            $sec = $result['timePlayed'] % 60;
            echo "  Time        {$min}:" . str_pad($sec, 2, '0', STR_PAD_LEFT) . "\n";
        }
        echo "  Longest cascade   {$result['maxCascade']} steps\n";
        echo "  Biggest clear     {$result['maxClear']} gems\n\n";

        $board = new HighScoreBoard(mode: $mode);

        if ($board->isHighScore($result['score'])) {
            echo "New high score: {$result['score']}!\n";
            echo "Enter your name: ";
            $name = preg_replace('/[[:^print:]]/', '', trim(fgets(STDIN)));

            if ($name === '') {
                $name = 'Anonymous';
            }

            $board->add($name, $result['score'], $result['level'], $result['validMoves'], $result['invalidMoves']);
        }

        echo "\n" . $board->render();

        echo "\nPress Enter to return to menu...";
        fgets(STDIN);
    }

    public function run(): array
    {
        $this->startTime = hrtime(true);
        $lastRender = 0;

        while (true) {
            $now = hrtime(true);
            $this->elapsedSeconds = (int) (($now - $this->startTime) / 1_000_000_000);

            if ($this->mode === 'timer') {
                if ($now - $lastRender >= self::TIMER_RENDER_INTERVAL_NS) {
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

            $timeout = ($this->mode === 'timer') ? self::INPUT_TIMEOUT_US : null;
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

            if ($action === 'cancel') {
                $this->selRow = -1;
                $this->selCol = -1;
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

            if ($action === 'pause') {
                $this->handlePause();
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
            'maxCascade' => $this->maxCascade,
            'maxClear' => $this->maxClear,
            'timePlayed' => $this->elapsedSeconds,
        ];
    }

    private function isTimeUp(): bool
    {
        return $this->mode === 'timer' && $this->elapsedSeconds >= $this->level->getTimeLimit();
    }

    private function getTimeLeft(): int
    {
        return max(0, $this->level->getTimeLimit() - $this->elapsedSeconds);
    }

    private function render(): void
    {
        $hud = $this->buildHud();
        $footer = $this->buildFooter();
        echo $this->renderer->render($this->grid, $this->cursorRow, $this->cursorCol, $this->selRow, $this->selCol, [], $hud, $footer);

        if (!self::$disableAnimations) {
            if (ob_get_level()) { ob_flush(); }
            flush();
        }
    }

    private function buildFooter(): string
    {
        $controls = match ($this->preset) {
            'arrows' => '←↑↓→',
            'wasd' => 'WASD',
            'hjkl' => 'HJKL',
            default => $this->preset,
        };

        $mouseHint = $this->mouseMode === 'click' ? 'Mouse: click-click' : 'Mouse: click-drag';
        return " Preset: {$this->preset}  |  Move: {$controls}  |  Select: Space  |  {$mouseHint}  |  Hint: ?  |  Pause: P  |  Quit: Q";
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
        } else {
            $this->attemptSwap($this->selRow, $this->selCol, $this->cursorRow, $this->cursorCol);
            $this->selRow = -1;
            $this->selCol = -1;
        }
    }

    private function handleClick(string $action): void
    {
        $parts = explode(':', $action);
        $termCol = (int) ($parts[1] ?? 0);
        $termRow = (int) ($parts[2] ?? 0);

        $col = intdiv($termCol - self::CLICK_COL_OFFSET, self::CLICK_COL_STRIDE);
        $row = intdiv($termRow - (self::CLICK_ROW_OFFSET + Renderer::HUD_LINES), self::CLICK_ROW_STRIDE);

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
        $this->renderAndWait(self::HINT_DISPLAY_US, $hint, $hud, $footer);
    }

    private function handleLeaderboard(): void
    {
        $board = new HighScoreBoard(mode: $this->mode);
        echo Renderer::ANSI_CLEAR_ALL_HOME . $board->render() . "\nPress any key to return...\n";

        while (true) {
            $action = $this->input->getAction();
            if ($action !== null) {
                break;
            }
        }
    }

    private function handlePause(): void
    {
        if ($this->mode !== 'timer') {
            return;
        }

        echo Renderer::ANSI_CLEAR_ALL_HOME;
        echo Renderer::renderTitleBox('PAUSED') . "\n";
        echo Renderer::ANSI_DIM . "   Press any key to resume" . Renderer::ANSI_RESET . "\n";
        if (ob_get_level()) { ob_flush(); }
        flush();

        $pauseStart = hrtime(true);
        $this->input->readRawKey();
        $this->startTime += hrtime(true) - $pauseStart;
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

        $totalClearedThisMove = 0;

        if ($this->grid->hasPendingActivation()) {
            $activated = $this->grid->consumeActivation();
            $totalClearedThisMove += count($activated);
            $hud = $this->buildHud();
            $footer = $this->buildFooter();
            $this->animateFlash($activated, $hud, $footer);
            $this->grid->removeCells($activated);
            $this->renderAndWait(self::CASCADE_GAP_US, [], $hud, $footer);
            $this->grid->applyGravity();
            $this->renderAndWait(self::CASCADE_SETTLE_US, [], $hud, $footer);
        }

        $result = $this->processCascade();
        $totalClearedThisMove += $result['cleared'];
        $this->maxCascade = max($this->maxCascade, $result['steps']);
        $this->maxClear = max($this->maxClear, $totalClearedThisMove);

        if ($this->level->isComplete(['score' => $this->score])) {
            $hud = $this->buildHud();
            $footer = $this->buildFooter();
            $this->renderAndWait(self::SPLASH_DISPLAY_US, [], $hud, $footer, "LEVEL CLEAR!\nLv.{$this->level->getNumber()}");

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

    private function processCascade(): array
    {
        $cascadeStep = 0;
        $totalCleared = 0;

        do {
            $matches = $this->grid->findMatches();

            if (empty($matches)) {
                break;
            }

            $allCells = [];
            $set = [];

            foreach ($matches as $pos) {
                $key = "{$pos[0]},{$pos[1]}";
                $allCells[$key] = $pos;
                $set[$key] = true;
            }

            $activated = [];

            foreach ($matches as [$r, $c]) {
                $sp = $this->grid->getSpecial($r, $c);
                if ($sp !== Grid::NONE) {
                    $extra = $this->grid->activateSpecial($r, $c);
                    foreach ($extra as $ep) {
                        $ek = "{$ep[0]},{$ep[1]}";
                        if (!isset($set[$ek])) {
                            $allCells[$ek] = $ep;
                            $set[$ek] = true;
                            $activated[] = $ep;
                        }
                    }
                }
            }

            $origGroups = $this->grid->groupMatches($matches);
            $keep = [];

            foreach ($origGroups as $group) {
                $size = count($group);
                if ($size >= 4) {
                    $pos = $this->grid->createSpecial($group);
                    $keep[] = $pos;
                }
            }

            $stepScore = $this->scoreMatches(array_values($allCells));
            $multiplier = 1 << $cascadeStep;
            $this->score += $stepScore * $multiplier;

            $flashCells = array_merge($matches, $activated);
            $hud = $this->buildHud();
            $footer = $this->buildFooter();

            $this->animateFlash($flashCells, $hud, $footer);

            $toClear = $allCells;

            foreach ($keep as [$kr, $kc]) {
                unset($toClear["$kr,$kc"]);
            }

            $totalCleared += count($toClear);
            $this->grid->removeCells(array_values($toClear));

            if (!empty($toClear)) {
                $this->renderAndWait(self::CASCADE_GAP_US, [], $hud, $footer);
            }

            $this->grid->applyGravity();
            $this->renderAndWait(self::CASCADE_SETTLE_US, [], $hud, $footer);
            $cascadeStep++;
        } while (true);

        return ['steps' => $cascadeStep, 'cleared' => $totalCleared];
    }

    private function renderAndWait(int $durationUs, array $highlights, array $hud, string $footer, ?string $splash = null): void
    {
        $start = hrtime(true);
        echo $this->renderer->render($this->grid, $this->cursorRow, $this->cursorCol, -1, -1, $highlights, $hud, $footer, $splash);

        if (!self::$disableAnimations) {
            if (ob_get_level()) { ob_flush(); }
            flush();
            $elapsedUs = (int) ((hrtime(true) - $start) / 1_000);
            usleep(max(0, $durationUs - $elapsedUs));
        }
    }

    private function animateFlash(array $flashCells, array $hud, string $footer): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->renderAndWait(self::FLASH_ON_US, $flashCells, $hud, $footer);
            $this->renderAndWait(self::FLASH_OFF_US, [], $hud, $footer);
        }
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
