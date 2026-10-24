<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error — {{ config('app.name', 'EventHub') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #C6A15B; --ink: #171513; --cream: #F8F5EF; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: radial-gradient(1200px 600px at 50% -10%, #24201B 0%, #171513 65%); color: var(--cream); display: flex; align-items: center; justify-content: center; padding: 2rem; line-height: 1.5; }
        .card { max-width: 580px; text-align: center; background: rgba(28, 25, 23, 0.85); border: 1px solid rgba(198, 161, 91, 0.25); border-radius: 1rem; padding: 3rem 2rem; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.55); }
        .eyebrow { display: inline-block; padding: 0.35rem 0.9rem; border-radius: 9999px; border: 1px solid rgba(198, 161, 91, 0.35); background: rgba(198, 161, 91, 0.1); color: var(--gold); font-size: 0.7rem; letter-spacing: 0.16em; text-transform: uppercase; font-weight: 600; margin-bottom: 1rem; }
        .code { font-family: 'Cormorant Garamond', Georgia, serif; font-size: clamp(5rem, 15vw, 8.5rem); line-height: 1; color: var(--gold); font-weight: 600; letter-spacing: -0.02em; }
        h1 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 2rem; margin: 0.75rem 0 0.5rem; font-weight: 600; }
        p { color: #c9c2b6; font-size: 0.925rem; margin: 0 0 2rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.8rem 1.6rem; border-radius: 0.5rem; border: 1px solid var(--gold); background: var(--gold); color: var(--ink); text-decoration: none; font-size: 0.78rem; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 600; transition: all .2s ease; }
        .btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
        .ref { font-size: 0.75rem; color: #7a7265; margin-top: 1.5rem; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <span class="eyebrow">Service Notice</span>
        <div class="code">500</div>
        <h1>Something went wrong behind the scenes.</h1>
        <p>Our engineering concierge has been notified. Please try again in a moment, or return home to continue planning your event.</p>
        <a class="btn" href="{{ url('/') }}">Return Home</a>
        @if (app()->bound('sentry') || config('app.debug'))
            <div class="ref">Reference: {{ request()->header('X-Request-Id', 'n/a') }}</div>
        @endif
    </main>
</body>
</html>