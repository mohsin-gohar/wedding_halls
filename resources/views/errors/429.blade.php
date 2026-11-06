<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Protection — {{ config('app.name', 'EventHub') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #C6A15B; --gold-light: #DFC07A; --ink: #171513; --cream: #F8F5EF; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: radial-gradient(1200px 600px at 50% -10%, #24201B 0%, #171513 65%); color: var(--cream); display: flex; align-items: center; justify-content: center; padding: 2rem; line-height: 1.5; }
        .card { max-width: 600px; text-align: center; background: rgba(28, 25, 23, 0.92); border: 1px solid rgba(198, 161, 91, 0.35); border-radius: 1.25rem; padding: 3rem 2.25rem; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.7); backdrop-filter: blur(12px); }
        .eyebrow { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.4rem 1rem; border-radius: 9999px; border: 1px solid rgba(239, 68, 68, 0.4); background: rgba(239, 68, 68, 0.12); color: #f87171; font-size: 0.72rem; letter-spacing: 0.16em; text-transform: uppercase; font-weight: 700; margin-bottom: 1.25rem; }
        .code { font-family: 'Cormorant Garamond', Georgia, serif; font-size: clamp(4.5rem, 14vw, 7.5rem); line-height: 1; color: var(--gold); font-weight: 700; letter-spacing: -0.02em; }
        h1 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: clamp(1.75rem, 5vw, 2.25rem); margin: 0.75rem 0 0.5rem; font-weight: 700; color: #fff; }
        .subhead { color: var(--gold-light); font-size: 0.95rem; font-weight: 600; margin-bottom: 1rem; letter-spacing: 0.05em; }
        p { color: #d1c8b8; font-size: 0.92rem; margin: 0 0 1.5rem; line-height: 1.6; }
        .timer-box { background: rgba(198, 161, 91, 0.08); border: 1px dashed rgba(198, 161, 91, 0.35); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.75rem; font-size: 0.88rem; color: var(--cream); }
        .timer-val { font-size: 1.5rem; font-weight: 700; color: var(--gold); display: inline-block; min-width: 2.5rem; }
        .actions { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.85rem 1.75rem; border-radius: 0.5rem; border: 1px solid var(--gold); background: var(--gold); color: var(--ink); text-decoration: none; font-size: 0.8rem; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 700; transition: all .2s ease; cursor: pointer; }
        .btn:hover { filter: brightness(1.1); transform: translateY(-1px); }
        .btn.secondary { background: transparent; border-color: rgba(198, 161, 91, 0.4); color: var(--cream); }
        .btn.secondary:hover { border-color: var(--gold); color: var(--gold); }
        .btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
        .footer-note { font-size: 0.75rem; color: #8c8275; margin-top: 1.75rem; }
    </style>
</head>
<body>
    <main class="card" role="main">
        <span class="eyebrow">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            Server Protection — سرور پروٹیکشن
        </span>
        <div class="code">429</div>
        <h1>Aap website ko bar bar reload nahi kar saktay</h1>
        <div class="subhead">Too Many Rapid Reloads — Server Load Safeguard Active</div>
        <p>
            Website server par zyada load se bachne ke liye bar bar reload karne par pabandi lagayi gayi hai. Baraye meherbani thori der intezar karein.<br>
            <span style="font-size: 0.84rem; color: #a39887; display: block; margin-top: 0.4rem;">
                To protect system stability and prevent server overload, repeated rapid requests are rate-limited.
            </span>
        </p>

        @php
            $secondsRemaining = (int) ($retryAfter ?? ($exception->getHeaders()['Retry-After'] ?? 60));
            if ($secondsRemaining <= 0) {
                $secondsRemaining = 60;
            }
        @endphp

        <div class="timer-box">
            Dobara koshish karein (Try again in): <br>
            <span class="timer-val" id="countdown">{{ $secondsRemaining }}</span> <span>seconds</span>
        </div>

        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Return Home</a>
            <button class="btn secondary" id="reloadBtn" onclick="window.location.reload();" disabled>
                Reload Page (Dobara Koshish)
            </button>
            <a class="btn secondary" href="{{ url('/venues') }}">Explore Venues</a>
        </div>

        <div class="footer-note">
            EventHub Security Guard • IP: {{ request()->ip() }} • Protected by Enterprise Rate Limiter
        </div>
    </main>

    <script>
        (function() {
            var timeLeft = {{ $secondsRemaining }};
            var timerEl = document.getElementById('countdown');
            var reloadBtn = document.getElementById('reloadBtn');

            var interval = setInterval(function() {
                timeLeft--;
                if (timeLeft <= 0) {
                    clearInterval(interval);
                    timerEl.textContent = '0';
                    reloadBtn.disabled = false;
                    reloadBtn.style.borderColor = 'var(--gold)';
                    reloadBtn.style.color = 'var(--gold)';
                    reloadBtn.textContent = '✓ Ready: Reload Page Now';
                } else {
                    timerEl.textContent = timeLeft;
                }
            }, 1000);
        })();
    </script>
</body>
</html>
