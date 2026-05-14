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

        .summary {
            border-collapse: collapse;
            margin-bottom: 18px;
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
                <th>ID Nota</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Dibayar</th>
                <th>Refund</th>
                <th>Refund Due</th>
                <th>Surplus Refund Paid</th>
                <th>Sisa Refund Due</th>
                <th>Kas Bersih</th>
                <th>Sisa Tagihan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['note_id'] }}</td>
                    <td>{{ $row['customer_name'] }}</td>
                    <td class="number">{{ $row['total'] }}</td>
                    <td class="number">{{ $row['paid'] }}</td>
                    <td class="number">{{ $row['refund'] }}</td>
                    <td class="number">{{ $row['refund_due'] }}</td>
                    <td class="number">{{ $row['surplus_refund_paid'] }}</td>
                    <td class="number">{{ $row['remaining_refund_due'] }}</td>
                    <td class="number">{{ $row['net_paid'] }}</td>
                    <td class="number">{{ $row['outstanding'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="muted">Tidak ada data transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
