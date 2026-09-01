<?php

declare(strict_types=1);

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editMode = false;
    public $client_id;
    public $doc_type = 'DNI';
    public $doc_number;
    public $name;
    public $email;
    public $phone;
    public $address;

    protected function rules()
    {
        return [
            'doc_type' => 'required|in:DNI,RUC,CE',
            'doc_number' => 'required|max:15',
            'name' => 'required',
            'email' => 'nullable|email',
            'phone' => 'nullable|max:20',
            'address' => 'nullable',
        ];
    }

    public function render()
    {
        $clients = Client::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('doc_number', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.clients.index', compact('clients'));
    }

    public function create()
    {
        $this->reset(['client_id', 'doc_number', 'name', 'email', 'phone', 'address']);
        $this->doc_type = 'DNI';
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $client = Client::findOrFail($id);
        $this->client_id = $client->id;
        $this->doc_type = $client->doc_type;
        $this->doc_number = $client->doc_number;
        $this->name = $client->name;
        $this->email = $client->email;
        $this->phone = $client->phone;
        $this->address = $client->address;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            Client::findOrFail($this->client_id)->update([
                'doc_type' => $this->doc_type,
                'doc_number' => $this->doc_number,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
            ]);
            session()->flash('message', 'Cliente actualizado correctamente.');
        } else {
            Client::create([
                'doc_type' => $this->doc_type,
                'doc_number' => $this->doc_number,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
            ]);
            session()->flash('message', 'Cliente creado correctamente.');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        Client::findOrFail($id)->delete();
        session()->flash('message', 'Cliente eliminado correctamente.');
    }
}
