<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Company\Settings as CompanySettings;
use App\Livewire\Dashboard;
use App\Livewire\Invoices\Create as InvoicesCreate;
use App\Livewire\Invoices\CreditNote as InvoicesCreditNote;
use App\Livewire\Invoices\Girar as InvoicesGirar;
use App\Livewire\Invoices\Index as InvoicesIndex;
use App\Livewire\Invoices\Voided as InvoicesVoided;
use App\Livewire\Payments\BulkInvoice as PaymentsBulk;
use App\Livewire\Payments\Index as PaymentsIndex;
use App\Livewire\Services\Index as ServicesIndex;
use App\Livewire\Sunat\DailySummary as SunatDailySummary;
use App\Models\Invoice;
use App\Services\PdfService;
use App\Services\Sunat\ResponseMapper;
use App\Services\SunatService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/invoices/{invoice}/pdf-public', function (Invoice $invoice) {
    if (! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
        PdfService::generate($invoice);
    }

    return Storage::disk('local')->response($invoice->pdf_path);
})->middleware('signed')->name('invoices.pdf.public');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/clients', ClientsIndex::class)->name('clients.index');
    Route::get('/services', ServicesIndex::class)->name('services.index');
    Route::get('/company', CompanySettings::class)->name('company.settings');
    Route::get('/invoices', InvoicesIndex::class)->name('invoices.index');
    Route::get('/invoices/create', InvoicesCreate::class)->name('invoices.create');
    Route::get('/invoices/{reservation}/girar', InvoicesGirar::class)->name('invoices.girar');
    Route::get('/invoices/{invoice}/nota-credito', InvoicesCreditNote::class)->name('invoices.nota-credito');
    Route::get('/invoices/{invoice}/anular', InvoicesVoided::class)->name('invoices.anular');
    Route::get('/sunat/comunicaciones', SunatDailySummary::class)->name('sunat.resumen');
    Route::get('/payments', PaymentsIndex::class)->name('payments.index');
    Route::get('/payments/bulk', PaymentsBulk::class)->name('payments.bulk');

    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        if (! $invoice->pdf_path) {
            PdfService::generate($invoice);
        }

        return Storage::disk('local')->download($invoice->pdf_path);
    })->name('invoices.pdf');

    Route::get('/invoices/{invoice}/pdf-view', function (Invoice $invoice) {
        if (! $invoice->pdf_path) {
            PdfService::generate($invoice);
        }

        return Storage::disk('local')->response($invoice->pdf_path);
    })->name('invoices.pdf.view');

    Route::get('/invoices/{invoice}/pdf-regenerate', function (Invoice $invoice) {
        PdfService::generate($invoice);

        return redirect()->back()->with('message', 'PDF regenerado.');
    })->name('invoices.pdf.regenerate');

    Route::get('/invoices/{invoice}/xml', function (Invoice $invoice) {
        if (! $invoice->xml_path) {
            return redirect()->back()->with('error', 'XML no disponible.');
        }

        return Storage::disk('local')->download($invoice->xml_path, $invoice->full_number.'.xml');
    })->name('invoices.xml');

    Route::post('/invoices/{invoice}/enviar', function (Invoice $invoice) {
        abort_if($invoice->is_reservation, 422, 'Una reserva no puede enviarse a SUNAT.');

        $result = SunatService::sendInvoice($invoice->loadMissing(['company', 'client', 'items', 'relatedInvoice']));

        if ($result['accepted'] ?? false) {
            return redirect()->back()->with(
                'message',
                $invoice->full_number.': '.ResponseMapper::label($result['status']).'.'
            );
        }

        return redirect()->back()->with(
            'error',
            trim($invoice->full_number.': '.ResponseMapper::label($result['status']).' — '.($result['description'] ?? 'sin respuesta'))
        );
    })->name('invoices.send');

    Route::post('/invoices/{invoice}/whatsapp', function (Invoice $invoice) {
        abort_if($invoice->is_reservation, 422, 'Una reserva no puede enviarse por WhatsApp.');

        $result = WhatsAppService::sendInvoice($invoice->loadMissing(['company', 'client', 'items']));

        return redirect()->back()->with(
            $result['ok'] ? 'message' : 'error',
            trim($invoice->full_number.': '.$result['message'])
        );
    })->name('invoices.whatsapp');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
