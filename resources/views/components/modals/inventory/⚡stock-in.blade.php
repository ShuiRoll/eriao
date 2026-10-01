<?php

use Livewire\Component;
use App\Models\ProductItem;
use Livewire\Attributes\On;
use Flux\Flux;

new class extends Component
{
    public $productID;
    public $productDetails;
    public $quantity = 0;
    public $max = 0;
    public $newQuantity = 0;

    #[On('onLoadProductID')]
    public function onLoadData($id) {
        $this->productID = $id;

        $this->productDetails = ProductItem::where('id', $id)->first();
        $this->quantity = $this->productDetails['quantity'];
        $this->max = $this->productDetails['max'];
    }

    public function onSave() {
        $this->validate([
            'newQuantity' => 'required|integer|min:1',
        ]);

        $frontQuantity = (int) $this->productDetails->front_quantity;
        $warehouseQuantity = (int) $this->productDetails->warehouse_quantity;

        if ((int) $this->newQuantity > $warehouseQuantity) {
            $this->addError('newQuantity', 'Cannot move more stock than is available in the warehouse.');

            return;
        }

        $frontQuantity += (int) $this->newQuantity;
        $warehouseQuantity -= (int) $this->newQuantity;

        ProductItem::where('id', $this->productID)->update([
            'quantity' => $frontQuantity + $warehouseQuantity,
            'front_quantity' => $frontQuantity,
            'warehouse_quantity' => $warehouseQuantity,
            'status' => 'available',
        ]);
        $this->dispatch('onRefreshProducts');
        $this->onClose();
    }

    public function onClose() {
        Flux::modal('stock-in')->close();
    }

    public function onReset() {
        $this->newQuantity = 0;
    }
};
?>

<flux:modal name="stock-in" @close="onReset">
    <div class="flex flex-col">
        <p class="text-xl font-medium">Stock In</p>
        <p>Add new stock</p>

        <div class="flex flex-col border border-zinc-200 p-4 rounded-xl mt-4 gap-2">
            <p>{{ $productDetails['name'] ?? '' }}</p>
            <x-wirekit::progress :value="$quantity" max="{{ $max }}" intent="success" label="Quantity" show-value />
        </div>

        <div class="flex flex-col mt-4">
            <flux:input wire:model='newQuantity' type="number" label="Quantity" />
            <p class="text-xs text-zinc-700 mt-1">Moves stock from the warehouse to front inventory.</p>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-8">
            <flux:button wire:click="onClose" variant="ghost">Cancel</flux:button>
            <flux:button wire:click='onSave' variant="primary">Save</flux:button>
        </div>
    </div>
</flux:modal>