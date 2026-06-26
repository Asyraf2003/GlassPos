<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #111827;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 18px;
        }

        h2 {
            margin: 18px 0 8px;
            font-size: 13px;
        }

        .meta {
            margin-bottom: 12px;
            color: #374151;
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
            font-weight: bold;
            font-size: 13px;
        }

        .note {
            background: #f9fafb;
            border-left: 4px solid #059669;
            margin-bottom: 8px;
            padding: 8px 10px;
        }

        .excel-note {
            color: #374151;
            font-size: 9px;
            margin-top: 14px;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Rentang movement: {{ $periodLabel }}<br>
        Tanggal referensi: {{ $referenceDateLabel }}<br>
        Dicetak: {{ $generatedAt }}
    </div>

    <h2>Ringkasan Utama</h2>
    @foreach ($summaryItems as $item)
        <div class="metric">
            <div class="metric-label">{{ $item['label'] }}</div>
            <div class="metric-value">{{ $item['value'] }}</div>
        </div>
    @endforeach

    <h2>Catatan Laporan</h2>
    <div class="note">
        Laporan ini merangkum posisi stok terbaru, nilai persediaan, dan mutasi
        utama pada rentang movement yang dipilih.
    </div>

    <div class="excel-note">Detail lengkap tersedia di Excel.</div>
</body>
</html>
