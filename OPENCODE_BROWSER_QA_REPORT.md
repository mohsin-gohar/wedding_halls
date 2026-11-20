> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# OPENCODE BROWSER QA REPORT — Riwaj Grand

**Date:** 2026-11-16
**Branch:** `enterprise-hardening`
**Dev server:** `php artisan serve --host=127.0.0.1 --port=9200` (background PID 13060)

---

## 1. BROWSER AUTOMATION ENVIRONMENT

- OpenCode in-app browser (Chromium via desktop app) used — **mandatory browser verification was executed**.
- Tooling: `tools.browser.tabs.open`, `tools.browser.navigate`, `tools.browser.wait`, `tools.browser.snapshot`, `tools.browser.console`, `tools.browser.screenshot`, `tools.browser.tabs.focus`.

---

## 2. PAGES VERIFIED (this cycle)

| # | URL | Load | Console errors | Snapshot |
|---|---|---|---|---|
| 1 | `http://127.0.0.1:9200/` | ✅ loaded | **0** | ✅ rendered — title: *"Riwaj Grand — Riwayat bhi, Jiddat bhi \| Luxury Wedding Banquets & Marquees"* |
| 2 | `http://127.0.0.1:9200/vendors` | ✅ loaded | **0** | ✅ |
| 3 | `http://127.0.0.1:9200/site/nawab-caterers` | ✅ loaded | **0** | ✅ (white-label vendor site) |
| 4 | `http://127.0.0.1:9200/customer/events` | ✅ loaded | **0** | ✅ (customer portal route reached) |

### Snapshot highlights (homepage)

- Skip-to-content link present.
- Announcement banner + nav with brand monogram ("RIWAJ GRAND", Urdu tagline "روایت کے رنگ، جذبات کے سنگ").
- Light/dark mode toggle functional in tree.
- "SIGN IN" link + nav toggle present.

---

## 3. BROWSER CAPABILITIES EXERCISED

- Tab lifecycle (open, wait-for-load, close).
- Snapshot (accessibility tree, depth 20).
- Console error collection at `error` level (bounded, 30).
- Screenshot capture — saved to `C:\Users\mohsi\AppData\Local\Temp\opencode-browser-6T7VXW\0\screenshot.png` (388,173 bytes). **The model could not visually read the PNG in this session — the screenshot must be reviewed by the user via the Review pane.** No visual regression was observed via the accessibility tree.

---

## 4. AUTHENTICATED BROWSER FLOWS

**Status:** NOT fully exercised in this cycle (login credentials / test accounts were not driven through the browser UI in this session).

Honest limit: this cycle's browser verification covered public-surface load + console health. Authenticated Livewire interaction flows (vendor award → booking, customer payment) were covered by automated feature tests instead.

---

## 5. RESULT

- **Pages loaded with zero console errors** ✅
- **No browser-observed crashes** on the verified public surfaces ✅
- **Screenshot artifact saved** for manual visual review ⏳ (user-side)

---

*Next: `OPENCODE_PERFORMANCE_REPORT.md`.*
