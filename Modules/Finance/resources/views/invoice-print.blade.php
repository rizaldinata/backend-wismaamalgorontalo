<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .title { text-align: right; }
        .title h1 { margin: 0; color: #e74c3c; font-size: 28px; }
        .details { display: flex; justify-content: space-between; margin-top: 30px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #f8f9fa; }
        .total { text-align: right; font-weight: bold; font-size: 18px; margin-top: 20px; }
        .status { margin-top: 20px; padding: 10px; border-radius: 5px; display: inline-block; font-weight: bold; }
        .status.paid { background-color: #d4edda; color: #155724; }
        .status.unpaid { background-color: #f8d7da; color: #721c24; }
        @media print {
            body { padding: 0; }
            button { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <div class="logo">
            {{ $wismaName }}<br>
            <small style="font-size: 12px; color: #7f8c8d;">Sistem Manajemen Properti & Kos</small>
        </div>
        <div class="title">
            <h1>INVOICE</h1>
            <p>No: {{ $invoice->invoice_number }}</p>
            <p>Tanggal: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</p>
        </div>
    </div>

    <div class="details">
        <div>
            <strong>Ditagihkan Kepada:</strong><br>
            {{ $invoice->lease->resident->name ?? 'Penyewa' }}<br>
            No HP: {{ $invoice->lease->resident->phone_number ?? '-' }}
        </div>
        <div style="text-align: right;">
            <strong>Detail Kamar:</strong><br>
            Kamar {{ $invoice->lease->room->room_number ?? '-' }}<br>
            Tenggat Waktu: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th style="text-align: right;">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sewa Kamar periode terkait (Auto-generated)</td>
                <td style="text-align: right;">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        TOTAL: Rp {{ number_format($invoice->amount, 0, ',', '.') }}
    </div>

    @php
        $statusLabel = is_object($invoice->status) ? $invoice->status->value : $invoice->status;
        $class = strtolower($statusLabel) === 'paid' ? 'paid' : 'unpaid';
    @endphp

    <div class="status {{ $class }}">
        STATUS: {{ strtoupper($statusLabel) }}
    </div>
</body>
</html>
