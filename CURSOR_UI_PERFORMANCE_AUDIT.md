> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# UI and performance

## 17 Nov 2026 measurements

- Desktop calendar at the default browser width: `scrollWidth` 1905, `clientWidth` 1905, no page overflow. Week view rendered.
- 360px: calendar page overflowed 513 vs 360 before the wrap fix. After the shell `min-w-0` change, payroll at 360px still overflowed 543 vs 360. Widths 390, 430, 768, 1024, and 1366 were not measured in this pass.
- Production build: `public/build/assets/app-CNySftVp.css` 199.38 kB (gzip 28.62 kB), `app-DbXcASJr.js` 53.11 kB (gzip 20.15 kB).
- Full suite duration 542.91s on SQLite memory. That is a test-runtime figure, not a page-latency figure. The PHP built-in server remains single-threaded and slow for Livewire.

## UI

Sale badges and the Agencies nav item were removed so the public site only sells event bookings. Palette tokens already used by the layouts (`#171513`, `#C6A15B`, `#F8F5EF`) were left in place. No full visual redesign was done.

A mojibake title on `PublicVenues` (`Marketplace â€”`) was replaced with a normal em dash.

## Performance

`php artisan serve` on this Windows machine has been slow (multi-second public pages). That is the built-in PHP server, not a measured N+1 fix. Homepage featured halls are cached. This pass did not add indexes or change queue workers.

Responsive checks at 360, 768, and 1920 were not run in this session.
