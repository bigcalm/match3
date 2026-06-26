<?php

namespace Match3\Tests;

use Match3\Level;
use PHPUnit\Framework\TestCase;

class LevelTest extends TestCase
{
    public function testFirstLevelDefaults(): void
    {
        $level = new Level(1);
        $this->assertSame(1, $level->getNumber());
        $this->assertSame(7, $level->getGemTypes());
        $this->assertSame(40, $level->getMoveLimit());
    }

    public function testLevelIsNotCompleteAtZeroScore(): void
    {
        $level = new Level(1);
        $this->assertFalse($level->isComplete(['score' => 0]));
    }

    public function testLevelIsCompleteAtTargetScore(): void
    {
        $level = new Level(1);
        $this->assertTrue($level->isComplete(['score' => 200]));
    }

    public function testLevelIsCompleteAboveTarget(): void
    {
        $level = new Level(1);
        $this->assertTrue($level->isComplete(['score' => 500]));
    }

    public function testNextLevelAdvances(): void
    {
        $level = new Level(1);
        $next = $level->next();
        $this->assertNotNull($next);
        $this->assertSame(2, $next->getNumber());
    }

    public function testNextReturnsNullAfterLastLevel(): void
    {
        $level = new Level(20);
        $this->assertNull($level->next());
    }

    public function testLevelClampedToRange(): void
    {
        $level = new Level(99);
        $this->assertSame(20, $level->getNumber());
    }

    public function testLevelClampedBelowOne(): void
    {
        $level = new Level(0);
        $this->assertSame(1, $level->getNumber());
    }

    public function testGoalTarget(): void
    {
        $level = new Level(1);
        $this->assertSame(200, $level->getGoalTarget(0));
    }

    public function testGoalDescription(): void
    {
        $level = new Level(1);
        $this->assertStringContainsString('Score', $level->getGoalDescription(0));
    }

    public function testGoalProgress(): void
    {
        $level = new Level(1);
        $this->assertSame(50, $level->getGoalProgress(0, ['score' => 50]));
    }

    public function testGoalProgressCapped(): void
    {
        $level = new Level(1);
        $this->assertSame(200, $level->getGoalProgress(0, ['score' => 999]));
    }

    public function testTotalLevels(): void
    {
        $this->assertSame(20, Level::count());
    }

    public function testLaterLevelHarder(): void
    {
        $early = new Level(1);
        $late = new Level(15);
        $this->assertGreaterThan($late->getMoveLimit(), $early->getMoveLimit());
        $this->assertLessThan($late->getGoalTarget(0), $early->getGoalTarget(0));
    }
}
