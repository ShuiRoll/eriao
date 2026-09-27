<?php

use Livewire\Component;
use App\Models\ProductCategories;
use App\Models\ProductItem;
use Flux\Flux;

new class extends Component
{
    public $productCategories = [];
    public $name;
    public $category;
    public $quantity;
    public $max;
    public $status;
    
    public function mount() {
        $this->productCategories = ProductCategories::all();
        $this->category = $this->productCategories[0]['id'];
        $this->status = 'available';
    }

    public function onSaveProduct() {
        $this->validate([
            'name' => 'required',
            'category' => 'required',
            'quantity' => 'required',
            'max' => 'required',
            'status' => 'required',
        ]);

        ProductItem::create([
            'name' => $this->name,
            'category_id' => $this->category,
            'quantity' => $this->quantity,
            'max' => $this->max,
            'status' => $this->status,
        ]);

        $this->dispatch('onRefreshProducts');
        $this->onClose();
    }

    public function onClose() {
        Flux::modal('new-item')->close();
    }

    public function onReset() {
        $this->name = '';
        $this->quantity = 0;
        $this->max = 0;
        $this->status = 'available';
    }
};
?>

<flux:modal name="new-item" class="max-w-1/3 w-full" @close="onReset">
    <div class="flex flex-col">
        <p class="text-xl font-bold">New Item</p>
        <p>Create new item</p>

        <div class="flex flex-col gap-4 mt-4">
            <flux:input wire:model='name' label="Name" />

            <div class="grid grid-cols-2 gap-4">
                <flux:select wire:model='category' label="Category" placeholder="Select Category">
                    @foreach ($productCategories as $category)
                        <flux:select.option value="{{ $category['id'] }}">{{ $category['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model='quantity' type="number" label="Quantity" />

                <flux:input wire:model='max' type="number" label="Maximum Quantity" />
                <flux:select wire:model='status' label="Status" placeholder="Status">
                    <flux:select.option value="available">Available</flux:select.option>
                    <flux:select.option value="unavailable">Unavailable</flux:select.option>
                    <flux:select.option value="out_of_stock">Out of Stock</flux:select.option>
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-8">
                <flux:button wire:click='onClose' variant="ghost">Cancel</flux:button>
                <flux:button wire:click='onSaveProduct' variant="primary">Save</flux:button>
            </div>
        </div>
    </div>
</flux:modal>