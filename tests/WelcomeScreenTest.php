<?php

namespace Match3\Tests;

use Match3\KeyBindings;
use Match3\WelcomeScreen;
use PHPUnit\Framework\TestCase;

class WelcomeScreenTest extends TestCase
{
    private string $settingsFile;

    protected function setUp(): void
    {
        $this->settingsFile = sys_get_temp_dir() . '/match3_settings_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->settingsFile)) {
            unlink($this->settingsFile);
        }
    }

    /** @return array{TestableInput, WelcomeScreen} */
    private function makeWelcome(string $preset = 'arrows'): array
    {
        $input = new TestableInput(new KeyBindings($preset));
        $welcome = new WelcomeScreen($input, $this->settingsFile);
        return [$input, $welcome];
    }

    private function runSilent(WelcomeScreen $welcome): array
    {
        ob_start();
        $result = $welcome->run();
        ob_end_clean();
        return $result;
    }

    // Default cursor is 0 (MODE_ROW).
    // ITEM_COUNT = 6: MODE_ROW=0, PRESET_ROW=1, MOUSE_ROW=2, START_ROW=3, LEADERBOARD_ROW=4, QUIT_ROW=5.

    public function testQuitKey(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('quit');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }

    public function testSelectStartFromDefault(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('moves', $result['mode']);
        $this->assertSame('arrows', $result['preset']);
        $this->assertSame('drag', $result['mouseMode']);
    }

    public function testNavigateDownToLeaderboardAndSelect(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $this->assertSame(['action' => 'leaderboard'], $this->runSilent($welcome));
    }

    public function testNavigateDownToQuitAndSelect(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }

    public function testNavigateUpFromDefaultStaysAtTop(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('up');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }

    public function testChangeModeToTimer(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('timer', $result['mode']);
    }

    public function testChangePresetToWasd(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('wasd', $result['preset']);
    }

    public function testChangePresetToHjkl(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('right');
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('hjkl', $result['preset']);
    }

    public function testConfirmKeyAlsoSelects(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('confirm');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('drag', $result['mouseMode']);
    }

    public function testLeftRightNoOpOnActionRows(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('left');
        $input->queueAction('right');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('drag', $result['mouseMode']);
    }

    public function testChangeMouseModeToClick(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('click', $result['mouseMode']);
    }

    public function testSettingsPersistAcrossSessions(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        // Change mode to timer, preset to wasd, mouse to click
        $input->queueAction('right');       // mode → timer
        $input->queueAction('down');        // to preset row
        $input->queueAction('right');       // preset → wasd
        $input->queueAction('down');        // to mouse row
        $input->queueAction('right');       // mouse → click
        $input->queueAction('down');        // to start
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('timer', $result['mode']);
        $this->assertSame('wasd', $result['preset']);
        $this->assertSame('click', $result['mouseMode']);

        // Create a new WelcomeScreen with the same settings file — should load saved values
        [$input2, $welcome2] = $this->makeWelcome();
        $input2->queueAction('down');
        $input2->queueAction('down');
        $input2->queueAction('down');
        $input2->queueAction('select');
        $result2 = $this->runSilent($welcome2);
        $this->assertSame('timer', $result2['mode']);
        $this->assertSame('wasd', $result2['preset']);
        $this->assertSame('click', $result2['mouseMode']);
    }

    public function testNullActionDoesNotCrash(): void
    {
        [$input, $welcome] = $this->makeWelcome();
        $input->queueAction('quit');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }
}
