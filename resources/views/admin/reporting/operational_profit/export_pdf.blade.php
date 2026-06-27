<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 24px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .meta {
            color: #374151;
            margin-bottom: 18px;
        }

        h2 {
            font-size: 14px;
            margin: 20px 0 8px;
        }

        .metric {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-bottom: 8px;
            padding: 9px 11px;
        }

        .metric-label {
            color: #4b5563;
            font-size: 10px;
            margin-bottom: 2px;
        }

        .metric-value {
            font-size: 15px;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Periode: {{ $periodLabel }}<br>
        Dicetak: {{ $generatedAt }}
    </div>

    <h2>Ringkasan Utama</h2>
    @foreach ($summaryItems as $item)
        <div class="metric">
            <div class="metric-label">{{ $item['label'] }}</div>
            <div class="metric-value">{{ $item['value'] }}</div>
        </div>
    @endforeach

</body>
</html>
