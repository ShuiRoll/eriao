<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use App\Models\ProductItem;

new class extends Component
{
    public $productItems;

    public function mount()
    {
        $this->loadProducts();
    }

    public function loadProducts()
    {
        $this->productItems = ProductItem::query()
            ->leftJoin(
                'product_categories',
                'product_categories.id',
                '=',
                'product_items.category_id'
            )
            ->select([
                'product_items.*',
                'product_categories.name as product_category_name',
            ])
            ->get();
    }

    #[On('onRefreshProducts')]
    public function refreshProducts()
    {
        $this->loadProducts();
    }

    #[Renderless]
    public function onStockIn($id)
    {
        $this->dispatch('onLoadProductID', id: $id);
    }

    #[Renderless]
    public function onStockOut($id)
    {
        $this->dispatch('onLoadProductIDStockOut', id: $id);
    }
};
?>

<div class="flex flex-col gap-4 w-full">
    <div class="flex flex-row items-center">
        <div class="flex flex-col">
            <p class="text-xl font-bold">Inventory</p>
            <p>Manage your Inventory</p>
        </div>

        <flux:spacer />

        <flux:modal.trigger name="new-item">
            <flux:button variant="primary" icon="plus">
                New Item
            </flux:button>
        </flux:modal.trigger>
    </div>

    <div class="grid grid-cols-3 gap-4">
        @foreach ($productItems as $product)
            <div
                wire:key="product-{{ $product->id }}"
                class="flex flex-col gap-4 border border-zinc-200 p-6 rounded-xl"
            >
                <span class="font-medium">
                    {{ $product->name }}

                    <x-wirekit::badge
                        class="ml-1"
                        intent="accent"
                    >
                        {{ $product->product_category_name }}
                    </x-wirekit::badge>
                </span>

                <x-wirekit::progress
                    :value="intval($product->quantity)"
                    :max="intval($product->max)"
                    intent="success"
                    label="Quantity"
                    show-value
                />

                <div class="grid grid-cols-2 gap-2">
                    <flux:modal.trigger name="stock-in">
                        <flux:button
                            wire:click="onStockIn({{ $product->id }})"
                            variant="outline"
                        >
                            Stock In
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:modal.trigger name="stock-out">
                        <flux:button
                            wire:click="onStockOut({{ $product->id }})"
                            variant="outline"
                        >
                            Stock Out
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        @endforeach
    </div>

    <livewire:modals.inventory.new-item />
    <livewire:modals.inventory.stock-in />
    <livewire:modals.inventory.stock-out />
</div>