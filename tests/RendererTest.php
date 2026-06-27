<?php

namespace Match3\Tests;

use Match3\Grid;
use Match3\Renderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    private function fillAll(Grid $grid, int $gem, ?int $special = null): void
    {
        for ($r = 0; $r < Grid::ROWS; $r++) {
            for ($c = 0; $c < Grid::COLS; $c++) {
                $grid->setCell($r, $c, $gem);
                if ($special !== null) {
                    $grid->setSpecial($r, $c, $special);
                }
            }
        }
    }

    // --- Structure ---

    public function testOutputStartsWithScreenClear(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringStartsWith("\e[H\e[J", $out);
    }

    public function testHasTopBorder(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString('┌───┬───┬───┬───┬───┬───┬───┬───┐', $out);
    }

    public function testHasBottomBorder(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString('└───┴───┴───┴───┴───┴───┴───┴───┘', $out);
    }

    public function testHasRowSeparators(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString('├───┼───┼───┼───┼───┼───┼───┼───┤', $out);
    }

    public function testContainsEightRows(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertSame(8, substr_count($out, "\n│"));
    }

    public function testContainsEightCellsPerRow(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertSame(64, substr_count($out, "\e[31m"));
    }

    // --- Gem symbols ---

    public static function provideGemSymbols(): array
    {
        return [
            [0, 31, '♦'],
            [1, 32, '♥'],
            [2, 33, '♣'],
            [3, 34, '♠'],
            [4, 35, '●'],
            [5, 36, '▲'],
            [6, 37, '◆'],
            [7, 90, '★'],
        ];
    }

    #[DataProvider('provideGemSymbols')]
    public function testRendersGemSymbolAndColor(int $gem, int $color, string $symbol): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, $gem);
        $r = new Renderer();
        $out = $r->render($grid);
        $expected = "\e[{$color}m{$symbol}\e[0m";
        $this->assertStringContainsString($expected, $out);
    }

    public function testEmptyCellRendersAsBlank(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $grid->setCell(3, 4, -1);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString("   │", $out);
    }

    // --- Cursor highlighting ---

    public function testCursorCellUsesReverseVideo(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 2, cursorCol: 3);
        $this->assertStringContainsString("\e[7m\e[31m♦\e[0m", $out);
    }

    public function testCursorAtZeroZero(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 0, cursorCol: 0);
        $this->assertStringContainsString("\e[7m\e[31m♦\e[0m", $out);
    }

    public function testNonCursorCellNotReversed(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 2, cursorCol: 2);
        $count = substr_count($out, "\e[7m\e[31m♦\e[0m");
        $this->assertSame(1, $count);
    }

    // --- Selected cell ---

    public function testSelectedCellUsesUnderline(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, selectedRow: 1, selectedCol: 2);
        $this->assertStringContainsString("\e[4m\e[31m♦\e[0m", $out);
    }

    public function testCursorOverridesSelected(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 3, cursorCol: 3, selectedRow: 3, selectedCol: 3);
        $this->assertStringContainsString("\e[7m\e[31m♦\e[0m", $out);
        $this->assertStringNotContainsString("\e[4m\e[31m♦\e[0m", $out);
    }

    // --- Highlights ---

    public function testHighlightedCellUsesBoldReverse(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, highlights: [[4, 5]]);
        $this->assertStringContainsString("\e[1m\e[7m\e[31m♦\e[0m", $out);
    }

    public function testMultipleHighlights(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, highlights: [[0, 0], [0, 1], [7, 7]]);
        $this->assertSame(3, substr_count($out, "\e[1m\e[7m\e[31m♦\e[0m"));
    }

    // --- Special gems ---

    public function testStripedGemUsesUnderline(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 1);
        $grid->setSpecial(2, 2, Grid::STRIPED_H);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString("\e[4m\e[32m♥\e[0m", $out);
    }

    public function testStripedVerticalGemUsesUnderline(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 1);
        $grid->setSpecial(5, 5, Grid::STRIPED_V);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString("\e[4m\e[32m♥\e[0m", $out);
    }

    public function testBombGemUsesBold(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 2);
        $grid->setSpecial(3, 3, Grid::BOMB);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString("\e[1m\e[33mB\e[0m", $out);
    }

    public function testHypercubeUsesHSymbol(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 3);
        $grid->setSpecial(0, 0, Grid::HYPERCUBE);
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringContainsString("\e[34mH\e[0m", $out);
    }

    public function testCursorOnStripedGemShowsCursorNotStriped(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $grid->setSpecial(1, 1, Grid::STRIPED_H);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 1, cursorCol: 1);
        $this->assertStringContainsString("\e[7m\e[31m♦\e[0m", $out);
    }

    public function testCursorOnBombGemShowsCursorNotBomb(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $grid->setSpecial(2, 2, Grid::BOMB);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 2, cursorCol: 2);
        $this->assertStringContainsString("\e[7m\e[31mB\e[0m", $out);
    }

    public function testCursorOnSelectedCell(): void
    {
        $grid = new Grid(7);
        $this->fillAll($grid, 0);
        $r = new Renderer();
        $out = $r->render($grid, cursorRow: 2, cursorCol: 2, selectedRow: 2, selectedCol: 2);
        $this->assertStringContainsString("\e[7m\e[31m♦\e[0m", $out);
        $this->assertStringNotContainsString("\e[4m\e[31m♦\e[0m", $out);
    }

    // --- HUD ---

    public function testHudShowsLevelAndScore(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 3,
            'score' => 150,
            'scoreGoal' => 500,
            'validMoves' => 5,
            'invalidMoves' => 2,
            'mode' => 'moves',
            'movesLeft' => 20,
            'movesTotal' => 30,
        ]);
        $this->assertStringContainsString('Level 3', $out);
        $this->assertStringContainsString('Score: 150/500', $out);
    }

    public function testHudShowsMovesInMovesMode(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 1,
            'score' => 0,
            'scoreGoal' => 200,
            'validMoves' => 10,
            'invalidMoves' => 1,
            'mode' => 'moves',
            'movesLeft' => 30,
            'movesTotal' => 40,
        ]);
        $this->assertStringContainsString('Moves: 30/40', $out);
        $this->assertStringContainsString('Valid: 10', $out);
        $this->assertStringContainsString('Invalid: 1', $out);
    }

    public function testHudShowsTimeInTimerMode(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 2,
            'score' => 100,
            'scoreGoal' => 300,
            'validMoves' => 3,
            'invalidMoves' => 0,
            'mode' => 'timer',
            'timeLeft' => 85,
            'timeTotal' => 120,
        ]);
        $this->assertStringContainsString('Time: 1:25/120', $out);
        $this->assertStringContainsString('Valid: 3', $out);
        $this->assertStringContainsString('Invalid: 0', $out);
    }

    public function testHudTimeFormatsZeroMinutes(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 1,
            'score' => 0,
            'scoreGoal' => 100,
            'validMoves' => 0,
            'invalidMoves' => 0,
            'mode' => 'timer',
            'timeLeft' => 45,
            'timeTotal' => 60,
        ]);
        $this->assertStringContainsString('0:45/60', $out);
    }

    public function testHudProgressBar(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 1,
            'score' => 100,
            'scoreGoal' => 200,
            'validMoves' => 0,
            'invalidMoves' => 0,
            'mode' => 'moves',
            'movesLeft' => 40,
            'movesTotal' => 40,
        ]);
        $this->assertStringContainsString('▓▓▓▓▓▓▓▓▓▓', $out);
        $this->assertStringContainsString('░░░░░░░░░░', $out);
    }

    public function testHudProgressBarFull(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 1,
            'score' => 200,
            'scoreGoal' => 200,
            'validMoves' => 0,
            'invalidMoves' => 0,
            'mode' => 'moves',
            'movesLeft' => 40,
            'movesTotal' => 40,
        ]);
        $this->assertStringContainsString('▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓', $out);
        $this->assertStringNotContainsString('░', $out);
    }

    public function testHudShowsEmptyProgressBarWhenNoGoal(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid, hud: [
            'level' => 1,
            'score' => 0,
            'scoreGoal' => 0,
            'validMoves' => 0,
            'invalidMoves' => 0,
            'mode' => 'moves',
            'movesLeft' => 40,
            'movesTotal' => 40,
        ]);
        $this->assertStringContainsString('Goal:', $out);
        $this->assertStringContainsString('0/0', $out);
    }

    // --- Footer ---

    public function testFooterAppendedWhenNonEmpty(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid, footer: 'Press Q to quit');
        $this->assertStringEndsWith("Press Q to quit\n", $out);
    }

    public function testFooterNotAppendedWhenEmpty(): void
    {
        $grid = new Grid(7);
        $r = new Renderer();
        $out = $r->render($grid, footer: '');
        $this->assertStringEndsWith("\n", $out);
    }

    // --- HUD_LINES constant ---

    public function testHudLinesConstant(): void
    {
        $this->assertSame(4, Renderer::HUD_LINES);
    }

    // --- Default parameter values ---

    public function testDefaultsRenderWithoutCrashing(): void
    {
        $grid = new Grid();
        $r = new Renderer();
        $out = $r->render($grid);
        $this->assertStringStartsWith("\e[H\e[J", $out);
        $this->assertStringContainsString('┌───┬', $out);
    }
}
