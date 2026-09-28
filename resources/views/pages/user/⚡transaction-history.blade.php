<?php

use App\Models\Transactions;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    // Only these keys may be sorted, mapped to real (table-qualified) columns
    private const SORTABLE = [
        'created_at'   => 'transactions.created_at',
        'status'       => 'transactions.status',
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
            'pending'           => 'yellow',
            'cancelled', 'canceled', 'refunded', 'failed' => 'red',
            default             => 'zinc',
        };
    }

    public function paymentColor(?string $method): string
    {
        return match (strtolower((string) $method)) {
            'cash'  => 'green',
            'card'  => 'blue',
            'gcash', 'maya' => 'sky',
            default => 'zinc',
        };
    }
};
?>

<div class="flex flex-col gap-4">
    <div class="flex flex-col">
        <p class="text-xl font-bold">Transaction History</p>
        <p>Manage your transactions</p>
    </div>

    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>Cashier</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Payment Method</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'total_amount'" :direction="$sortDirection" wire:click="sort('total_amount')">Amount</flux:table.column>
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
                        {{ trim($order->first_name . ' ' . $order->last_name) ?: 'Walk-in' }}
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
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500">
                        No transactions yet.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</div>