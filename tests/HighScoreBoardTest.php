<?php

namespace Match3\Tests;

use Match3\HighScoreBoard;
use PHPUnit\Framework\TestCase;

class HighScoreBoardTest extends TestCase
{
    private string $testFile;

    protected function setUp(): void
    {
        $this->testFile = sys_get_temp_dir() . '/match3_test_scores_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testEmptyBoardHasNoEntries(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $this->assertEmpty($board->getTop(10));
    }

    public function testEmptyBoardIsHighScore(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $this->assertTrue($board->isHighScore(1));
    }

    public function testAddEntry(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $board->add('Alice', 500, 3, 15, 2);
        $top = $board->getTop(10);
        $this->assertCount(1, $top);
        $this->assertSame('Alice', $top[0]['name']);
        $this->assertSame(500, $top[0]['score']);
    }

    public function testSortsByScoreDescending(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $board->add('Alice', 300, 2, 10, 1);
        $board->add('Bob', 500, 3, 15, 2);
        $top = $board->getTop(10);
        $this->assertSame('Bob', $top[0]['name']);
        $this->assertSame('Alice', $top[1]['name']);
    }

    public function testTieBreakByFewerInvalidMoves(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $board->add('Alice', 500, 3, 15, 2);
        $board->add('Bob', 500, 3, 14, 0);
        $top = $board->getTop(10);
        $this->assertSame('Bob', $top[0]['name']);
        $this->assertSame('Alice', $top[1]['name']);
    }

    public function testIsHighScoreWhenNotFull(): void
    {
        $board = new HighScoreBoard($this->testFile);
        for ($i = 0; $i < 5; $i++) {
            $board->add("Player$i", 100 * ($i + 1), 1, 5, 0);
        }
        $this->assertTrue($board->isHighScore(50));
        $this->assertTrue($board->isHighScore(500));
    }

    public function testIsHighScoreWithFullBoard(): void
    {
        $board = new HighScoreBoard($this->testFile);
        for ($i = 0; $i < 10; $i++) {
            $board->add("Player$i", 100 * ($i + 1), 1, 5, 0);
        }
        $this->assertTrue($board->isHighScore(1001));
        $this->assertFalse($board->isHighScore(50));
    }

    public function testGetTopReturnsCorrectCount(): void
    {
        $board = new HighScoreBoard($this->testFile);
        for ($i = 0; $i < 15; $i++) {
            $board->add("Player$i", 100 * ($i + 1), 1, 5, 0);
        }
        $this->assertCount(10, $board->getTop(10));
    }

    public function testPersistsToFile(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $board->add('Alice', 500, 3, 15, 2);
        unset($board);

        $loaded = new HighScoreBoard($this->testFile);
        $top = $loaded->getTop(10);
        $this->assertCount(1, $top);
        $this->assertSame('Alice', $top[0]['name']);
    }

    public function testHandlesMissingFile(): void
    {
        $board = new HighScoreBoard('/nonexistent/path/to/file.json');
        $this->assertEmpty($board->getTop(10));
    }

    public function testHandlesCorruptFile(): void
    {
        file_put_contents($this->testFile, 'not valid json');
        $board = new HighScoreBoard($this->testFile);
        $this->assertEmpty($board->getTop(10));
    }

    public function testRenderReturnsString(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $render = $board->render();
        $this->assertNotNull($render);
    }

    public function testRenderShowsEntries(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $board->add('Alice', 500, 3, 15, 2);
        $render = $board->render();
        $this->assertStringContainsString('Alice', $render);
        $this->assertStringContainsString('500', $render);
    }

    public function testRenderEmpty(): void
    {
        $board = new HighScoreBoard($this->testFile);
        $render = $board->render();
        $this->assertStringContainsString('No high scores', $render);
    }
}
