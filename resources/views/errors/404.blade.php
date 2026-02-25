<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Page Not Found | Matchday</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 480px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(200, 255, 0, 0.08);
            border: 1px solid rgba(200, 255, 0, 0.2);
            color: #c8ff00;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.35rem 0.85rem;
            border-radius: 999px;
            margin-bottom: 2rem;
        }
        .code {
            font-size: 8rem;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #c8ff00 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            letter-spacing: -0.04em;
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 0.75rem;
        }
        p {
            font-size: 0.95rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }
        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #c8ff00;
            color: #0f172a;
            font-weight: 700;
            font-size: 0.875rem;
            padding: 0.65rem 1.4rem;
            border-radius: 0.625rem;
            text-decoration: none;
            transition: opacity 0.15s;
        }
        .btn-primary:hover { opacity: 0.85; }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            border: 1px solid #334155;
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.65rem 1.4rem;
            border-radius: 0.625rem;
            text-decoration: none;
            transition: border-color 0.15s, color 0.15s;
        }
        .btn-secondary:hover { border-color: #c8ff00; color: #c8ff00; }
        .divider {
            width: 48px;
            height: 2px;
            background: rgba(200, 255, 0, 0.3);
            border-radius: 999px;
            margin: 2rem auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            Error 404
        </div>

        <div class="code">404</div>

        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.<br>Head back to the dashboard to continue.</p>

        <div class="divider"></div>

        <div class="actions">
            @auth
                @if(auth()->user()->role === 'platform_admin' || auth()->user()->is_platform_admin)
                    <a href="{{ route('platform.dashboard') }}" class="btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                        Go to Dashboard
                    </a>
                @else
                    <a href="/" class="btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        </svg>
                        Go Home
                    </a>
                @endif
            @else
                <a href="/platform" class="btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    Go Home
                </a>
            @endauth
            <a href="javascript:history.back()" class="btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
                </svg>
                Go Back
            </a>
        </div>
    </div>
</body>
</html>
