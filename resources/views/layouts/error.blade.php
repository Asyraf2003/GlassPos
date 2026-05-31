<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Terjadi Gangguan')</title>

    @if (file_exists(public_path('assets/compiled/svg/favicon.svg')))
        <link rel="shortcut icon" href="{{ asset('assets/compiled/svg/favicon.svg') }}" type="image/x-icon">
    @endif

    @if (file_exists(public_path('assets/compiled/css/app.css')))
        <link rel="stylesheet" href="{{ asset('assets/compiled/css/app.css') }}?v={{ config('app.asset_version') }}">
    @endif

    @if (file_exists(public_path('assets/compiled/css/error.css')))
        <link rel="stylesheet" href="{{ asset('assets/compiled/css/error.css') }}?v={{ config('app.asset_version') }}">
    @endif

    <style>
        :root {
            color-scheme: light;
            --error-bg: #f2f7ff;
            --error-card-bg: #ffffff;
            --error-text: #25396f;
            --error-muted: #607080;
            --error-border: #dce7f7;
            --error-primary: #435ebe;
            --error-primary-hover: #334b9b;
            --error-outline-bg: #ffffff;
            --error-shadow: 0 18px 50px rgba(67, 94, 190, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--error-bg);
            color: var(--error-text);
            font-family: Inter, Arial, Helvetica, sans-serif;
        }

        #error {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .error-page {
            width: 100%;
            max-width: 880px;
        }

        .error-card {
            background: var(--error-card-bg);
            border: 1px solid var(--error-border);
            border-radius: 24px;
            box-shadow: var(--error-shadow);
            padding: 40px 28px;
        }

        .text-center {
            text-align: center;
        }

        .img-error {
            display: block;
            width: 100%;
            max-width: 360px;
            margin: 0 auto 24px;
        }

        .error-title {
            margin: 0 0 12px;
            font-size: clamp(2rem, 3.2vw, 3.6rem);
            line-height: 1.1;
            font-weight: 700;
            color: var(--error-text);
        }

        .error-message {
            margin: 0 auto;
            max-width: 620px;
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--error-muted);
        }

        .error-code {
            display: inline-block;
            margin-bottom: 18px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid var(--error-border);
            background: #f8fbff;
            color: var(--error-muted);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .error-actions {
            margin-top: 28px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .error-note {
            margin-top: 18px;
            color: var(--error-muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .btn {
            appearance: none;
            border: 1px solid transparent;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 50px;
            padding: 0 20px;
            border-radius: 0.7rem;
            font-weight: 700;
            line-height: 1;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
        }

        .btn-lg {
            min-height: 54px;
            padding: 0 24px;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--error-primary);
            border-color: var(--error-primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--error-primary-hover);
            border-color: var(--error-primary-hover);
            color: #ffffff;
        }

        .btn-outline-primary {
            background: var(--error-outline-bg);
            border-color: var(--error-primary);
            color: var(--error-primary);
        }

        .btn-outline-primary:hover {
            background: #eef3ff;
            color: var(--error-primary-hover);
            border-color: var(--error-primary-hover);
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 28px 20px;
                border-radius: 18px;
            }

            .img-error {
                max-width: 260px;
                margin-bottom: 20px;
            }

            .error-actions {
                flex-direction: column;
            }

            .error-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    @if (file_exists(public_path('assets/static/js/initTheme.js')))
        <script src="{{ asset('assets/static/js/initTheme.js') }}?v={{ config('app.asset_version') }}"></script>
    @endif

    <div id="error">
        <div class="error-page">
            <div class="error-card">
                <div class="text-center">
                    <div class="error-code">Error {{ $statusCode ?? '500' }}</div>

                    @hasSection('image_asset')
                        <img class="img-error" src="@yield('image_asset')" alt="@yield('image_alt', 'Error')">
                    @endif

                    <h1 class="error-title">@yield('heading')</h1>
                    <p class="error-message">@yield('message')</p>

                    <div class="error-actions">
                        @yield('actions')
                    </div>

                    @hasSection('note')
                        <div class="error-note">
                            @yield('note')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
