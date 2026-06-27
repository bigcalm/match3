<?php

namespace Match3\Tests;

use Match3\Game;
use Match3\Grid;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
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
        $game = new Game(mode: 'moves');
        $ref = new \ReflectionClass($game);

        $gridProp = $ref->getProperty('grid');
        $gridProp->setAccessible(true);
        $grid = $gridProp->getValue($game);
        $this->setGridNoMatch($grid);
        $grid->setCell(0, 0, 1);
        $grid->setCell(0, 1, 1);
        $grid->setCell(0, 2, 2);
        $grid->setCell(1, 2, 1);

        $scoreProp = $ref->getProperty('score');
        $scoreProp->setAccessible(true);
        $scoreProp->setValue($game, 180);

        $movesProp = $ref->getProperty('movesUsed');
        $movesProp->setAccessible(true);
        $movesProp->setValue($game, 39);

        $method = $ref->getMethod('attemptSwap');
        $method->setAccessible(true);

        Game::$disableAnimations = true;
        ob_start();
        $method->invoke($game, 0, 2, 1, 2);
        ob_end_clean();
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
        $game = new Game(mode: 'moves');
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
        ob_start();
        $result = $game->run();
        ob_end_clean();
        Game::$disableAnimations = false;

        $this->assertArrayHasKey('maxCascade', $result);
        $this->assertArrayHasKey('maxClear', $result);
        $this->assertArrayHasKey('timePlayed', $result);
        $this->assertSame(7, $result['maxCascade']);
        $this->assertSame(24, $result['maxClear']);
        $this->assertGreaterThanOrEqual(0, $result['timePlayed']);
    }
}
