# TODO — Match-3 CLI

## Done

| Item | Status |
|---|---|
| Issue 1 — `processCascade()` toClear set-subtraction | ✅ |
| Issue 2 — HUD always emits 4 lines, click offset stable | ✅ |
| Issue 4 — Duplicate adjacency check removed | ✅ |
| Issue 5 — Timer drift eliminated (shared `elapsedSeconds`) | ✅ |
| Issue 6 — Redundant `enableRawMode` in `Game::play()` | ✅ |
| Test gaps — `GameTest.php` (9 tests), `$disableAnimations` flag | ✅ |
| Issue 3 — Score-only goals confirmed, branch deleted, docs updated | ❌ won't fix |

---

## Remaining

### Polish / UX (high impact, low code)

- **Level-complete splash** ✅
  "LEVEL CLEAR!" banner overlaid on grid rows 3-4 with a solid separator
  for 1.2 seconds before advancing. Uses `str_pad` for centring.

- **Game-over stats screen** ✅
  Shows score, level, moves (valid/invalid), time played, longest cascade,
  and biggest clear in a formatted panel before the high-score prompt.

- **Pause key**
  No way to pause in timer mode. A `p` key (mapped in all three presets)
  that freezes the timer and overlays "PAUSED" until any key is pressed.

- **High-score file resilience**
  `data/high_scores.json` is read with graceful-empty on missing/corrupt,
  but there's no atomic write (partial write on crash corrupts the file)
  and no write-lock guard. Rare, but scores would be lost silently.

### Architecture (nice-to-have, moderate refactor)

- **Constructor injection for Game dependencies**
  `Grid`, `Level`, `Renderer`, `Input` are all instantiated inside
  `Game::__construct()`. Accepting them as optional parameters would let
  standalone unit tests verify Game-specific logic (e.g. the
  `attemptSwap` priority chain — currently only testable via reflection).

### Skipped (out of scope for a terminal game of this size)

- Multi-theme colour schemes / unicode vs ascii fallback
- Replay system / move history
- Network leaderboard
- Configuration beyond `data/settings.json`

---

## Suggested next

**Level-complete splash** — most visible UX improvement for the least code.
~50 lines: a `renderSplash()` method, new `renderAndWait` call in
`attemptSwap()` after `level->next()`, and a brief flash animation.
