# UI and performance

## UI

Sale badges and the Agencies nav item were removed so the public site only sells event bookings. Palette tokens already used by the layouts (`#171513`, `#C6A15B`, `#F8F5EF`) were left in place. No full visual redesign was done.

A mojibake title on `PublicVenues` (`Marketplace â€”`) was replaced with a normal em dash.

## Performance

`php artisan serve` on this Windows machine has been slow (multi-second public pages). That is the built-in PHP server, not a measured N+1 fix. Homepage featured halls are cached. This pass did not add indexes or change queue workers.

Responsive checks at 360, 768, and 1920 were not run in this session.
