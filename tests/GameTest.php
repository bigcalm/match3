<?php

namespace Match3\Tests;

use Match3\Game;
use Match3\Grid;
use Match3\KeyBindings;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
    private function makeTestableGame(string $mode = 'moves', ?Grid $grid = null): array
    {
        $renderer = new TestableRenderer();
        $input = new TestableInput(new KeyBindings('arrows'));
        $game = new Game(mode: $mode, grid: $grid, renderer: $renderer, input: $input);
        return [$game, $renderer, $input];
    }

    private function setGridNoMatch(Grid $grid): void
    {
        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 7);
                $grid->setSpecial($r, $c, Grid::NONE);
            }
        }
    }

    public function testIsTimeUpFalseInMovesMode(): void
    {
        $game = new Game(mode: 'moves');
        $ref = new \ReflectionClass($game);

        $modeProp = $ref->getProperty('mode');
        $modeProp->setAccessible(true);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, 999);

        $method = $ref->getMethod('isTimeUp');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($game));
    }

    public function testIsTimeUpFalseWithinLimit(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, 0);

        $method = $ref->getMethod('isTimeUp');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($game));
    }

    public function testIsTimeUpTrueAtLimit(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $levelProp = $ref->getProperty('level');
        $levelProp->setAccessible(true);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, $levelProp->getValue($game)->getTimeLimit());

        $method = $ref->getMethod('isTimeUp');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($game));
    }

    public function testIsTimeUpTruePastLimit(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, 999);

        $method = $ref->getMethod('isTimeUp');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($game));
    }

    public function testGetTimeLeftReturnsLimitWhenNoElapsed(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $levelProp = $ref->getProperty('level');
        $levelProp->setAccessible(true);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, 0);

        $method = $ref->getMethod('getTimeLeft');
        $method->setAccessible(true);

        $this->assertSame($levelProp->getValue($game)->getTimeLimit(), $method->invoke($game));
    }

    public function testGetTimeLeftReturnsRemaining(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $levelProp = $ref->getProperty('level');
        $levelProp->setAccessible(true);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, 50);

        $method = $ref->getMethod('getTimeLeft');
        $method->setAccessible(true);

        $this->assertSame($levelProp->getValue($game)->getTimeLimit() - 50, $method->invoke($game));
    }

    public function testGetTimeLeftReturnsZeroAtLimit(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $levelProp = $ref->getProperty('level');
        $levelProp->setAccessible(true);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, $levelProp->getValue($game)->getTimeLimit());

        $method = $ref->getMethod('getTimeLeft');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke($game));
    }

    public function testGetTimeLeftReturnsZeroPastLimit(): void
    {
        $game = new Game(mode: 'timer');
        $ref = new \ReflectionClass($game);

        $elapsedProp = $ref->getProperty('elapsedSeconds');
        $elapsedProp->setAccessible(true);
        $elapsedProp->setValue($game, 999);

        $method = $ref->getMethod('getTimeLeft');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke($game));
    }

    public function testLevelCompletionTakesPriorityOverMoveLimit(): void
    {
        $grid = new Grid(7);
        $this->setGridNoMatch($grid);
        $grid->setCell(0, 0, 1);
        $grid->setCell(0, 1, 1);
        $grid->setCell(0, 2, 2);
        $grid->setCell(1, 2, 1);

        [$game] = $this->makeTestableGame(grid: $grid);
        $ref = new \ReflectionClass($game);

        $scoreProp = $ref->getProperty('score');
        $scoreProp->setAccessible(true);
        $scoreProp->setValue($game, 180);

        $movesProp = $ref->getProperty('movesUsed');
        $movesProp->setAccessible(true);
        $movesProp->setValue($game, 39);

        $method = $ref->getMethod('attemptSwap');
        $method->setAccessible(true);

        Game::$disableAnimations = true;
        $method->invoke($game, 0, 2, 1, 2);
        Game::$disableAnimations = false;

        $gameOverProp = $ref->getProperty('gameOver');
        $gameOverProp->setAccessible(true);
        $this->assertFalse($gameOverProp->getValue($game));

        $levelProp = $ref->getProperty('level');
        $levelProp->setAccessible(true);
        $this->assertSame(2, $levelProp->getValue($game)->getNumber());

        $this->assertSame(0, $movesProp->getValue($game));

        $maxCascadeProp = $ref->getProperty('maxCascade');
        $maxCascadeProp->setAccessible(true);
        $this->assertGreaterThan(0, $maxCascadeProp->getValue($game));

        $maxClearProp = $ref->getProperty('maxClear');
        $maxClearProp->setAccessible(true);
        $this->assertGreaterThan(0, $maxClearProp->getValue($game));
    }

    public function testRunReturnsAllStats(): void
    {
        [$game] = $this->makeTestableGame();
        $ref = new \ReflectionClass($game);

        $gameOverProp = $ref->getProperty('gameOver');
        $gameOverProp->setAccessible(true);
        $gameOverProp->setValue($game, true);

        $maxCascadeProp = $ref->getProperty('maxCascade');
        $maxCascadeProp->setAccessible(true);
        $maxCascadeProp->setValue($game, 7);

        $maxClearProp = $ref->getProperty('maxClear');
        $maxClearProp->setAccessible(true);
        $maxClearProp->setValue($game, 24);

        Game::$disableAnimations = true;
        $result = $game->run();
        Game::$disableAnimations = false;

        $this->assertArrayHasKey('maxCascade', $result);
        $this->assertArrayHasKey('maxClear', $result);
        $this->assertArrayHasKey('timePlayed', $result);
        $this->assertSame(7, $result['maxCascade']);
        $this->assertSame(24, $result['maxClear']);
        $this->assertGreaterThanOrEqual(0, $result['timePlayed']);
    }

    public function testHandlePauseReturnsEarlyInMovesMode(): void
    {
        $game = new Game(mode: 'moves');
        $ref = new \ReflectionClass($game);

        $modeProp = $ref->getProperty('mode');
        $modeProp->setAccessible(true);
        $this->assertSame('moves', $modeProp->getValue($game));

        $startTimeProp = $ref->getProperty('startTime');
        $startTimeProp->setAccessible(true);
        $before = $startTimeProp->getValue($game);

        $method = $ref->getMethod('handlePause');
        $method->setAccessible(true);
        $method->invoke($game);

        $this->assertSame($before, $startTimeProp->getValue($game));
    }
}
