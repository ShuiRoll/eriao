<?php

use Livewire\Component;
use \Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public function sort($column) {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function orders()
    {
        return \App\Models\Transactions::query()
            ->join('users', 'users.id', '=', 'transactions.employee_id')
            ->select([
                'transactions.*',
                'users.first_name as cashier_first_name',
                'users.last_name as cashier_last_name',
            ])
            ->tap(fn ($query) => $this->sortBy
                ? $query->orderBy($this->sortBy, $this->sortDirection)
                : $query
            )
            ->paginate(5);
    }
};
?>

<div class="flex flex-col">
    <div class="flex flex-row">
        <div class="flex flex-col">
            <p class="text-xl font-bold">Transaction History</p>
            <p>Manage your transactions</p>
        </div>
    </div>

    <flux:table :paginate="$this->orders">
        <flux:table.columns>
            <flux:table.column>Cashier</flux:table.column>
            <flux:table.column>Customer</flux:table.column>
            <flux:table.column>Payment Method</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'date'" :direction="$sortDirection" wire:click="sort('date')">Date</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">Status</flux:table.column>
            <flux:table.column sortable :sorted="$sortBy === 'amount'" :direction="$sortDirection" wire:click="sort('amount')">Amount</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->orders as $order)
                <flux:table.row :key="$order->id">
                    <flux:table.cell class="flex items-center gap-3">
                        <flux:avatar size="xs" src="{{ $order->customer_avatar }}" />

                        {{ $order->cashier_first_name . ' ' . $order->cashier_last_name }}
                    </flux:table.cell>

                    <flux:table.cell class="whitespace-nowrap">{{ $order->first_name . ' ' . $order->last_name }}</flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:badge size="sm" :color="$order->status_color">{{ ucwords($order->payment_method) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>{{ Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</flux:table.cell>

                    <flux:table.cell class="py-0">
                        <flux:badge size="sm" :color="$order->status_color">{{ ucwords($order->status) }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell variant="strong">PHP {{ number_format($order->total_amount) }}</flux:table.cell>

                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

</div>