<?php

use App\Models\Transactions;
use App\Models\ProductItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public ?int $selectedTransactionId = null;

    public string $editIdNumber = '';

    public array $editItems = [];

    public float $originalTransactionTotal = 0;

    public float $originalDiscountAmount = 0;

    public float $originalTaxAmount = 0;

    public string $paymentMethod = 'Cash';

    public string $referenceNumber = '';

    public array $availableProducts = [];

    // Only these keys may be sorted, mapped to real (table-qualified) columns
    private const SORTABLE = [
        'created_at' => 'transactions.created_at',
        'status' => 'transactions.status',
        'total_amount' => 'transactions.total_amount',
    ];

    public function sort(string $column): void
    {
        if (! array_key_exists($column, self::SORTABLE)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        return Transactions::query()
            ->leftJoin('users', 'users.id', '=', 'transactions.employee_id')
            ->select([
                'transactions.*',
                'users.first_name as cashier_first_name',
                'users.last_name as cashier_last_name',
            ])
            ->orderBy(self::SORTABLE[$this->sortBy], $this->sortDirection)
            ->paginate(5);
    }

    public function statusColor(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'completed', 'paid' => 'green',
            'pending' => 'yellow',
            'cancelled', 'canceled', 'refunded', 'failed' => 'red',
            default => 'zinc',
        };
    }

    public function paymentColor(?string $method): string
    {
        return match (strtolower((string) $method)) {
            'cash' => 'green',
            'card' => 'blue',
            'gcash', 'maya' => 'sky',
            default => 'zinc',
        };
    }

    public function editPendingTransaction(int $id): void
    {
        $transaction = Transactions::find($id);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        $this->selectedTransactionId = $transaction->id;
        $this->editIdNumber = (string) $transaction->id_number;
        $this->originalTransactionTotal = (float) $transaction->total_amount;
        $this->originalDiscountAmount = (float) $transaction->discount_price;
        $this->originalTaxAmount = (float) $transaction->tax;
        $this->availableProducts = ProductItem::query()
            ->whereIn('status', ['available', 'out_of_stock'])
            ->orderBy('name')
            ->get(['id', 'name', 'price'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
            ])
            ->all();
        $this->editItems = DB::table('orders')
            ->leftJoin('product_items', 'product_items.id', '=', 'orders.product_id')
            ->where('orders.transaction_id', $transaction->id)
            ->select([
                'orders.id as order_line_id',
                'orders.product_id',
                'product_items.name',
                'product_items.price',
                'product_items.front_quantity',
            ])
            ->orderBy('orders.id')
            ->get()
            ->map(fn ($item) => [
                    'order_line_id' => $item->order_line_id,
                    'product_id' => $item->product_id,
                    'original_product_id' => $item->product_id,
                    'name' => $item->name ?? 'Missing product #' . $item->product_id,
                    'price' => (float) ($item->price ?? 0),
                    'front_quantity' => (int) $item->front_quantity,
                    'quantity' => 1,
                ])
            ->values()
            ->all();

        Flux::modal('edit-pending-transaction')->show();
    }

    public function replacementTotals(): array
    {
        $prices = collect($this->availableProducts)->keyBy('id');
        $replacementItemsTotal = collect($this->editItems)->sum(function ($item) use ($prices) {
            $product = $prices->get($item['product_id']);
            $unitPrice = $product['price'] ?? (
                (int) $item['product_id'] === (int) $item['original_product_id']
                    ? $item['price']
                    : 0
            );

            return (int) $item['quantity'] * (float) $unitPrice;
        });

        $itemAllowance = max(
            0,
            $this->originalTransactionTotal
                + $this->originalDiscountAmount
                - $this->originalTaxAmount
        );
        $appliedDiscount = min($this->originalDiscountAmount, $replacementItemsTotal);
        $projectedTotal = max(
            0,
            $replacementItemsTotal - $appliedDiscount + $this->originalTaxAmount
        );

        return [
            'item_allowance' => $itemAllowance,
            'replacement_items_total' => $replacementItemsTotal,
            'projected_total' => $projectedTotal,
            'estimated_refund' => max(0, $this->originalTransactionTotal - $projectedTotal),
            'exceeds_allowance' => $replacementItemsTotal > $itemAllowance,
        ];
    }

    public function savePendingTransaction(): void
    {
        $this->validate([
            'editIdNumber' => ['required', 'string', 'max:255'],
            'editItems' => ['required', 'array', 'min:1'],
            'editItems.*.product_id' => ['required', 'integer', 'exists:product_items,id'],
            'editItems.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () {
            $transaction = Transactions::query()
                ->whereKey($this->selectedTransactionId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            $productIds = collect($this->editItems)->pluck('product_id')->all();
            $products = ProductItem::whereIn('id', $productIds)->get()->keyBy('id');
            $total = 0;

            foreach ($this->editItems as $item) {
                $product = $products->get($item['product_id']);

                if (!$product) {
                    throw ValidationException::withMessages(['editItems' => 'A selected product no longer exists.']);
                }

                $total += (int) $item['quantity'] * (float) $product->price;
            }

            $original = Transactions::findOrFail($this->selectedTransactionId);
            $targetSubtotal = (float) $original->total_amount
                + (float) $original->discount_price
                - (float) $original->tax;

            if (round($total, 2) > round($targetSubtotal, 2)) {
                throw ValidationException::withMessages([
                    'editItems' => 'Replacement items cannot exceed the original transaction amount.',
                ]);
            }

            $updatedDiscount = min((float) $original->discount_price, $total);
            $updatedTotal = max(0, $total - $updatedDiscount + (float) $original->tax);

            DB::table('orders')->where('transaction_id', $transaction->id)->delete();

            foreach ($this->editItems as $item) {
                for ($quantity = 0; $quantity < (int) $item['quantity']; $quantity++) {
                    DB::table('orders')->insert([
                        'transaction_id' => $transaction->id,
                        'product_id' => $item['product_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $transaction->update([
                'id_number' => trim($this->editIdNumber),
                'discount_price' => round($updatedDiscount, 2),
                'total_amount' => round($updatedTotal, 2),
            ]);
        });

        Flux::modal('edit-pending-transaction')->close();
        $this->resetTransactionForm();
        unset($this->orders);
    }

    public function finalizeTransaction(): void
    {
        if (!in_array(auth()->user()->role, ['admin', 'cashier'], true)) {
            abort(403);
        }

        $this->validate([
            'paymentMethod' => ['required', 'string', 'max:255'],
            'referenceNumber' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () {
            $transaction = Transactions::query()
                ->whereKey($this->selectedTransactionId)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();

            $orders = DB::table('orders')
                ->where('transaction_id', $transaction->id)
                ->select('product_id', DB::raw('COUNT(*) as quantity'))
                ->groupBy('product_id')
                ->get();

            $products = ProductItem::whereIn('id', $orders->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($orders as $order) {
                $product = $products->get($order->product_id);

                if (!$product || $product->status !== 'available' || (int) $product->front_quantity < (int) $order->quantity) {
                    throw ValidationException::withMessages([
                        'paymentMethod' => 'The slip cannot be paid because front inventory has changed.',
                    ]);
                }
            }

            foreach ($orders as $order) {
                $product = $products->get($order->product_id);
                $frontQuantity = (int) $product->front_quantity - (int) $order->quantity;
                $totalQuantity = $frontQuantity + (int) $product->warehouse_quantity;

                $product->update([
                    'front_quantity' => $frontQuantity,
                    'quantity' => $totalQuantity,
                    'status' => $totalQuantity > 0 ? 'available' : 'out_of_stock',
                ]);
            }

            $transaction->update([
                'cashier_id' => auth()->id(),
                'payment_method' => $this->paymentMethod,
                'reference_num' => trim($this->referenceNumber) ?: null,
                'cash' => 0,
                'change' => 0,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        });

        Flux::modal('finalize-transaction')->close();
        $this->resetTransactionForm();
        unset($this->orders);
    }

    public function openFinalizeTransaction(int $id): void
    {
        $transaction = Transactions::find($id);

        if (!$transaction || $transaction->status !== 'pending') {
            return;
        }

        $this->selectedTransactionId = $transaction->id;
        $this->paymentMethod = 'Cash';
        $this->referenceNumber = '';
        Flux::modal('finalize-transaction')->show();
    }

    public function resetTransactionForm(): void
    {
        $this->selectedTransactionId = null;
        $this->editIdNumber = '';
        $this->editItems = [];
        $this->originalTransactionTotal = 0;
        $this->originalDiscountAmount = 0;
        $this->originalTaxAmount = 0;
        $this->paymentMethod = 'Cash';
        $this->referenceNumber = '';
        $this->availableProducts = [];
    }
};
?>

<div>
<div class="flex flex-col gap-4">
    <div class="flex flex-col">
        <p class="text-xl font-bold">Transactions</p>
        <p>Review and end student purchases</p>
    </div>

    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>Cashier</flux:table.column>
            <flux:table.column>Student ID</flux:table.column>
            <flux:table.column>Payment Method</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'total_amount'" :direction="$sortDirection" wire:click="sort('total_amount')">Amount</flux:table.column>
            <flux:table.column>Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->orders as $order)
                @php $cashier = trim($order->cashier_first_name . ' ' . $order->cashier_last_name) ?: 'Unknown'; @endphp

                <flux:table.row :key="$order->id">
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar size="xs" :name="$cashier" />
                        {{ $cashier }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $order->id_number ?: '—' }}
                    </flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:badge size="sm" :color="$this->paymentColor($order->payment_method)">
                            {{ ucwords($order->payment_method) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        {{ $order->created_at->format('M d, Y') }}
                    </flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:badge size="sm" :color="$this->statusColor($order->status)">
                            {{ ucwords($order->status) }}
                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell variant="strong" class="whitespace-nowrap">
                        PHP {{ number_format($order->total_amount, 2) }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">
                        @if ($order->status === 'pending')
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    icon="ellipsis-horizontal"
                                    variant="ghost"
                                    size="sm"
                                    square
                                    aria-label="Transaction actions"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        wire:click="editPendingTransaction({{ $order->id }})"
                                        icon="arrow-path"
                                    >
                                        Refund / replace items
                                    </flux:menu.item>

                                    @if (in_array(auth()->user()->role, ['admin', 'cashier'], true))
                                        <flux:menu.item
                                            wire:click="openFinalizeTransaction({{ $order->id }})"
                                            icon="check"
                                        >
                                            Complete transaction
                                        </flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                        <flux:table.cell colspan="7" class="text-center text-zinc-500">
                        No transactions yet.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>

<flux:modal name="edit-pending-transaction" class="max-w-xl w-full">
    @php($replacementTotals = $this->replacementTotals())
    <div class="flex flex-col gap-5">
        <div>
                <p class="text-lg font-semibold">Refund / Replace Items</p>
            <p class="text-sm text-zinc-500">Replace individual items. Any difference below the original is the estimated refund; the transaction cannot be increased or voided.</p>
        </div>

        <flux:input wire:model="editIdNumber" label="Student ID Number" />

        @forelse ($editItems as $itemKey => $item)
            <div wire:key="replacement-item-{{ $itemKey }}" class="flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-medium">{{ $item['name'] }}</p>
                    <p class="text-sm text-zinc-500">Individual item</p>
                </div>
                <flux:select wire:model.live="editItems.{{ $itemKey }}.product_id" label="Replace with">
                    @if (!collect($availableProducts)->contains('id', $item['product_id']))
                        <flux:select.option value="{{ $item['product_id'] }}" disabled>
                            {{ $item['name'] }} (unavailable)
                        </flux:select.option>
                    @endif
                    @foreach ($availableProducts as $product)
                        <flux:select.option value="{{ $product['id'] }}">{{ $product['name'] }} (PHP {{ number_format($product['price'], 2) }})</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @empty
            <p class="text-sm text-red-600">No items are linked to this slip, so there is nothing to replace.</p>
        @endforelse

        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex justify-between gap-4 text-sm">
                <span class="text-zinc-500">Original transaction total</span>
                <span class="font-medium">₱{{ number_format($originalTransactionTotal, 2) }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-4 text-sm">
                <span class="text-zinc-500">Replacement items total</span>
                <span class="font-medium">₱{{ number_format($replacementTotals['replacement_items_total'], 2) }} / ₱{{ number_format($replacementTotals['item_allowance'], 2) }} allowed</span>
            </div>
            <div class="mt-2 flex justify-between gap-4 text-sm">
                <span class="text-zinc-500">New transaction total</span>
                <span class="font-medium">₱{{ number_format($replacementTotals['projected_total'], 2) }}</span>
            </div>
            <div class="mt-2 flex justify-between gap-4 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <span class="font-semibold">Estimated refund</span>
                <span class="font-bold">₱{{ number_format($replacementTotals['estimated_refund'], 2) }}</span>
            </div>
            @if ($replacementTotals['exceeds_allowance'])
                <p class="mt-2 text-sm text-red-600">Replacement items exceed the original amount. Choose lower-priced items.</p>
            @endif
        </div>

        <flux:button
            wire:click="savePendingTransaction"
            variant="primary"
            :disabled="$replacementTotals['exceeds_allowance'] || empty($editItems)"
        >Replace Items</flux:button>
    </div>
</flux:modal>

<flux:modal name="finalize-transaction" class="max-w-md w-full">
    <div class="flex flex-col gap-5">
        <div>
            <p class="text-lg font-semibold">Complete Transaction</p>
            <p class="text-sm text-zinc-500">Record the payment and deduct the sold items from front inventory.</p>
        </div>

        <flux:select wire:model="paymentMethod" label="Payment Method">
            <flux:select.option value="Cash">Cash</flux:select.option>
            <flux:select.option value="GCash">GCash</flux:select.option>
            <flux:select.option value="Card">Card</flux:select.option>
            <flux:select.option value="Bank Transfer">Bank Transfer</flux:select.option>
        </flux:select>

        <flux:input wire:model="referenceNumber" label="Reference Number" />
        <flux:button wire:click="finalizeTransaction" variant="primary">Complete transaction</flux:button>
    </div>
</flux:modal>
</div>