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

    public function testStripedActivationClearsRow(): void
    {
        $grid = new Grid(7);

        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 7);
                $grid->setSpecial($r, $c, Grid::NONE);
            }
        }

        $grid->setCell(3, 5, 0);
        $grid->setSpecial(3, 5, Grid::STRIPED_H);
        $grid->setCell(3, 4, 0);
        $grid->setCell(3, 6, 0);

        $matches = $grid->findMatches();
        $this->assertGreaterThanOrEqual(3, count($matches));
        $this->assertSame(Grid::STRIPED_H, $grid->getSpecial(3, 5));

        $set = [];
        foreach ($matches as $pos) {
            $set["{$pos[0]},{$pos[1]}"] = true;
        }

        $activated = [];
        foreach ($matches as [$r, $c]) {
            $sp = $grid->getSpecial($r, $c);
            if ($sp !== Grid::NONE) {
                $extra = $grid->activateSpecial($r, $c);
                foreach ($extra as $ep) {
                    $ek = "{$ep[0]},{$ep[1]}";
                    if (!isset($set[$ek])) {
                        $activated[] = $ep;
                    }
                }
            }
        }

        $this->assertGreaterThan(0, count($activated));
        $this->assertSame(Grid::NONE, $grid->getSpecial(3, 5));

        $activatedSet = [];
        foreach ($activated as $ap) {
            $activatedSet["{$ap[0]},{$ap[1]}"] = true;
        }

        $this->assertTrue(isset($activatedSet["3,0"]));
        $this->assertTrue(isset($activatedSet["3,7"]));
        $this->assertFalse(isset($activatedSet["3,4"]));
    }

    public function testFiveMatchCreatesBombViaCascade(): void
    {
        $grid = new Grid(7);

        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, ($r + $c) % 7);
                $grid->setSpecial($r, $c, Grid::NONE);
            }
        }

        $grid->setCell(0, 0, 1);
        $grid->setCell(0, 1, 1);
        $grid->setCell(0, 2, 1);
        $grid->setCell(0, 3, 1);
        $grid->setCell(0, 4, 1);
        $grid->setCell(0, 5, 2);

        $matches = $grid->findMatches();
        $this->assertCount(5, $matches);

        $groups = $grid->groupMatches($matches);
        $this->assertCount(1, $groups);

        $pos = $grid->createSpecial($groups[0]);
        $this->assertSame(Grid::BOMB, $grid->getSpecial($pos[0], $pos[1]));

        $keep = [$pos];
        $grid->removeMatches($keep);
        $this->assertSame(-1, $grid->getCell(0, 1));
        $this->assertNotSame(-1, $grid->getCell($pos[0], $pos[1]));

        $grid->applyGravity();
        $this->assertSame(Grid::BOMB, $grid->getSpecial($pos[0], $pos[1]));
    }
}
