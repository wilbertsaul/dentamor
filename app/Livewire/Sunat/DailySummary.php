<?php

declare(strict_types=1);

namespace App\Livewire\Sunat;

use App\Models\Company;
use App\Models\SunatOperation;
use App\Services\Sunat\ResponseMapper;
use App\Services\Sunat\SummaryService;
use App\Services\SunatService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DailySummary extends Component
{
    public $date;

    public $errorMessage = '';

    public function mount()
    {
        $this->date = now()->toDateString();
    }

    public function updatedDate(): void
    {
        $this->errorMessage = '';
    }

    public function pendingInvoices()
    {
        $company = Company::first();

        if (! $company) {
            return collect();
        }

        return SummaryService::pendingFor($company, Carbon::parse($this->date));
    }

    public function operations()
    {
        $company = Company::first();

        if (! $company) {
            return collect();
        }

        return SunatOperation::query()
            ->where('company_id', $company->id)
            ->whereIn('type', [SunatOperation::TYPE_SUMMARY, SunatOperation::TYPE_VOIDED])
            ->latest('id')
            ->limit(20)
            ->get();
    }

    public function enviar()
    {
        $this->resetErrorMessage();

        $company = Company::first();

        if (! $company) {
            $this->errorMessage = 'Debe configurar la empresa primero.';

            return;
        }

        if (! $company->hasSecondarySol() && $company->production) {
            $this->errorMessage = 'Configura el usuario y la clave SOL secundarios: SUNAT exige un segundo usuario para el resumen diario.';

            return;
        }

        if ($company->fallsBackToPrimarySol() && ! $company->hasPrimarySol()) {
            $this->errorMessage = 'Configura el usuario y la clave SOL principales para poder enviar el resumen.';

            return;
        }

        try {
            $operations = SunatService::sendDailySummary($company, Carbon::parse($this->date));
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();

            return;
        }

        $accepted = collect($operations)->filter(fn (SunatOperation $op) => ResponseMapper::isValid($op->status))->count();
        $processing = collect($operations)->filter(fn (SunatOperation $op) => $op->status === 'processing')->count();

        session()->flash(
            'message',
            'Resumen enviado: '.count($operations).' comunicación(es). Aceptadas: '.$accepted
            .($processing > 0 ? ' | En proceso de validación: '.$processing : '')
        );
    }

    public function consultar($reference)
    {
        $company = Company::first();

        $operation = SunatOperation::query()
            ->where('company_id', $company?->id)
            ->where('reference', $reference)
            ->first();

        if (! $operation) {
            return;
        }

        try {
            $fresh = SunatService::refreshOperation($operation);
            $message = $reference.': '.ResponseMapper::label($fresh->status)
                .($fresh->code !== null ? ' ('.$fresh->code.')' : '');
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar el CDR de '.$reference.': '.$e->getMessage());
            $message = $reference.': '.$e->getMessage();
        }

        if (ResponseMapper::isValid($operation->status)) {
            session()->flash('message', $message);

            return;
        }

        $this->errorMessage = $message;
    }

    public function render()
    {
        $company = Company::first();

        return view('livewire.sunat.daily-summary', [
            'pending' => $this->pendingInvoices(),
            'operations' => $this->operations(),
            'company' => $company,
            'usesPrimarySol' => (bool) $company?->fallsBackToPrimarySol(),
        ]);
    }
}
