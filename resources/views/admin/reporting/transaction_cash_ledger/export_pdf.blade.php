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

        .summary {
            border-collapse: collapse;
            margin-bottom: 16px;
            width: 100%;
        }

        .summary td {
            border: 1px solid #d1d5db;
            padding: 7px 9px;
        }

        .summary td:first-child {
            background: #f3f4f6;
            font-weight: bold;
            width: 42%;
        }

        .detail {
            border-collapse: collapse;
            width: 100%;
        }

        .detail th,
        .detail td {
            border: 1px solid #d1d5db;
            padding: 5px 6px;
            vertical-align: top;
        }

        .detail th {
            background: #e5e7eb;
            font-weight: bold;
            text-align: left;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        Periode: {{ $periodLabel }}<br>
        Dicetak: {{ $generatedAt }}
    </div>

    <table class="summary">
        <tbody>
            @foreach ($summaryItems as $item)
                <tr>
                    <td>{{ $item['label'] }}</td>
                    <td>{{ $item['value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nota</th>
                <th>Jenis Kejadian</th>
                <th>Arah</th>
                <th>Metode Pembayaran</th>
                <th>Pembayaran</th>
                <th>Refund</th>
                <th>Tabel Sumber</th>
                <th>ID Sumber</th>
                <th>ID Disposisi Sumber</th>
                <th>Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['note_label'] }}</td>
                    <td>{{ $row['event_type'] }}</td>
                    <td>{{ $row['direction'] }}</td>
                    <td>{{ $row['payment_method'] ?? '-' }}</td>
                    <td>{{ $row['payment_marker'] }}</td>
                    <td>{{ $row['refund_marker'] }}</td>
                    <td>{{ $row['source_table'] }}</td>
                    <td>{{ $row['source_id'] }}</td>
                    <td>{{ $row['source_disposition_id'] }}</td>
                    <td class="number">{{ $row['amount'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="muted">Belum ada kejadian kas pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
