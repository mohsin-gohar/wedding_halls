<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #C6A15B; --ink: #171513; --cream: #F7F3EC; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Inter', system-ui, sans-serif; background: radial-gradient(1200px 600px at 50% -10%, #2a2620 0%, #0e0d0b 60%); color: var(--cream); display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .card { max-width: 640px; text-align: center; }
        .code { font-family: 'Playfair Display', serif; font-size: clamp(6rem, 18vw, 10rem); line-height: 1; color: var(--gold); font-weight: 700; letter-spacing: -0.02em; }
        h1 { font-family: 'Playfair Display', serif; font-size: 1.75rem; margin: 1rem 0 0.5rem; font-weight: 500; }
        p { color: #c9c2b6; line-height: 1.6; margin: 0 0 2rem; }
        .btn { display: inline-block; padding: 0.85rem 1.75rem; border: 1px solid var(--gold); color: var(--gold); text-decoration: none; font-size: 0.85rem; letter-spacing: 0.12em; text-transform: uppercase; font-weight: 600; transition: all .25s; }
        .btn:hover { background: var(--gold); color: var(--ink); }
        .ornament { color: var(--gold); font-size: 1.25rem; letter-spacing: 0.4em; margin-bottom: 1rem; }
        .ref { font-family: monospace; font-size: 0.75rem; color: #7a7265; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="ornament">✦ ✦ ✦</div>
        <div class="code">500</div>
        <h1>Something went wrong behind the scenes.</h1>
        <p>Our team has been notified. Please try again in a moment, or return home to continue planning your event.</p>
        <a class="btn" href="{{ url('/') }}">Return Home</a>
        @if (app()->bound('sentry') || config('app.debug'))
            <div class="ref">Reference: {{ request()->header('X-Request-Id', 'n/a') }}</div>
        @endif
    </div>
</body>
</html>