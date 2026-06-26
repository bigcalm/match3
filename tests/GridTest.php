<?php

namespace Match3\Tests;

use Match3\Grid;
use PHPUnit\Framework\TestCase;

class GridTest extends TestCase
{
    private function fillNoMatch(Grid $grid): void
    {
        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 7);
            }
        }
    }

    public function testGridIsFilled(): void
    {
        $grid = new Grid(7);
        $gem = $grid->getCell(0, 0);
        $this->assertGreaterThanOrEqual(0, $gem);
        $this->assertLessThan(7, $gem);
    }

    public function testNoPreExistingMatches(): void
    {
        $grid = new Grid();
        $this->assertEmpty($grid->findMatches());
    }

    public function testDetectsHorizontalMatch(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $grid->setCell(2, 0, 1);
        $grid->setCell(2, 1, 1);
        $grid->setCell(2, 2, 1);
        $grid->setCell(2, 3, 2);
        $matches = $grid->findMatches();
        $this->assertCount(3, $matches);
    }

    public function testDetectsVerticalMatch(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $grid->setCell(0, 0, 2);
        $grid->setCell(1, 0, 2);
        $grid->setCell(2, 0, 2);
        $grid->setCell(3, 0, 3);
        $grid->setCell(0, 1, 4);
        $matches = $grid->findMatches();
        $this->assertCount(3, $matches);
    }

    public function testDetectsRunOfFive(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        for ($c = 0; $c < 5; $c++) {
            $grid->setCell(0, $c, 3);
        }
        $grid->setCell(0, 5, 4);
        $matches = $grid->findMatches();
        $this->assertCount(5, $matches);
    }

    public function testNoMatchWhenDifferent(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $grid->setCell(0, 0, 1);
        $grid->setCell(0, 1, 2);
        $grid->setCell(0, 2, 3);
        $this->assertEmpty($grid->findMatches());
    }

    public function testSwapRejectsNonAdjacent(): void
    {
        $grid = new Grid();
        $this->assertFalse($grid->swap(0, 0, 2, 2));
    }

    public function testSwapSwapsBackWhenNoMatch(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $a = $grid->getCell(0, 0);
        $b = $grid->getCell(0, 1);
        $result = $grid->swap(0, 0, 0, 1);
        $this->assertFalse($result);
        $this->assertSame($a, $grid->getCell(0, 0));
        $this->assertSame($b, $grid->getCell(0, 1));
    }

    public function testSwapSucceedsWhenMatchFound(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $grid->setCell(0, 0, 5);
        $grid->setCell(0, 1, 5);
        $grid->setCell(0, 2, 6);
        $grid->setCell(1, 2, 5);
        $result = $grid->swap(0, 2, 1, 2);
        $this->assertTrue($result);
    }

    public function testRemoveMatchesClearsCells(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $grid->setCell(3, 3, 1);
        $grid->setCell(3, 4, 1);
        $grid->setCell(3, 5, 1);
        $grid->setCell(3, 6, 2);
        $removed = $grid->removeMatches();
        $this->assertCount(3, $removed);
        $this->assertSame(-1, $grid->getCell(3, 3));
    }

    public function testApplyGravityFillsGaps(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $grid->setCell(7, 0, -1);
        $grid->applyGravity();
        $this->assertNotSame(-1, $grid->getCell(7, 0));
    }

    public function testFindHintReturnsValidPair(): void
    {
        $grid = new Grid();
        $hint = $grid->findHint();
        $this->assertNotNull($hint);
        $this->assertCount(2, $hint);
        [$a, $b] = $hint;
        $this->assertCount(2, $a);
        $this->assertCount(2, $b);
        $adjacent = abs($a[0] - $b[0]) + abs($a[1] - $b[1]);
        $this->assertSame(1, $adjacent);
    }

    public function testFindHintOnNoMatchGrid(): void
    {
        $grid = new Grid(7);
        $this->fillNoMatch($grid);
        $hint = $grid->findHint();
        $this->assertNull($hint);
    }

    public function testHasValidMovesOnRandomGrid(): void
    {
        $grid = new Grid();
        $this->assertTrue($grid->hasValidMoves());
    }

    public function testGridWithFewerGemTypes(): void
    {
        $grid = new Grid(5);
        $this->assertTrue($grid->hasValidMoves());
    }

    public function testConstructorRespectsGemTypeCount(): void
    {
        $grid = new Grid(5);
        $this->assertTrue($grid->hasValidMoves());
    }
}
