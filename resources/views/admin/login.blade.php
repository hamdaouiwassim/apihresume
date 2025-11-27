<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #eef2ff, #e0f2fe);
            padding: 1.5rem;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 1.5rem;
            padding: 2.5rem;
            box-shadow: 0 30px 70px rgb(15 23 42 / 12%);
            border: 1px solid #e2e8f0;
        }
        h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }
        p.subtitle {
            color: #475569;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #0f172a;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.85rem 0.95rem;
            border-radius: 0.85rem;
            border: 1px solid #cbd5f5;
            background: #f8fafc;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #2563eb;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }
        .field {
            margin-bottom: 1.5rem;
        }
        .error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
            color: #475569;
            font-size: 0.95rem;
        }
        button[type="submit"] {
            width: 100%;
            border: none;
            border-radius: 0.95rem;
            padding: 0.95rem;
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(120deg, #2563eb, #7c3aed);
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        button[type="submit"]:hover {
            transform: translateY(-1px);
            box-shadow: 0 15px 25px rgba(37, 99, 235, 0.25);
        }
        .status {
            margin-bottom: 1rem;
            padding: 0.85rem 1rem;
            border-radius: 0.85rem;
            font-size: 0.9rem;
        }
        .status--success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .status--error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Admin Access</h1>
        <p class="subtitle">Sign in to view live Pulse monitoring.</p>

        @if (session('status'))
            <div class="status status--success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="status status--error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    autofocus
                >
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    required
                    autocomplete="current-password"
                >
            </div>

            <label class="remember">
                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                <span>Remember this device</span>
            </label>

            <button type="submit">Sign in &raquo;</button>
        </form>
    </div>
</body>
</html>

