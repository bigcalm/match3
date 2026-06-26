<?php

namespace Match3\Tests;

use Match3\Grid;
use PHPUnit\Framework\TestCase;

class FunctionalTest extends TestCase
{
    public function testFullCascadeFlow(): void
    {
        $grid = new Grid(7);

        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 7);
            }
        }

        $grid->setCell(0, 0, 3);
        $grid->setCell(0, 1, 3);
        $grid->setCell(0, 2, 6);
        $grid->setCell(1, 2, 3);

        $result = $grid->swap(0, 2, 1, 2);
        $this->assertTrue($result);

        $steps = 0;

        while (!empty($grid->findMatches())) {
            $grid->removeMatches();
            $grid->applyGravity();
            $steps++;
        }

        $this->assertGreaterThan(0, $steps);
        $this->assertEmpty($grid->findMatches());
    }

    public function testAlternatingGridHasNoMatches(): void
    {
        $grid = new Grid(2);

        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 2);
            }
        }

        $this->assertEmpty($grid->findMatches());
        $this->assertTrue($grid->hasValidMoves());
    }

    public function testGridCascadeReturnsStableBoard(): void
    {
        $grid = new Grid();
        $steps = $grid->cascade();
        $this->assertEmpty($grid->findMatches());
    }

    public function testSwapSwapsCellsThenRestores(): void
    {
        $grid = new Grid(7);

        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 7);
            }
        }

        $a = $grid->getCell(0, 0);
        $b = $grid->getCell(0, 1);

        $result = $grid->swap(0, 0, 0, 1);

        $this->assertFalse($result);
        $this->assertSame($a, $grid->getCell(0, 0));
        $this->assertSame($b, $grid->getCell(0, 1));
    }

    public function testMatchDetectionDeduplicatesOverlappingRuns(): void
    {
        $grid = new Grid(7);

        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, 0);
            }
        }

        $matches = $grid->findMatches();
        $this->assertGreaterThan(0, count($matches));

        $unique = [];

        foreach ($matches as [$r, $c]) {
            $key = "$r,$c";
            $this->assertArrayNotHasKey($key, $unique);
            $unique[$key] = true;
        }
    }
}
