# Match-3 CLI Game — Build Plan

## Overview
A terminal-based Match-3 puzzle game (Bejeweled/Candy Crush style). The player swaps adjacent gems on a grid to form lines of 3+ matching gems, which are cleared and replaced by new gems falling from above.

---

## 1. Tech Stack

| Layer | Option |
|---|---|
| Language | PHP (CLI) |
| Terminal I/O | Readline + ANSI escape codes (or ncurses via `ncurses` extension) |
| Rendering | Frame-based redraw using ANSI cursor movement |
| No external dependencies | Pure PHP with built-in readline/terminal functions |

Alternative: Python + `curses` (simpler for terminal control).

---

## 2. Core Components

### 2.1 Grid (`Grid.php`)
- 2D array (e.g. 8×8) storing gem IDs.
- Constants: `ROWS`, `COLS`, `GEM_TYPES` (number of distinct gem kinds).
- On initialization, fill randomly, then resolve any pre-existing matches.

### 2.2 Gem
- Simple integer ID (0..N-1) or a small value object.
- Displayed as a coloured character (e.g. `♦ ♥ ♣ ♠ ● ▲ ◆ ★` or letters with ANSI foreground colours).

### 2.3 Input Handler (`Input.php`)
- Read input one event at a time (non-canonical terminal mode).
- **Keyboard** — resolves raw byte sequences against a binding map to produce actions (move-up, move-down, select, swap, quit, etc.).
- **Mouse** — click on a gem to select it; click on an adjacent gem to swap. Also supports dragging from one gem to another.
- Enable mouse tracking on start; disable on exit.

### 2.6 Key Bindings (`KeyBindings.php`)
- Stores a map of raw byte sequences → action names.
- Ships three built-in presets: `arrows`, `wasd`, `hjkl` (default: `arrows`).
- Allows user to select a preset or define custom bindings via a config file or in-game menu.
- Actions: `up`, `down`, `left`, `right`, `select`, `swap`, `quit`, `confirm`, `cancel`.

### 2.4 Renderer (`Renderer.php`)
- Clears screen and redraws the full grid each frame (or uses targeted ANSI moves).
- Draws cursor highlight, gem symbols with colour, score, and status line.
- Uses `stty -icanon -echo` to enter raw mode; restores on exit.

### 2.5 Game Engine (`Game.php`)
- Coordinates the game loop: input → logic → render.
- Manages game state (current level, score, valid/invalid move counters, turn timer).

### 2.7 Level (`Level.php`)
- Defines a level's parameters: grid size, gem types, target goals, move limit.
- Goal types: reach a target score, clear N gems of a specific colour, survive N cascades, or a mix.
- Progression: when goals are met, advance to the next level (harder parameters).
- Preset level table (e.g. 20 levels with increasing difficulty).

### 2.8 High Score Board (`HighScoreBoard.php`)
- Stores entries: `{name, score, level_reached, valid_moves, invalid_moves}`.
- Persisted to JSON file (`high_scores.json`).
- On game-over (no moves remain) or level fail, prompt for player name and save score.
- Displays top 10 entries sorted by score descending.

---

## 3. Game Logic

### 3.1 Swap
1. Player selects gem A, then adjacent gem B.
2. Temporarily swap A and B in the grid.
3. Check for matches. If none found, swap back (invalid move).

### 3.2 Match Detection
Algorithm:
```
for each row, scan horizontally for runs of 3+
for each col, scan vertically for runs of 3+
```
Collect all matched positions into a set. Runs can overlap.

### 3.3 Cascade (Gravity + Refill)
1. Remove matched gems (set their cell to null / empty).
2. For each column, shift remaining gems downward to fill gaps.
3. Fill empty top cells with new random gems.
4. Check for new matches (repeat until no matches remain).

### 3.4 Scoring
- 3-match: 30 pts
- 4-match: 60 pts
- 5-match: 100 pts
- Cascade multiplier: each successive cascade step doubles the per-match points.

### 3.5 Levels & Goals
- Each level has a goal definition (e.g. "score 500 pts", "clear 10 red gems").
- A HUD shows goal progress (e.g. `Goal: 340/500 pts` or `Red gems: 4/10`).
- When all goals are met, show a level-complete animation and advance.
- Level progression increases difficulty: fewer gem types, larger grid, higher targets, fewer moves.
- Game ends when either: no valid moves remain (game-over) OR move limit exceeded (level fail).

### 3.6 Move Tracking
- Valid move: swap produces ≥1 match → increment `validMoves`.
- Invalid move: swap produces no match (swap back) → increment `invalidMoves`.
- Counters displayed in the HUD. Used as tie-breakers on the high score board (fewer invalid moves ranks higher).

---

## 4. Terminal Handling

### Modes
- **Normal mode** — restore terminal on start/stop (`stty sane`).
- **Raw mode** — `stty -icanon -echo min 1 time 0` for single-key reads.
- **Mouse tracking** — emit `\e[?1000h\e[?1006h` to enable (button events + SGR coordinates). Disable on exit with `\e[?1000l\e[?1006l`.
- Use `register_shutdown_function` to restore terminal on crash.

### Colours (ANSI 256 / true colour)
Assign each gem type a distinct foreground colour:
```
0 → Red    (31)
1 → Green  (32)
2 → Yellow (33)
3 → Blue   (34)
4 → Magenta(35)
5 → Cyan   (36)
6 → White  (37)
7 → Grey   (90)
```

---

## 5. Key Binding Presets

Each preset maps physical keys to actions. Default preset is `arrows`.

### arrows
| Key | Action |
|---|---|
| `↑` / `↓` / `←` / `→` | Move cursor |
| `Space` | Select / swap |
| `Enter` | Confirm |
| `q` / `Escape` | Quit / back |

### wasd
| Key | Action |
|---|---|
| `w` / `a` / `s` / `d` | Move cursor (↑ ← ↓ →) |
| `Space` / `f` | Select / swap |
| `Enter` | Confirm |
| `q` / `Escape` | Quit / back |

### hjkl
| Key | Action |
|---|---|
| `k` / `h` / `j` / `l` | Move cursor (↑ ← ↓ →) |
| `Space` / `f` | Select / swap |
| `Enter` | Confirm |
| `q` / `Escape` | Quit / back |

### Custom
User writes a JSON config file mapping key names or byte sequences to actions:

```json
{
  "i": "up", "j": "left", "k": "down", "l": "right",
  " ": "select", "q": "quit"
}
```

---

## 6. Implementation Phases

### Phase 1 — Skeleton
- [x] Set up project structure (composer.json, autoloading PSR-4).
- [x] Create `Grid` class with random fill + `getCell`/`setCell`.
- [x] Create `Renderer` that prints the grid as coloured text.
- [x] Run a single frame: render and exit.

### Phase 2 — Input & Key Bindings
- [x] Implement raw terminal mode toggle.
- [x] Enable/disable mouse tracking (`\e[?1000h\e[?1006h` / `\e[?1000l\e[?1006l`).
- [x] `KeyBindings` class: load preset or custom JSON config; expose `getAction(bytes)` → action name.
- [x] `Input` class: reads raw bytes, passes to `KeyBindings::getAction()`, returns parsed action.
- [x] Parse mouse click SGR sequences (`\e[<btn>;<x>;<y>M`) and map to grid coordinates.
- [x] Overlay cursor on grid.

### Phase 3 — Swap & Match
- [x] Implement adjacency swap with validation.
- [x] Implement match detection (horizontal + vertical runs ≥ 3).
- [x] Animate/flash matched gems.

### Phase 4 — Cascade
- [x] Implement gravity (gems fall).
- [x] Implement refill (new random gems at top).
- [x] Loop cascade until board is stable.

### Phase 5 — Levels & Move Counters
- [x] `Level` class: load level definitions from a config array; expose `getGoals()`, `isComplete(state)`, `getMoveLimit()`.
- [x] Track valid and invalid moves in game state (`Game::$validMoves`, `Game::$invalidMoves`).
- [x] HUD display: goal progress bar, move counters, current level number.
- [x] Level-complete check after each cascade; advance to next level or trigger game-over.
- [x] Game-over: detect when no valid swaps remain (`Grid::hasValidMoves()`).

### Phase 6 — Polish
- [x] Score display and tracking.
- [x] High Score Board: load/save JSON, prompt for name on game-over, show top 10.
- [x] Restart / quit flow.
- [x] Graceful terminal restore on interrupt.

### Phase 7 — Optional Enhancements
- [ ] Special gems (bomb, hypercube, striped).
- [x] Timer mode vs. move-limited mode.
- [x] Leaderboard (save to file, mode-split boards).
- [ ] Sound effects via terminal bell (`\a`).

---

## 7. Data Flow

```
init: Grid::fillRandom(), Input::enableRawMode(), load Level(1)
loop (timer mode: non-blocking 200ms poll):
  Renderer::draw(grid, cursor, score, moves/time, level, goals)
  Input::getAction() → action
  on swap:
    Grid::swap()
    if Grid::findMatches(): validMoves++, cascade, score, check goals
    else: swap back, invalidMoves++
  check game-over (no moves, move limit hit, or time up)
  on game-over: HighScoreBoard::save()
cleanup: Input::restoreTerminal()
```

Startup flow:

```
bin/play
  └─ Input::enableRawMode()
  └─ WelcomeScreen::run()
       ├─ [Start] → Game::play(preset, mode) → results → back to menu
       ├─ [Leaderboard] → HighScoreBoard::renderAll() → back to menu
       └─ [Quit] → exit
```

---

## 8. File Structure

```
match3/
├── PLAN.md
├── composer.json
├── src/
│   ├── Game.php
│   ├── Grid.php
│   ├── HighScoreBoard.php
│   ├── Input.php
│   ├── KeyBindings.php
│   ├── Level.php
│   ├── Renderer.php
│   ├── WelcomeScreen.php  (interactive menu)
│   └── Gem.php            (optional value object)
├── data/
│   └── high_scores.json   (persisted high scores, mode-split)
├── bin/
│   ├── play               # entry-point script
│   └── leaderboard        # standalone high-score viewer
```

---

## 9. Risks & Notes

- **Terminal flicker** — full redraw is fine for 8×8 but could flicker at higher sizes. Use double-buffering (build a string then `echo` once).
- **Arrow keys** — send 3-byte sequences (`\e[A` etc). Input parser must handle multi-byte reads.
- **Race conditions** — none (single-threaded).
- **PHP readline** — `readline()` blocks for a full line. For single-key input, you must bypass readline and use `fread(STDIN, 1)` on a raw terminal.
- **Mouse** — SGR coordinates (mode 1006) are 1-based, grid rendering must account for any offset from status lines or borders. Dragging sends separate press/release events which can be used for a click-drag-release swap flow.
- **Key binding conflicts** — certain multi-byte sequences can collide (e.g. `\e` alone vs `\e[A`). The parser needs a short timeout after receiving `\e` to disambiguate escape from the start of a sequence.
- **Timer mode polling** — `Input::getAction(timeoutUs)` uses `stream_select` for non-blocking reads. The game loop polls every 200ms in timer mode to keep the countdown visible.
- **Level balancing** — goal targets, gem-type counts, and move limits need play-testing to feel fair. Start generous and tighten per level.
- **High score file** — `data/high_scores.json` could be deleted or corrupted; handle gracefully (start with empty board).
