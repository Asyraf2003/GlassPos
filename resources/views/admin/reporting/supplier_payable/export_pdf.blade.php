<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            color: #111827;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 8px;
            line-height: 1.3;
            margin: 16px;
        }

        h1 {
            font-size: 19px;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        h2 {
            font-size: 12px;
            margin: 13px 0 6px;
        }

        .meta {
            color: #374151;
            margin-bottom: 10px;
        }

        .metric {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-bottom: 6px;
            padding: 7px 9px;
        }

        .metric-label {
            color: #4b5563;
            font-size: 8px;
            margin-bottom: 2px;
        }

        .metric-value {
            font-size: 12px;
            font-weight: bold;
        }

    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Periode: {{ $periodLabel }}<br>
        Tanggal Referensi: {{ $referenceDateLabel }}<br>
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
