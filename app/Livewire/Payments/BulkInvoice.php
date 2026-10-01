<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentRecord;
use App\Services\PdfService;
use App\Services\SunatService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkInvoice extends Component
{
    use WithFileUploads;

    public $payments;
    public $selected = [];

    public $tab = 'pendientes';

    #[Validate('file|mimes:xlsx,xls|max:10240')]
    public $file;

    public $rows = [];
    public $defaultInvoiceType = 'B';
    public $excelError = null;
    public $excelInfo = null;

    public function mount()
    {
        $this->payments = $this->pendingPayments();
    }

    protected function pendingPayments()
    {
        return PaymentRecord::where('status', 'pending')->latest()->get();
    }

    public function setTab(string $tab)
    {
        if (in_array($tab, ['pendientes', 'excel'], true)) {
            $this->tab = $tab;
        }
    }

    public function toggleAll()
    {
        if (count($this->selected) === $this->payments->count()) {
            $this->selected = [];
        } else {
            $this->selected = $this->payments->pluck('id')->toArray();
        }
    }

    public function toggleAllRows()
    {
        $allOn = count(array_filter($this->rows, fn ($row) => !empty($row['selected'])))
            === count(array_filter($this->rows, fn ($row) => empty($row['duplicate'])));

        foreach ($this->rows as $i => $row) {
            if (!empty($row['duplicate'])) {
                $this->rows[$i]['selected'] = false;
            } else {
                $this->rows[$i]['selected'] = !$allOn;
            }
        }
    }

    public function updatedRows($value, $key)
    {
        if (!str_ends_with((string) $key, '.client_query')) {
            return;
        }

        $index = (int) explode('.', $key, 2)[0];
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->rows[$index]['client_id'] = null;
        $this->rows[$index]['client_name'] = '';
        $this->rows[$index]['client_doc'] = '';
        $this->rows[$index]['client_results'] = $this->searchClients((string) $value);
    }

    public function updatedDefaultInvoiceType($value)
    {
        if (!in_array($value, ['F', 'B'], true)) {
            return;
        }

        foreach ($this->rows as $i => $row) {
            $this->rows[$i]['invoice_type'] = $value;
        }
    }

    public function selectClient(int $index, int $clientId)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $client = Client::find($clientId);
        if (!$client) {
            return;
        }

        $this->rows[$index]['client_id'] = $client->id;
        $this->rows[$index]['client_name'] = $client->name;
        $this->rows[$index]['client_doc'] = $client->doc_number;
        $this->rows[$index]['client_query'] = $client->name . ' — ' . $client->doc_number;
        $this->rows[$index]['client_results'] = [];
        $this->rows[$index]['error'] = null;
    }

    public function clearClient(int $index)
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $this->rows[$index]['client_id'] = null;
        $this->rows[$index]['client_name'] = '';
        $this->rows[$index]['client_doc'] = '';
        $this->rows[$index]['client_query'] = '';
        $this->rows[$index]['client_results'] = [];
    }

    public function resetExcel()
    {
        $this->reset('rows', 'excelError', 'excelInfo', 'defaultInvoiceType');
    }

    protected function searchClients(string $query)
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [];
        }

        return Client::query()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('doc_number', 'like', '%' . $query . '%');
            })
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'doc_number', 'doc_type'])
            ->toArray();
    }

    public function processUpload()
    {
        $this->reset('excelError', 'excelInfo');

        $this->validate();

        if (!$this->file) {
            $this->excelError = 'Seleccione un archivo Excel primero.';
            return;
        }

        try {
            $this->rows = $this->parseIzipayFile(
                $this->file->getRealPath(),
                $this->file->getClientOriginalName()
            );

            if (empty($this->rows)) {
                $this->excelError = 'No se encontraron transacciones válidas (IMPORTE > 0) en el archivo.';
                return;
            }

            $duplicates = count(array_filter($this->rows, fn ($row) => !empty($row['duplicate'])));
            $this->tab = 'excel';
            $this->excelInfo = 'Se leyeron ' . count($this->rows) . ' transacción(es)'
                . ($duplicates > 0 ? ' (' . $duplicates . ' ya importada(s))' : '')
                . '. Deja el cliente vacío o búscalo por fila; vacío = Consumidor Final.';
        } catch (\Throwable $e) {
            $this->rows = [];
            $this->excelError = 'No se pudo procesar el archivo: ' . $e->getMessage();
        } finally {
            $this->file = null;
        }
    }

    public function importSelected()
    {
        $this->reset('excelError', 'excelInfo');

        $imported = 0;
        $errors = [];

        foreach ($this->rows as $index => $row) {
            if (empty($row['selected'])) {
                continue;
            }

            if (!empty($row['duplicate'])) {
                $errors[] = 'Fila ' . ($index + 1) . ' (voucher ' . $row['voucher'] . '): ya fue importada anteriormente.';
                continue;
            }

            try {
                $client = $this->resolveClient($row);
                $invoiceType = in_array($row['invoice_type'] ?? null, ['F', 'B'], true) ? $row['invoice_type'] : 'B';

                if ($invoiceType === 'F' && strlen($client['doc_number']) !== 11) {
                    $errors[] = 'Fila ' . ($index + 1) . ': la Factura requiere un RUC de 11 dígitos.';
                    continue;
                }
                if ($invoiceType === 'B' && $client['doc_type'] === 'RUC') {
                    $errors[] = 'Fila ' . ($index + 1) . ': la Boleta no puede emitirse a un RUC.';
                    continue;
                }

                PaymentRecord::create([
                    'client_id' => $client['id'],
                    'client_name' => $client['name'],
                    'client_doc' => $client['doc_number'],
                    'amount' => (float) $row['importe'],
                    'payment_method' => 'Tarjeta',
                    'reference' => $row['voucher'] ?: 'IZIPAY-' . strtoupper($row['codigo'] . '-' . date('ymdHis') . '-' . $index),
                    'payment_date' => $this->rowPaymentDate($row),
                    'notes' => $this->buildNotes($row),
                    'invoice_type' => $invoiceType,
                    'status' => 'pending',
                    'user_id' => auth()->id(),
                    'source' => 'izipay',
                    'client_phone' => null,
                    'original_data' => $this->buildOriginalData($row),
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = 'Fila ' . ($index + 1) . ': ' . $e->getMessage();
            }
        }

        $this->rows = [];
        $this->defaultInvoiceType = 'B';
        $this->tab = 'pendientes';
        $this->payments = $this->pendingPayments();

        session()->flash('importResult', ['imported' => $imported, 'errors' => $errors]);
    }

    protected function resolveClient(array $row): array
    {
        $query = trim((string) ($row['client_query'] ?? ''));

        if (!empty($row['client_id'])) {
            $client = Client::find($row['client_id']);
            if ($client) {
                return $this->clientPayload($client);
            }
        }

        $digits = preg_replace('/\D/', '', $query);
        if (strlen($digits) === 8 || strlen($digits) === 11) {
            $docType = strlen($digits) === 8 ? 'DNI' : 'RUC';
            $client = Client::where('doc_type', $docType)->where('doc_number', $digits)->first();
            if (!$client) {
                throw new \RuntimeException('El cliente con documento ' . $digits . ' no existe. Selecciónelo de la lista.');
            }
            return $this->clientPayload($client);
        }

        if ($query === '') {
            $client = Client::where('doc_type', 'N')->where('doc_number', '00000000')->first();
            if (!$client) {
                $client = Client::create([
                    'doc_type' => 'N',
                    'doc_number' => '00000000',
                    'name' => 'CONSUMIDOR FINAL',
                ]);
            }
            return $this->clientPayload($client);
        }

        throw new \RuntimeException('Seleccione un cliente de la lista o deje el campo vacío para Consumidor Final.');
    }

    protected function clientPayload(Client $client): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'doc_number' => $client->doc_number,
            'doc_type' => $client->doc_type,
        ];
    }

    protected function parseIzipayFile(string $path, string $originalName): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);

        $dataRows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $headerIndex = null;
        $headerMap = [];

        foreach ($dataRows as $i => $row) {
            $normalized = array_map(fn ($cell) => strtoupper(trim((string) $cell)), $row);
            if (in_array('CODIGO', $normalized, true) || in_array('IMPORTE', $normalized, true)) {
                $headerIndex = $i;
                foreach ($normalized as $idx => $name) {
                    if ($name !== '') {
                        $headerMap[$name] = $idx;
                    }
                }
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException('No se encontró la fila de cabeceras (CODIGO / IMPORTE). Debe subir el Reporte de Abono de Izipay.');
        }

        $rows = [];
        for ($i = $headerIndex + 1; $i < count($dataRows); $i++) {
            $raw = $dataRows[$i];

            $get = function (string $name) use ($headerMap, $raw) {
                if (!isset($headerMap[$name]) || !isset($raw[$headerMap[$name]])) {
                    return null;
                }
                return $raw[$headerMap[$name]];
            };

            $importe = $this->toFloat($get('IMPORTE'));
            if ($importe === null || $importe <= 0) {
                continue;
            }

            $date = $this->toDate($get('FECHA DE TRANSACCION'));
            if ($date === null) {
                continue;
            }

            $voucher = trim((string) ($get('NUM DE REF (VOUCHER)') ?? ''));

            $rows[] = [
                'selected' => true,
                'codigo' => trim((string) ($get('CODIGO') ?? '')),
                'fecha' => $date->format('d/m/Y'),
                'hora' => $this->toHour($get('HORA DE TRANSACCION')),
                'importe' => round($importe, 2),
                'comision' => round((float) ($this->toFloat($get('COMISION')) ?? 0), 2),
                'igv' => round((float) ($this->toFloat($get('IGV')) ?? 0), 2),
                'neto' => round((float) ($this->toFloat($get('IMPORTE NETO')) ?? 0), 2),
                'abono' => round((float) ($this->toFloat($get('ABONO DEL LOTE')) ?? 0), 2),
                'voucher' => $voucher,
                'marca' => trim((string) ($get('MARCA DE TARJETA') ?? '')),
                'lote' => trim((string) ($get('NUM DE LOTE') ?? '')),
                'cuotas' => trim((string) ($get('CUOTAS') ?? '')),
                'estado' => trim((string) ($get('ESTADO') ?? '')),
                'autorizacion' => trim((string) ($get('CODIGO DE AUTORIZACION') ?? '')),
                'tarjeta' => trim((string) ($get('NUM DE TARJETA') ?? '')),
                'observaciones' => trim((string) ($get('OBSERVACIONES') ?? '')),
                'tipo_movimiento' => trim((string) ($get('TIPO DE MOVIMIENTO') ?? '')),
                'tipo_captura' => trim((string) ($get('TIPO DE CAPTURA') ?? '')),
                'transaccion' => trim((string) ($get('TRANSACCION') ?? '')),
                'duplicate' => false,
                'client_query' => '',
                'client_id' => null,
                'client_name' => '',
                'client_doc' => '',
                'client_results' => [],
                'invoice_type' => $this->defaultInvoiceType,
                'error' => null,
            ];
        }

        $vouchers = array_values(array_filter(array_column($rows, 'voucher')));
        if (!empty($vouchers)) {
            $existing = array_flip(PaymentRecord::where('source', 'izipay')
                ->whereNotNull('reference')
                ->whereIn('reference', $vouchers)
                ->pluck('reference')
                ->all());

            foreach ($rows as $i => $row) {
                if ($row['voucher'] !== '' && isset($existing[$row['voucher']])) {
                    $rows[$i]['duplicate'] = true;
                    $rows[$i]['selected'] = false;
                }
            }
        }

        return $rows;
    }

    protected function toFloat($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^\d.,\-]/', '', (string) $value);
        if ($clean === '' || $clean === '-') {
            return null;
        }

        if (substr_count($clean, '.') > 0 && substr_count($clean, ',') > 0) {
            if (strrpos($clean, ',') < strrpos($clean, '.')) {
                $clean = str_replace(',', '', $clean);
            } else {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            }
        } elseif (substr_count($clean, ',') === 1) {
            $clean = str_replace(',', '.', $clean);
        }

        return (float) $clean;
    }

    protected function toDate($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }
        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestamp(((float) $value - 25569) * 86400);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $str = trim((string) $value);
        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'm/d/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $str);
            } catch (\Throwable $e) {
                // siguiente formato
            }
        }

        try {
            return Carbon::parse($str);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function toHour($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }
        if (is_numeric($value)) {
            $fraction = (float) $value;
            $seconds = (int) round(($fraction - floor($fraction)) * 86400);
            return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
        }

        $str = trim((string) $value);
        if (preg_match('/(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?/i', $str, $m)) {
            $h = (int) $m[1];
            $meridiem = $m[3] ?? '';
            if (strtoupper($meridiem) === 'PM' && $h < 12) {
                $h += 12;
            }
            if (strtoupper($meridiem) === 'AM' && $h === 12) {
                $h = 0;
            }
            return sprintf('%02d:%02d', $h, (int) $m[2]);
        }

        return '';
    }

    protected function rowPaymentDate(array $row): Carbon
    {
        $date = Carbon::createFromFormat('d/m/Y', $row['fecha']);
        if (!empty($row['hora'])) {
            [$h, $m] = array_map('intval', explode(':', $row['hora']));
            $date->setTime($h, $m);
        }
        return $date;
    }

    protected function buildNotes(array $row): string
    {
        $parts = array_filter([
            $row['marca'] ?? null,
            $row['lote'] ? 'Lote ' . $row['lote'] : null,
            $row['cuotas'] ? 'Cuotas ' . $row['cuotas'] : null,
            $row['estado'] ?? null,
            $row['autorizacion'] ? 'Aut.: ' . $row['autorizacion'] : null,
            $row['observaciones'] ?? null,
        ]);

        return implode(' • ', $parts);
    }

    protected function buildOriginalData(array $row): array
    {
        return [
            'codigo' => $row['codigo'] ?? null,
            'fecha' => $row['fecha'] ?? null,
            'hora' => $row['hora'] ?? null,
            'importe' => $row['importe'] ?? null,
            'comision' => $row['comision'] ?? null,
            'igv' => $row['igv'] ?? null,
            'importe_neto' => $row['neto'] ?? null,
            'abono_lote' => $row['abono'] ?? null,
            'voucher' => $row['voucher'] ?? null,
            'marca_tarjeta' => $row['marca'] ?? null,
            'num_tarjeta' => $row['tarjeta'] ?? null,
            'num_lote' => $row['lote'] ?? null,
            'cuotas' => $row['cuotas'] ?? null,
            'estado' => $row['estado'] ?? null,
            'codigo_autorizacion' => $row['autorizacion'] ?? null,
            'observaciones' => $row['observaciones'] ?? null,
            'tipo_movimiento' => $row['tipo_movimiento'] ?? null,
            'tipo_captura' => $row['tipo_captura'] ?? null,
            'transaccion' => $row['transaccion'] ?? null,
            'pos_codigo' => $row['codigo'] ?? null,
        ];
    }

    public function processSelected()
    {
        if (empty($this->selected)) {
            session()->flash('error', 'Seleccione al menos un pago para facturar.');
            return;
        }

        $company = Company::first();
        if (!$company) {
            session()->flash('error', 'Debe configurar la empresa primero.');
            return;
        }

        $results = ['success' => 0, 'error' => 0, 'errors' => []];

        foreach ($this->selected as $paymentId) {
            $payment = PaymentRecord::find($paymentId);
            if (!$payment || $payment->status !== 'pending') {
                continue;
            }

            try {
                $client = null;
                if ($payment->client_doc) {
                    $client = Client::where('doc_number', $payment->client_doc)->first();
                }
                if (!$client) {
                    $client = Client::create([
                        'doc_type' => 'DNI',
                        'doc_number' => $payment->client_doc ?? '00000000',
                        'name' => $payment->client_name,
                    ]);
                }

                $serie = $payment->invoice_type === 'F' ? 'F001' : 'B001';
                $lastInvoice = Invoice::withTrashed()->where('serie', $serie)->orderBy('number', 'desc')->first();
                $number = $lastInvoice ? $lastInvoice->number + 1 : 1;

                $subtotal = round($payment->amount / 1.18, 2);
                $igv = round($payment->amount - $subtotal, 2);

                $invoice = Invoice::create([
                    'company_id' => $company->id,
                    'client_id' => $client->id,
                    'invoice_type' => $payment->invoice_type,
                    'serie' => $serie,
                    'number' => $number,
                    'issue_date' => now()->toDateString(),
                    'currency' => 'PEN',
                    'subtotal' => $subtotal,
                    'igv' => $igv,
                    'total' => $payment->amount,
                ]);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => 'Servicio odontológico',
                    'quantity' => 1,
                    'unit_price' => $payment->amount,
                    'subtotal' => $subtotal,
                ]);

                try {
                    $invoice->load('items');
                    SunatService::sendInvoice($invoice);
                    PdfService::generate($invoice);
                } catch (\Throwable $e) {
                    $invoice->update([
                        'sunat_status' => 'error',
                        'sunat_description' => $e->getMessage(),
                    ]);
                }

                $payment->update([
                    'status' => 'invoiced',
                    'invoice_id' => $invoice->id,
                ]);

                $results['success']++;
            } catch (\Throwable $e) {
                $results['error']++;
                $results['errors'][] = "Pago #{$payment->id}: {$e->getMessage()}";
            }
        }

        $this->selected = [];
        $this->payments = PaymentRecord::where('status', 'pending')->latest()->get();

        session()->flash('bulkResult', $results);
    }

    public function render()
    {
        return view('livewire.payments.bulk-invoice');
    }
}