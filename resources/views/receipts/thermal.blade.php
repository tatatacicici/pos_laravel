<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk #{{ $order->invoice_number }}</title>
    <style>
        @page {
            margin: 4mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: {{ $paperWidthMm === 58 ? '9px' : '11px' }};
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 6px 0;
        }
        .outlet-name {
            font-size: {{ $paperWidthMm === 58 ? '12px' : '14px' }};
            font-weight: bold;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td, th {
            padding: 2px 0;
            vertical-align: top;
        }
        .item-name {
            word-break: break-all;
        }
        .footer-note {
            font-size: {{ $paperWidthMm === 58 ? '8px' : '10px' }};
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="outlet-name">{{ $outlet->name ?? 'POINT OF SALE' }}</div>
        <div>{{ $outlet->address ?? '' }}</div>
        @if(!empty($outlet->phone))
            <div>Telp: {{ $outlet->phone }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">No: {{ $order->invoice_number }}</td>
            <td class="text-right">{{ $order->created_at->format('d/m/y H:i') }}</td>
        </tr>
        <tr>
            <td class="text-left">Kasir: {{ $order->cashier->name ?? 'Kasir' }}</td>
            <td class="text-right">
                @if($order->table_number)
                    Meja: {{ $order->table_number }}
                @else
                    {{ ucfirst(str_replace('_', ' ', $order->order_type)) }}
                @endif
            </td>
        </tr>
        @if($order->customer_name && $order->customer_name !== 'Walk-in Customer')
        <tr>
            <td colspan="2" class="text-left">Pelanggan: {{ $order->customer_name }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <table>
        @foreach($order->items as $item)
            <tr>
                <td colspan="3" class="bold item-name">
                    {{ $item->product_name }}
                    @if($item->variant_name)
                        <span style="font-weight: normal; font-size: 0.9em;">({{ $item->variant_name }})</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="width: 45%; padding-left: 8px;">
                    {{ $item->quantity }} x {{ number_format((float)$item->unit_price, 0, ',', '.') }}
                </td>
                <td style="width: 15%;" class="text-right">
                    @if((float)$item->discount_amount > 0)
                        -{{ number_format((float)$item->discount_amount, 0, ',', '.') }}
                    @endif
                </td>
                <td style="width: 40%;" class="text-right bold">
                    {{ number_format((float)$item->total_amount, 0, ',', '.') }}
                </td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">Subtotal</td>
            <td class="text-right">Rp {{ number_format((float)$order->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if((float)$order->discount_amount > 0)
        <tr>
            <td class="text-left">Diskon</td>
            <td class="text-right">-Rp {{ number_format((float)$order->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float)$order->tax_amount > 0)
        <tr>
            <td class="text-left">Pajak (PPN)</td>
            <td class="text-right">Rp {{ number_format((float)$order->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if((float)$order->service_charge > 0)
        <tr>
            <td class="text-left">Service Charge</td>
            <td class="text-right">Rp {{ number_format((float)$order->service_charge, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="bold" style="font-size: 1.1em;">
            <td class="text-left">TOTAL</td>
            <td class="text-right">Rp {{ number_format((float)$order->total_amount, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="text-left">Metode Bayar</td>
            <td class="text-right bold">
                {{ strtoupper(str_replace('_', ' ', $order->payment_method?->value ?? 'CASH')) }}
            </td>
        </tr>
        @php
            $latestPayment = $order->latestPayment;
        @endphp
        @if($latestPayment && (float)$latestPayment->cash_received > 0)
        <tr>
            <td class="text-left">Tunai Diterima</td>
            <td class="text-right">Rp {{ number_format((float)$latestPayment->cash_received, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="text-left">Kembalian</td>
            <td class="text-right">Rp {{ number_format((float)$latestPayment->change_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td class="text-left">Status Bayar</td>
            <td class="text-right bold">
                {{ strtoupper($order->payment_status->value) }}
            </td>
        </tr>
    </table>

    <div class="double-divider"></div>

    <div class="text-center footer-note">
        <div>{{ $outlet->receipt_footer ?? 'Terima Kasih Atas Kunjungan Anda' }}</div>
        <div>Simpan struk ini sebagai bukti pembayaran yang sah</div>
        <div style="font-size: 0.85em; margin-top: 4px; color: #555;">Power by Laravel POS</div>
    </div>
</body>
</html>
