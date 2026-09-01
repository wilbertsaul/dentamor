<?php

declare(strict_types=1);

namespace App\Livewire\Company;

use App\Models\Company;
use Livewire\Component;
use Livewire\WithFileUploads;

class Settings extends Component
{
    use WithFileUploads;

    public $company;
    public $ruc;
    public $business_name;
    public $commercial_name;
    public $address;
    public $sunat_username;
    public $sunat_password;
    public $certificate;
    public $production = false;

    public function mount()
    {
        $this->company = Company::first();
        if ($this->company) {
            $this->ruc = $this->company->ruc;
            $this->business_name = $this->company->business_name;
            $this->commercial_name = $this->company->commercial_name;
            $this->address = $this->company->address;
            $this->sunat_username = $this->company->sunat_username;
            $this->sunat_password = $this->company->sunat_password;
            $this->production = $this->company->production ?? false;
        }
    }

    public function save()
    {
        $this->validate([
            'ruc' => 'required|size:11',
            'business_name' => 'required',
            'commercial_name' => 'nullable',
            'address' => 'nullable',
            'sunat_username' => 'nullable',
            'sunat_password' => 'nullable',
            'certificate' => 'nullable|file|mimes:pem,crt,cer|max:2048',
            'production' => 'boolean',
        ]);

        $data = [
            'ruc' => $this->ruc,
            'business_name' => $this->business_name,
            'commercial_name' => $this->commercial_name,
            'address' => $this->address,
            'sunat_username' => $this->sunat_username,
            'sunat_password' => $this->sunat_password,
            'production' => $this->production,
        ];

        if ($this->certificate) {
            $data['certificate_path'] = $this->certificate->store('certificates', 'local');
        }

        if ($this->company) {
            $this->company->update($data);
        } else {
            $this->company = Company::create($data);
        }

        session()->flash('message', 'Configuración guardada correctamente.');
    }

    public function render()
    {
        return view('livewire.company.settings');
    }
}
