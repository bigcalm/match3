# Match-3 CLI Game

A terminal-based Match-3 puzzle game (Bejeweled / Candy Crush style) written in PHP with no external dependencies.

Swap adjacent gems to form lines of 3+, clear them, and trigger cascading matches. Play through 20 levels with goals, track your score, and compete on the high score board.

![Gameplay](screenshots/game.gif)

## Requirements

- PHP 8.3+

## Install

```bash
composer install
```

## Play

```bash
php bin/play
```

On launch you'll see a welcome screen where you can:

| Setting | Options | Change with |
|---|---|---|
| Game mode | `moves` (default) or `timer` | `←` `→` |
| Key preset | `arrows`, `wasd`, `hjkl` | `←` `→` |
| Mouse mode | `drag` or `click` | `←` `→` |
| Start / Leaderboard / Quit | select with `↑` `↓` + `Enter` |

Your settings persist across sessions via `data/settings.json`. Delete that file to reset to defaults.

### Welcome screen

| Action | Key |
|---|---|
| Navigate | `↑` `↓` |
| Change value | `←` `→` |
| Select | `Enter` |
| Quit | `Q` / `Escape` |

### In-game controls

**Common to all presets:** `Space` (select/swap), `Enter` (confirm), `?` (hint), `B` (leaderboard), `P` (pause, timer mode only), `Q` (quit), `Esc` (cancel selection).

| Preset | Up | Down | Left | Right |
|--------|----|------|------|-------|
| arrows | `↑` | `↓` | `←` | `→` |
| wasd | `W` | `S` | `A` | `D` |
| hjkl | `K` | `J` | `H` | `L` |

`wasd` and `hjkl` also accept `F` for swap.

You can also click gems with the mouse.

On game-over a stats screen shows score, level reached, moves (valid/invalid), longest cascade chain, biggest single clear, and time played (moves mode).

### Custom key bindings

Place a `data/custom_bindings.json` file to override any keys from your chosen preset. Only the keys you specify are overridden — all other preset keys keep working. Below are the default bindings so you know what to override.

| Action | arrows | wasd | hjkl |
|---|---|---|---|
| Up | `↑` (`\e[A`) | `W` | `K` |
| Down | `↓` (`\e[B`) | `S` | `J` |
| Left | `←` (`\e[D`) | `A` | `H` |
| Right | `→` (`\e[C`) | `D` | `L` |
| Select / swap | `Space` | `Space` / `F` | `Space` / `F` |
| Confirm | `Enter` | `Enter` | `Enter` |
| Cancel | `Escape` | `Escape` | `Escape` |
| Hint | `?` | `?` | `?` |
| Leaderboard | `B` | `B` | `B` |
| Pause | `P` | `P` | `P` |
| Quit | `Q` | `Q` | `Q` |

Recognised key names for custom bindings: `up`, `down`, `left`, `right`, `space`, `enter`, `escape`, `tab`, or any single character.

Actions: `up`, `down`, `left`, `right`, `select`, `swap`, `confirm`, `quit`, `hint`, `leaderboard`, `cancel`, `pause`.

Example `data/custom_bindings.json` that switches movement to `IJKL` while keeping everything else:

```json
{
    "i": "up", "j": "left", "k": "down", "l": "right"
}
```

## Rules

- Form a line of **3+ matching gems** horizontally or vertically to clear them.
- New gems fall from above, potentially creating **chain reactions** (cascades).
- Each level has a **score goal**. Reach it within the limit (moves or time) to advance.
- **20 levels** with increasing difficulty (fewer gem types, higher targets, fewer moves).
- **Running out of valid moves** or **exceeding the move/time limit** ends the game.
- **High scores** are saved to `data/high_scores.json` (with a `.bak` backup written after each save), split into separate boards for moves mode and timer mode. Tie-breaker: fewer invalid moves ranks higher.

### Scoring

| Match | Points |
|---|---|
| 3 gems | 30 |
| 4 gems | 60 |
| 5+ gems | 100 |

Each cascade step doubles the points (step 1 = ×1, step 2 = ×2, step 3 = ×4, …).

### Special gems

Matches of 4+ create special gems that stay on the board after clearing.

| Match | Creates | Appearance | Effect when matched again |
|---|---|---|---|---|
| 4 in a row | **Striped** | Underlined gem | Clears entire row or column |
| 5 in a row | **Bomb** | Bold `B` | Clears 3×3 area |
| 6+ in a row | **Hypercube** | `H` symbol | Swap with any gem to clear all of that colour |

Striped direction (horizontal vs vertical) matches the line that spawned it.
Hypercubes activate on swap rather than match — swap with an adjacent gem to clear every gem of that type on the board.

## Development

```bash
composer install           # Install dependencies (dev included)
php -l src/                # Lint all source files
./vendor/bin/phpunit       # Run tests (168 tests, PHPUnit 13)
```

## Project structure

```
src/
├── Game.php              # Main loop and state machine
├── Grid.php              # Gem grid, match detection, cascade, hints
├── HighScoreBoard.php    # Persistent high scores (JSON, split by mode)
├── Input.php             # Raw terminal input, mouse tracking
├── KeyBindings.php       # Key binding presets and custom maps
├── Level.php             # 20-level table with goals, move and time limits
├── Renderer.php          # ANSI frame rendering, HUD, controls footer
└── WelcomeScreen.php     # Interactive menu for mode and preset selection
data/
├── high_scores.json      # Persisted high scores (atomic write + .bak fallback)
└── settings.json         # Persisted welcome screen settings
tests/
├── FunctionalTest.php    # Integration tests
├── GameTest.php          # Game loop and stats tests
├── GridTest.php          # Grid logic tests
├── HighScoreBoardTest.php
├── InputTest.php         # Mouse SGR parsing, timeout
├── KeyBindingsTest.php
├── LevelTest.php
├── RendererTest.php      # Output structure, HUD, special gems
├── TestableInput.php     # Action-queue input stub for testing
└── WelcomeScreenTest.php # Menu navigation, settings persistence
bin/
├── play                  # Entry point (welcome screen + game loop)
├── leaderboard           # Standalone high score viewer
└── test-animation        # Animation test script
```

## License

MIT
