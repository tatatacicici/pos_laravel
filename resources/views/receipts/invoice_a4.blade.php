<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>FAKTUR PENJUALAN - {{ $order->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
            padding: 10px;
        }
        .header-table {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            vertical-align: top;
            width: 50%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .summary-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .summary-table td {
            padding: 6px 10px;
        }
        .summary-total {
            font-size: 14px;
            font-weight: bold;
            background-color: #f8fafc;
            border-top: 2px solid #2563eb;
        }
        .clear { clear: both; }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            font-weight: bold;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #15803d;
        }
        .status-pending {
            background-color: #fef9c3;
            color: #854d0e;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td>
                <div style="font-size: 20px; font-weight: bold; color: #0f172a;">{{ $outlet->name ?? 'POS SYSTEM' }}</div>
                <div>{{ $outlet->address ?? 'Alamat Toko' }}</div>
                @if(!empty($outlet->phone))
                    <div>Telp: {{ $outlet->phone }}</div>
                @endif
            </td>
            <td class="text-right">
                <div class="invoice-title">FAKTUR PENJUALAN</div>
                <div class="bold">No: {{ $order->invoice_number }}</div>
                <div>Tanggal: {{ $order->created_at->format('d F Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <div class="bold" style="color: #64748b; margin-bottom: 4px;">PELANGGAN:</div>
                <div class="bold">{{ $order->customer_name }}</div>
                @if($order->customer_phone) <div>Telp: {{ $order->customer_phone }}</div> @endif
                @if($order->customer_email) <div>Email: {{ $order->customer_email }}</div> @endif
                @if($order->table_number) <div>Meja: {{ $order->table_number }}</div> @endif
            </td>
            <td class="text-right">
                <div class="bold" style="color: #64748b; margin-bottom: 4px;">STATUS TRANSAKSI:</div>
                <div style="margin-bottom: 4px;">
                    Kasir: <strong>{{ $order->cashier->name ?? 'Kasir' }}</strong>
                </div>
                <div style="margin-bottom: 6px;">
                    Metode: <strong>{{ strtoupper(str_replace('_', ' ', $order->payment_method?->value ?? 'CASH')) }}</strong>
                </div>
                <div>
                    <span class="status-badge {{ $order->payment_status->value === 'settlement' || $order->payment_status->value === 'capture' ? 'status-paid' : 'status-pending' }}">
                        {{ strtoupper($order->payment_status->value) }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Item / Produk</th>
                <th class="text-right" style="width: 15%;">Harga Satuan</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-right" style="width: 10%;">Diskon</th>
                <th class="text-right" style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <span class="bold">{{ $item->product_name }}</span>
                        @if($item->variant_name)
                            <span style="color: #64748b;">({{ $item->variant_name }})</span>
                        @endif
                    </td>
                    <td class="text-right">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">
                        {{ (float)$item->discount_amount > 0 ? '-Rp ' . number_format((float)$item->discount_amount, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-right bold">Rp {{ number_format((float)$item->total_amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">Rp {{ number_format((float)$order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if((float)$order->discount_amount > 0)
        <tr>
            <td>Total Diskon:</td>
            <td class="text-right">-Rp {{ number_format((float)$order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float)$order->tax_amount > 0)
        <tr>
            <td>PPN / Pajak:</td>
            <td class="text-right">Rp {{ number_format((float)$order->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float)$order->service_charge > 0)
        <tr>
            <td>Service Charge:</td>
            <td class="text-right">Rp {{ number_format((float)$order->service_charge, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="summary-total">
            <td>TOTAL AKHIR:</td>
            <td class="text-right">Rp {{ number_format((float)$order->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="clear"></div>

    <div class="footer">
        <div>{{ $outlet->receipt_footer ?? 'Terima Kasih Atas Kerjasama Anda.' }}</div>
        <div>Dokumen ini dicetak otomatis dan merupakan bukti transaksi yang sah.</div>
    </div>
</body>
</html>
