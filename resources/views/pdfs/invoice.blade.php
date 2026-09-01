<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->full_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; font-size: 10px; color: #000; padding: 20px; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { font-size: 16px; font-weight: bold; }
        .header .type { font-size: 18px; font-weight: bold; border: 2px solid #000; display: inline-block; padding: 3px 15px; margin-top: 5px; }
        .header .ruc { font-size: 11px; margin-top: 3px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .info-box { width: 48%; }
        .info-box h3 { font-size: 10px; font-weight: bold; border-bottom: 1px solid #000; margin-bottom: 3px; }
        .info-box p { font-size: 9px; line-height: 1.4; }
        table.items { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.items th { background: #eee; border: 1px solid #000; padding: 4px; font-size: 9px; text-align: center; }
        table.items td { border: 1px solid #000; padding: 4px; font-size: 9px; }
        table.items td.right { text-align: right; }
        table.items td.center { text-align: center; }
        .totals { width: 250px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 3px 8px; font-size: 10px; }
        .totals td.right { text-align: right; }
        .totals .grand td { font-weight: bold; font-size: 12px; border-top: 2px solid #000; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; }
        .qr { text-align: right; margin-top: 10px; }
        .qr img { width: 100px; }
        .legend { font-size: 9px; text-align: center; margin: 8px 0; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        @if($logoBase64)
            <div style="margin-bottom:8px;"><img src="{{ $logoBase64 }}" alt="Logo" style="height:60px;"></div>
        @endif
        <h1>{{ $invoice->company->business_name }}</h1>
        <p class="ruc">RUC: {{ $invoice->company->ruc }}</p>
        <div class="type">{{ $invoice->invoice_type === 'F' ? 'FACTURA ELECTRÓNICA' : 'BOLETA ELECTRÓNICA' }}</div>
        <p style="font-size:14px;font-weight:bold;margin-top:3px;">{{ $invoice->serie }}-{{ str_pad($invoice->number, 8, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="info-row">
        <div class="info-box">
            <h3>EMISOR</h3>
            <p>{{ $invoice->company->business_name }}<br>
            {{ $invoice->company->address ?? '' }}<br>
            {{ $invoice->company->commercial_name ? 'Nombre Comercial: ' . $invoice->company->commercial_name : '' }}</p>
        </div>
        <div class="info-box">
            <h3>ADQUIRIENTE</h3>
            <p>{{ $invoice->client?->name ?? 'CONSUMIDOR FINAL' }}<br>
            {{ $invoice->client ? $invoice->client->doc_type . ': ' . $invoice->client->doc_number : '' }}<br>
            {{ $invoice->client?->address ?? '' }}</p>
        </div>
    </div>

    <div class="info-row">
        <div class="info-box">
            <p><strong>Fecha Emisión:</strong> {{ $invoice->issue_date->format('d/m/Y') }}</p>
        </div>
        <div class="info-box">
            <p><strong>Moneda:</strong> {{ $invoice->currency === 'PEN' ? 'SOLES' : 'DÓLARES' }}</p>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:8%">Cant.</th>
                <th style="width:10%">Unid.</th>
                <th style="width:42%">Descripción</th>
                <th style="width:15%">P. Unit.</th>
                <th style="width:15%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td class="center">{{ $item->quantity }}</td>
                <td class="center">ZZ</td>
                <td>{{ $item->description }}</td>
                <td class="right">{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Op. Gravadas</td><td class="right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>IGV (18%)</td><td class="right">{{ number_format($invoice->igv, 2) }}</td></tr>
        <tr><td>Total Impuestos</td><td class="right">{{ number_format($invoice->igv, 2) }}</td></tr>
        <tr class="grand"><td>TOTAL</td><td class="right">{{ number_format($invoice->total, 2) }}</td></tr>
    </table>

    <div class="legend">
        @php
            $totalWords = \App\Services\SunatService::numberToWords($invoice->total);
        @endphp
        Son: {{ $totalWords }} {{ $invoice->currency === 'PEN' ? 'SOLES' : 'DOLARES' }}
    </div>

    <div class="qr">
        <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR">
        <p style="font-size:7px;margin-top:2px;">Hash: {{ $invoice->hash_cpe ?? '---' }}</p>
    </div>

    <div class="footer">
        <p>Representación Impresa del Comprobante Electrónico</p>
        <p>Autorizado mediante Resolución N° 000000-2023/SUNAT</p>
    </div>
</body>
</html>
