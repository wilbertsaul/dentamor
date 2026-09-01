<?php

declare(strict_types=1);

namespace App\Livewire\Services;

use App\Models\Service;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editMode = false;
    public $service_id;
    public $code;
    public $name;
    public $description;
    public $price;

    protected function rules()
    {
        $unique = 'unique:services,code';
        if ($this->editMode) {
            $unique .= ',' . $this->service_id;
        }

        return [
            'code' => 'required|' . $unique . '|max:10',
            'name' => 'required',
            'description' => 'nullable',
            'price' => 'required|numeric|min:0',
        ];
    }

    public function render()
    {
        $services = Service::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('code', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.services.index', compact('services'));
    }

    public function create()
    {
        $this->reset(['service_id', 'code', 'name', 'description', 'price']);
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        $this->service_id = $service->id;
        $this->code = $service->code;
        $this->name = $service->name;
        $this->description = $service->description;
        $this->price = $service->price;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editMode) {
            Service::findOrFail($this->service_id)->update([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'price' => $this->price,
            ]);
            session()->flash('message', 'Servicio actualizado correctamente.');
        } else {
            Service::create([
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'price' => $this->price,
            ]);
            session()->flash('message', 'Servicio creado correctamente.');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        Service::findOrFail($id)->delete();
        session()->flash('message', 'Servicio eliminado correctamente.');
    }
}
