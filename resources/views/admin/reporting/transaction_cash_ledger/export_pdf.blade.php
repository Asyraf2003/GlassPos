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
            line-height: 1.4;
            margin: 22px;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .meta {
            color: #374151;
            margin-bottom: 16px;
        }

        h2 {
            font-size: 13px;
            margin: 18px 0 8px;
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
            font-size: 14px;
            font-weight: bold;
        }

        .note {
            background: #f9fafb;
            border-left: 4px solid #2563eb;
            margin-bottom: 8px;
            padding: 9px 11px;
        }

        .excel-note {
            color: #374151;
            font-size: 10px;
            margin-top: 16px;
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

    <h2>Catatan Laporan</h2>
    <div class="note">
        Laporan ini merangkum uang transaksi yang masuk dan uang yang keluar
        karena refund pada periode yang dipilih. Detail per kejadian kas tidak
        ditampilkan di PDF agar laporan mudah dibaca.
    </div>

    <div class="excel-note">Detail lengkap tersedia di Excel.</div>
</body>
</html>
