<?php

namespace App\Console\Commands;

use App\Jobs\SendInvoiceToSunat;
use App\Models\Invoice;
use App\Models\SunatOperation;
use App\Services\Sunat\InvoiceSender;
use App\Services\Sunat\ResponseMapper;
use App\Services\Sunat\SummaryService;
use Illuminate\Console\Command;

class RetrySunatDocuments extends Command
{
    protected $signature = 'sunat:reintentar
                            {--limit=50 : Máximo de comprobantes a procesar por ejecución}
                            {--minutes=2 : Espera mínima desde el último intento}
                            {--queue : Encolar en la cola en lugar de enviar en línea}
                            {--dry-run : Solo listar qué se enviaría}';

    protected $description = 'Reintenta el envío de comprobantes con error y consulta el estado de los resúmenes y bajas en SUNAT';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $minutes = (int) $this->option('minutes');
        $dryRun = (bool) $this->option('dry-run');

        $this->components->info('Reintento de envíos a SUNAT');

        $pending = Invoice::query()
            ->whereIn('sunat_status', ['pending', 'sent', ResponseMapper::ERROR])
            ->where(fn ($query) => $query->where('is_reservation', false)->orWhereNull('is_reservation'))
            ->where('updated_at', '<=', now()->subMinutes(max($minutes, 0)))
            ->orderBy('id')
            ->limit(max($limit, 1))
            ->get(['id', 'serie', 'number', 'sunat_status', 'sunat_code']);

        $sent = 0;
        $failed = 0;

        foreach ($pending as $invoice) {
            $label = $invoice->full_number.' ['.ResponseMapper::label($invoice->sunat_status).']';

            if ($dryRun) {
                $this->components->twoColumnDetail($label, 'se enviaría');

                continue;
            }

            if ($this->option('queue')) {
                SendInvoiceToSunat::dispatch($invoice->id);
                $this->components->twoColumnDetail($label, 'encolado');

                $sent++;

                continue;
            }

            $result = InvoiceSender::send($invoice->fresh()->loadMissing(['company', 'client', 'items', 'relatedInvoice']));

            if ($result['accepted'] ?? false) {
                $this->components->twoColumnDetail($label, '<fg=green>'.ResponseMapper::label($result['status']).'</>');
                $sent++;

                continue;
            }

            $this->components->twoColumnDetail($label, '<fg=red>'.($result['description'] ?? 'sin respuesta').'</>');
            $failed++;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Comprobantes reenviados', (string) $sent);
        $this->components->twoColumnDetail('Comprobantes con error', (string) $failed);

        $refreshed = $this->refreshOperations($dryRun);

        $this->newLine();
        $this->components->twoColumnDetail('Operaciones consultadas', (string) $refreshed);

        if (! $dryRun && ($sent > 0 || $refreshed > 0)) {
            $this->components->info('Proceso finalizado. Revisa el detalle en Configuración >SUNAT y los logs en storage/logs.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function refreshOperations(bool $dryRun): int
    {
        $pendingCodes = implode(', ', SummaryService::TICKET_PENDING_CODES);

        $operations = SunatOperation::query()
            ->whereNotNull('ticket')
            ->where(function ($query) use ($pendingCodes): void {
                $query->whereIn('status', ['sent', 'processing'])
                    ->orWhere(function ($query) use ($pendingCodes): void {
                        // Recupera operaciones que quedaron en error solo porque el
                        // ticket aún estaba en proceso (p. ej. "0098" sin normalizar).
                        $query->where('status', ResponseMapper::ERROR)
                            ->whereRaw("CAST(code AS UNSIGNED) IN ({$pendingCodes})");
                    });
            })
            ->orderBy('id')
            ->limit(50)
            ->get();

        $count = 0;

        foreach ($operations as $operation) {
            if ($dryRun) {
                $this->components->twoColumnDetail($operation->reference, 'se consultaría');

                $count++;

                continue;
            }

            try {
                $fresh = SummaryService::refresh($operation);
                $this->components->twoColumnDetail(
                    $operation->reference,
                    ResponseMapper::label($fresh->status).($fresh->code !== null ? ' ('.$fresh->code.')' : '')
                );
            } catch (\Throwable $e) {
                $this->components->twoColumnDetail($operation->reference, '<fg=red>'.$e->getMessage().'</>');
            }

            $count++;
        }

        return $count;
    }
}
