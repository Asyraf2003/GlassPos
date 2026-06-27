<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1.35;
            margin: 20px;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        h2 {
            font-size: 13px;
            margin: 16px 0 7px;
        }

        .meta {
            color: #374151;
            margin-bottom: 14px;
        }

        .metric {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-bottom: 7px;
            padding: 8px 10px;
        }

        .metric-label {
            color: #4b5563;
            font-size: 9px;
            margin-bottom: 2px;
        }

        .metric-value {
            font-size: 13px;
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
