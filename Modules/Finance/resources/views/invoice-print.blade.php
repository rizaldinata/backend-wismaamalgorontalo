<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isPaid ? 'Kwitansi' : 'Invoice' }} - {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1a1a1a; background: #f5f5f5; }
        .page { max-width: 680px; margin: 30px auto; background: #fff; padding: 48px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 24px; border-bottom: 2px solid #e5e7eb; }
        .wisma-name { font-size: 20px; font-weight: 700; color: #1e3a5f; }
        .wisma-sub { font-size: 11px; color: #6b7280; margin-top: 3px; }
        .doc-type { text-align: right; }
        .doc-type h1 { font-size: 26px; font-weight: 800; letter-spacing: 2px; color: {{ $isPaid ? '#059669' : '#1e3a5f' }}; }
        .doc-type p { font-size: 12px; color: #6b7280; margin-top: 4px; }

        /* Info row */
        .info-row { display: flex; justify-content: space-between; margin-top: 28px; gap: 24px; }
        .info-block h4 { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #9ca3af; margin-bottom: 6px; }
        .info-block p { font-size: 13px; color: #374151; line-height: 1.6; }
        .info-block strong { color: #111827; }

        /* Table */
        .table { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .table thead tr { background: #f9fafb; border-bottom: 2px solid #e5e7eb; }
        .table th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: #6b7280; }
        .table th.right, .table td.right { text-align: right; }
        .table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #374151; }
        .table tbody tr:last-child td { border-bottom: none; }

        /* Total */
        .total-row { display: flex; justify-content: flex-end; margin-top: 16px; }
        .total-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 20px; min-width: 240px; }
        .total-box .total-label { font-size: 11px; color: #9ca3af; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; }
        .total-box .total-amount { font-size: 22px; font-weight: 800; color: #111827; margin-top: 4px; }

        /* Payment detail (kwitansi) */
        .payment-section { margin-top: 28px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; }
        .payment-section h3 { font-size: 13px; font-weight: 700; color: #15803d; margin-bottom: 14px; display: flex; align-items: center; gap: 6px; }
        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .payment-item .label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; color: #6b7280; margin-bottom: 3px; }
        .payment-item .value { font-size: 13px; color: #111827; font-weight: 600; }

        /* Status badge */
        .status-row { margin-top: 28px; display: flex; justify-content: space-between; align-items: center; }
        .badge { display: inline-block; padding: 6px 18px; border-radius: 99px; font-size: 12px; font-weight: 700; letter-spacing: 0.5px; }
        .badge-paid { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
        .badge-unpaid { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }

        /* Footer */
        .footer { margin-top: 36px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-note { font-size: 11px; color: #9ca3af; line-height: 1.6; }
        .footer-sign { text-align: center; }
        .footer-sign .sign-line { width: 140px; border-bottom: 1px solid #d1d5db; margin: 0 auto 6px; padding-bottom: 32px; }
        .footer-sign .sign-label { font-size: 11px; color: #6b7280; }

        /* Print button */
        .print-btn { display: block; text-align: center; margin-bottom: 24px; }
        .print-btn button { background: #1e3a5f; color: #fff; border: none; padding: 10px 28px; border-radius: 6px; font-size: 14px; cursor: pointer; }

        @media print {
            body { background: #fff; }
            .page { margin: 0; padding: 32px; box-shadow: none; border-radius: 0; }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-btn no-print">
        <button onclick="window.print()">&#128438; Cetak / Simpan PDF</button>
    </div>

    <div class="page">
        {{-- ── Header ─────────────────────────────────────────────── --}}
        <div class="header">
            <div>
                <div class="wisma-name">{{ $wismaName }}</div>
                <div class="wisma-sub">Sistem Manajemen Properti & Kos</div>
            </div>
            <div class="doc-type">
                <h1>{{ $isPaid ? 'KWITANSI' : 'INVOICE' }}</h1>
                <p>No: {{ $invoice->invoice_number }}</p>
                <p>Tanggal: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</p>
            </div>
        </div>

        {{-- ── Info Rows ───────────────────────────────────────────── --}}
        <div class="info-row">
            <div class="info-block">
                <h4>Ditagihkan Kepada</h4>
                <p>
                    <strong>{{ $invoice->schedule->tenant_name ?? 'Penyewa' }}</strong><br>
                    No HP: {{ $invoice->schedule->tenant_phone ?? '-' }}
                </p>
            </div>
            <div class="info-block">
                <h4>Detail Kamar</h4>
                <p>
                    <strong>Kamar {{ $invoice->schedule->room->number ?? '-' }}</strong><br>
                    {{ $invoice->schedule->room->title ?? '' }}
                </p>
            </div>
            <div class="info-block" style="text-align:right">
                <h4>Jatuh Tempo</h4>
                <p>
                    <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong><br>
                    @php
                        $isOverdue = !$isPaid && \Carbon\Carbon::parse($invoice->due_date)->isPast();
                    @endphp
                    @if($isOverdue)
                        <span style="color:#dc2626;font-size:11px;font-weight:600;">Sudah Jatuh Tempo</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- ── Table ──────────────────────────────────────────────── --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Sewa Kamar {{ $invoice->schedule->room->number ?? '' }}<br>
                        <span style="font-size:11px;color:#6b7280;">
                            Periode: {{ \Carbon\Carbon::parse($invoice->schedule->start_date)->format('d M Y') }}
                            – {{ \Carbon\Carbon::parse($invoice->schedule->end_date)->format('d M Y') }}
                        </span>
                    </td>
                    <td class="right" style="font-weight:600;">
                        Rp {{ number_format($invoice->amount, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="total-row">
            <div class="total-box">
                <div class="total-label">Total Tagihan</div>
                <div class="total-amount">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- ── Payment Detail (jika sudah lunas) ──────────────────── --}}
        @if($isPaid && $payment)
        <div class="payment-section">
            <h3>&#10003; Detail Pembayaran</h3>
            <div class="payment-grid">
                <div class="payment-item">
                    <div class="label">Tanggal Bayar</div>
                    <div class="value">{{ \Carbon\Carbon::parse($payment->updated_at)->format('d M Y, H:i') }}</div>
                </div>
                <div class="payment-item">
                    <div class="label">Metode</div>
                    <div class="value">
                        @php
                            $method = is_object($payment->payment_method) ? $payment->payment_method->value : $payment->payment_method;
                        @endphp
                        {{ $method === 'midtrans' ? 'Midtrans / QRIS' : 'Transfer Manual' }}
                    </div>
                </div>
                @if($payment->transaction_id)
                <div class="payment-item">
                    <div class="label">ID Transaksi</div>
                    <div class="value">{{ $payment->transaction_id }}</div>
                </div>
                @endif
                @if($payment->admin_notes)
                <div class="payment-item">
                    <div class="label">Catatan</div>
                    <div class="value">{{ $payment->admin_notes }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- ── Status & Footer ────────────────────────────────────── --}}
        <div class="status-row">
            <span class="badge {{ $isPaid ? 'badge-paid' : 'badge-unpaid' }}">
                {{ $isPaid ? '✓ LUNAS' : 'BELUM DIBAYAR' }}
            </span>
            <span style="font-size:11px;color:#9ca3af;">Dicetak: {{ now()->format('d M Y, H:i') }}</span>
        </div>

        <div class="footer">
            <div class="footer-note">
                Dokumen ini digenerate secara otomatis oleh sistem.<br>
                Jika ada pertanyaan, hubungi admin wisma.
            </div>
            @if($isPaid)
            <div class="footer-sign">
                <div class="sign-line"></div>
                <div class="sign-label">Admin / Pengelola</div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
