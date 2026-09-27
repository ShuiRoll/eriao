<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

new class extends Component
{
    public $totalSales = 0;
    public $totalTransactions = 0;
    public $totalProducts = 0;
    public $totalStock = 0;
    public $inventoryValue = 0;
    public $activeUsers = 0;

    public $salesChange = 0;
    public $transactionChange = 0;
    public $stockChange = 0;
    public $userChange = 0;

    public $salesLabels = [];
    public $salesData = [];

    public $categoryLabels = [];
    public $categoryData = [];

    public $paymentLabels = [];
    public $paymentData = [];

    public $recentTransactions = [];

    public $exportRange = 'this_month';
    public $exportFrom;
    public $exportTo;

    public function mount()
    {
        $this->loadReport();

        $this->exportFrom = now()->startOfMonth()->format('Y-m-d');
        $this->exportTo = now()->format('Y-m-d');
    }

    public function loadReport()
    {
        $this->loadStatistics();
        $this->loadSalesChart();
        $this->loadCategoryChart();
        $this->loadPaymentChart();
        $this->loadRecentTransactions();
    }

    protected function loadStatistics()
    {
        $currentStart = now()->startOfMonth();
        $previousStart = now()->subMonth()->startOfMonth();
        $previousEnd = now()->subMonth()->endOfMonth();

        $currentSales = DB::table('transactions')
            ->whereIn('status', ['completed', 'paid'])
            ->whereBetween('created_at', [
                $currentStart,
                now(),
            ])
            ->sum('total_amount');

        $previousSales = DB::table('transactions')
            ->whereIn('status', ['completed', 'paid'])
            ->whereBetween('created_at', [
                $previousStart,
                $previousEnd,
            ])
            ->sum('total_amount');

        $currentTransactions = DB::table('transactions')
            ->whereIn('status', ['completed', 'paid'])
            ->whereBetween('created_at', [
                $currentStart,
                now(),
            ])
            ->count();

        $previousTransactions = DB::table('transactions')
            ->whereIn('status', ['completed', 'paid'])
            ->whereBetween('created_at', [
                $previousStart,
                $previousEnd,
            ])
            ->count();

        $this->totalSales = (float) DB::table('transactions')
            ->whereIn('status', ['completed', 'paid'])
            ->sum('total_amount');

        $this->totalTransactions = DB::table('transactions')
            ->whereIn('status', ['completed', 'paid'])
            ->count();

        $this->totalProducts = DB::table('product_items')
            ->count();

        $this->totalStock = (int) DB::table('product_items')
            ->sum('quantity');

        $this->inventoryValue = (float) DB::table('product_items')
            ->selectRaw(
                'COALESCE(SUM(quantity * price), 0) as value'
            )
            ->value('value');

        $this->activeUsers = DB::table('users')
            ->where('status', 'active')
            ->count();

        $this->salesChange = $this->calculateChange(
            $currentSales,
            $previousSales
        );

        $this->transactionChange = $this->calculateChange(
            $currentTransactions,
            $previousTransactions
        );

        $this->stockChange = 0;

        $previousActiveUsers = DB::table('users')
            ->where('status', 'active')
            ->whereBetween('created_at', [
                $previousStart,
                $previousEnd,
            ])
            ->count();

        $this->userChange = $this->calculateChange(
            $this->activeUsers,
            $previousActiveUsers
        );
    }

    protected function calculateChange($current, $previous)
    {
        if ((float) $previous === 0.0) {
            return (float) $current > 0 ? 100 : 0;
        }

        return round(
            (($current - $previous) / $previous) * 100,
            1
        );
    }

    protected function loadSalesChart()
    {
        $this->salesLabels = [];
        $this->salesData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $this->salesLabels[] = $date->format('M Y');

            $sales = DB::table('transactions')
                ->whereIn('status', ['completed', 'paid'])
                ->whereBetween('created_at', [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth(),
                ])
                ->sum('total_amount');

            $this->salesData[] = round(
                (float) $sales,
                2
            );
        }
    }

    protected function loadCategoryChart()
    {
        $categories = DB::table('product_categories')
            ->leftJoin(
                'product_items',
                'product_items.category_id',
                '=',
                'product_categories.id'
            )
            ->select(
                'product_categories.name',
                DB::raw(
                    'COALESCE(SUM(product_items.quantity), 0) as quantity'
                )
            )
            ->groupBy(
                'product_categories.id',
                'product_categories.name'
            )
            ->orderBy(
                'product_categories.name'
            )
            ->get();

        $this->categoryLabels = $categories
            ->pluck('name')
            ->values()
            ->all();

        $this->categoryData = $categories
            ->pluck('quantity')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();
    }

    protected function loadPaymentChart()
    {
        $payments = DB::table('transactions')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as total')
            )
            ->whereIn('status', ['completed', 'paid'])
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $this->paymentLabels = $payments
            ->pluck('payment_method')
            ->values()
            ->all();

        $this->paymentData = $payments
            ->pluck('total')
            ->map(fn ($value) => (int) $value)
            ->values()
            ->all();

        if (empty($this->paymentLabels)) {
            $this->paymentLabels = ['No Transactions'];
            $this->paymentData = [0];
        }
    }

    protected function loadRecentTransactions()
    {
        $this->recentTransactions = DB::table('transactions')
            ->leftJoin(
                'users',
                'users.id',
                '=',
                'transactions.employee_id'
            )
            ->select([
                'transactions.id',
                'transactions.first_name',
                'transactions.last_name',
                'transactions.payment_method',
                'transactions.total_amount',
                'transactions.status',
                'transactions.created_at',
                'users.first_name as employee_first_name',
                'users.last_name as employee_last_name',
            ])
            ->orderByDesc('transactions.created_at')
            ->limit(8)
            ->get();
    }

    protected function getExportDates()
    {
        $now = now();

        return match ($this->exportRange) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],

            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay(),
            ],

            'this_week' => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],

            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],

            'this_quarter' => [
                $now->copy()->startOfQuarter(),
                $now->copy()->endOfQuarter(),
            ],

            'past_6_months' => [
                $now->copy()->subMonths(5)->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],

            'past_year' => [
                $now->copy()->subMonths(11)->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],

            'custom' => [
                $this->exportFrom
                    ? now()->parse($this->exportFrom)->startOfDay()
                    : $now->copy()->startOfMonth(),

                $this->exportTo
                    ? now()->parse($this->exportTo)->endOfDay()
                    : $now->copy()->endOfDay(),
            ],

            default => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
        };
    }

    public function updatedExportRange()
    {
        if ($this->exportRange === 'today') {
            $this->exportFrom = now()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->format('Y-m-d');
        }

        if ($this->exportRange === 'yesterday') {
            $this->exportFrom = now()
                ->subDay()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->subDay()
                ->format('Y-m-d');
        }

        if ($this->exportRange === 'this_week') {
            $this->exportFrom = now()
                ->startOfWeek()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->endOfWeek()
                ->format('Y-m-d');
        }

        if ($this->exportRange === 'this_month') {
            $this->exportFrom = now()
                ->startOfMonth()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->endOfMonth()
                ->format('Y-m-d');
        }

        if ($this->exportRange === 'this_quarter') {
            $this->exportFrom = now()
                ->startOfQuarter()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->endOfQuarter()
                ->format('Y-m-d');
        }

        if ($this->exportRange === 'past_6_months') {
            $this->exportFrom = now()
                ->subMonths(5)
                ->startOfMonth()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->endOfMonth()
                ->format('Y-m-d');
        }

        if ($this->exportRange === 'past_year') {
            $this->exportFrom = now()
                ->subMonths(11)
                ->startOfMonth()
                ->format('Y-m-d');

            $this->exportTo = now()
                ->endOfMonth()
                ->format('Y-m-d');
        }
    }

    public function exportReport()
    {
        if (
            $this->exportRange === 'custom' &&
            (!$this->exportFrom || !$this->exportTo)
        ) {
            throw ValidationException::withMessages([
                'exportFrom' => 'Please select both a start and end date.',
            ]);
        }

        if (
            $this->exportRange === 'custom' &&
            $this->exportFrom > $this->exportTo
        ) {
            throw ValidationException::withMessages([
                'exportTo' => 'The end date cannot be earlier than the start date.',
            ]);
        }

        [$from, $to] = $this->getExportDates();

        $filename = 'sales-report-' .
            $from->format('Y-m-d') .
            '-to-' .
            $to->format('Y-m-d') .
            '.csv';

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Transaction ID',
                'Customer',
                'Payment Method',
                'Reference Number',
                'Discount Category',
                'Discount',
                'Tax',
                'Total Amount',
                'Cash',
                'Change',
                'Status',
                'Employee',
                'Date',
            ]);

            DB::table('transactions')
                ->leftJoin(
                    'users',
                    'users.id',
                    '=',
                    'transactions.employee_id'
                )
                ->select([
                    'transactions.id',
                    'transactions.first_name',
                    'transactions.last_name',
                    'transactions.payment_method',
                    'transactions.reference_num',
                    'transactions.discount_category',
                    'transactions.discount_price',
                    'transactions.tax',
                    'transactions.total_amount',
                    'transactions.cash',
                    'transactions.change',
                    'transactions.status',
                    'transactions.created_at',
                    'users.first_name as employee_first_name',
                    'users.last_name as employee_last_name',
                ])
                ->whereBetween('transactions.created_at', [
                    $from,
                    $to,
                ])
                ->orderByDesc('transactions.created_at')
                ->chunk(500, function ($transactions) use ($handle) {
                    foreach ($transactions as $transaction) {
                        fputcsv($handle, [
                            $transaction->id,

                            trim(
                                $transaction->first_name .
                                ' ' .
                                $transaction->last_name
                            ),

                            $transaction->payment_method,

                            $transaction->reference_num,

                            $transaction->discount_category,

                            number_format(
                                (float) $transaction->discount_price,
                                2,
                                '.',
                                ''
                            ),

                            number_format(
                                (float) $transaction->tax,
                                2,
                                '.',
                                ''
                            ),

                            number_format(
                                (float) $transaction->total_amount,
                                2,
                                '.',
                                ''
                            ),

                            number_format(
                                (float) $transaction->cash,
                                2,
                                '.',
                                ''
                            ),

                            number_format(
                                (float) $transaction->change,
                                2,
                                '.',
                                ''
                            ),

                            $transaction->status,

                            trim(
                                ($transaction->employee_first_name ?? '') .
                                ' ' .
                                ($transaction->employee_last_name ?? '')
                            ),

                            $transaction->created_at,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename);
    }
};
?>

<div class="flex flex-col w-full h-full gap-8">
    <div class="flex flex-row items-center">
        <div class="flex flex-col">
            <p class="text-xl font-bold">Reports</p>
            <p>Manage your reports</p>
        </div>

        <flux:spacer />

        <div class="flex items-center gap-2">
            <flux:select
                wire:model.live="exportRange"
                class="w-48"
            >
                <flux:select.option value="today">
                    Today
                </flux:select.option>

                <flux:select.option value="yesterday">
                    Yesterday
                </flux:select.option>

                <flux:select.option value="this_week">
                    This Week
                </flux:select.option>

                <flux:select.option value="this_month">
                    This Month
                </flux:select.option>

                <flux:select.option value="this_quarter">
                    This Quarter
                </flux:select.option>

                <flux:select.option value="past_6_months">
                    Past 6 Months
                </flux:select.option>

                <flux:select.option value="past_year">
                    Past Year
                </flux:select.option>

                <flux:select.option value="custom">
                    Custom
                </flux:select.option>
            </flux:select>

            <flux:button
                wire:click="exportReport"
                variant="primary"
            >
                Export Report
            </flux:button>
        </div>
    </div>

    <div
        x-data
        x-show="$wire.exportRange === 'custom'"
        x-cloak
        class="flex flex-row items-end gap-3"
    >
        <div class="flex flex-col gap-1">
            <flux:label>From</flux:label>

            <flux:input
                type="date"
                wire:model.live="exportFrom"
            />
        </div>

        <div class="flex flex-col gap-1">
            <flux:label>To</flux:label>

            <flux:input
                type="date"
                wire:model.live="exportTo"
            />
        </div>
    </div>

    <x-wirekit::stats cols="4">
        <x-wirekit::stat
            label="Total Sales"
            value="₱{{ number_format($totalSales, 2) }}"
            change="{{ $salesChange > 0 ? '+' : '' }}{{ number_format($salesChange, 1) }}%"
            trend="{{ $salesChange > 0 ? 'up' : ($salesChange < 0 ? 'down' : 'neutral') }}"
            description="vs last month"
        />

        <x-wirekit::stat
            label="Transactions"
            value="{{ number_format($totalTransactions) }}"
            change="{{ $transactionChange > 0 ? '+' : '' }}{{ number_format($transactionChange, 1) }}%"
            trend="{{ $transactionChange > 0 ? 'up' : ($transactionChange < 0 ? 'down' : 'neutral') }}"
            description="vs last month"
        />

        <x-wirekit::stat
            label="Inventory Stock"
            value="{{ number_format($totalStock) }}"
            change="{{ $stockChange > 0 ? '+' : '' }}{{ number_format($stockChange, 1) }}%"
            trend="{{ $stockChange > 0 ? 'up' : ($stockChange < 0 ? 'down' : 'neutral') }}"
            description="{{ number_format($totalProducts) }} products"
        />

        <x-wirekit::stat
            label="Active Users"
            value="{{ number_format($activeUsers) }}"
            change="{{ $userChange > 0 ? '+' : '' }}{{ number_format($userChange, 1) }}%"
            trend="{{ $userChange > 0 ? 'up' : ($userChange < 0 ? 'down' : 'neutral') }}"
            description="vs last month"
        />
    </x-wirekit::stats>

    <div class="grid grid-cols-3 gap-4 w-full h-full">
        <div class="flex flex-col w-full col-span-2">
            <x-wirekit-chart
                library="apexcharts"
                type="line"
                valueDecimals="2"
                valuePrefix="₱"
                height="100%"
                :labels="$salesLabels"
                :datasets="[
                    [
                        'label' => 'Sales',
                        'data' => $salesData,
                    ]
                ]"
                aria-label="Line chart showing sales for the last six months"
            />
        </div>

        <div class="grid grid-cols-1 grid-rows-2 gap-4">
            <x-wirekit-chart
                type="doughnut"
                :labels="$categoryLabels"
                :datasets="[
                    [
                        'data' => $categoryData,
                    ]
                ]"
                height="100"
                aria-label="Doughnut chart showing current inventory by category"
            />

            <x-wirekit-chart
                type="doughnut"
                :labels="$paymentLabels"
                :datasets="[
                    [
                        'data' => $paymentData,
                    ]
                ]"
                height="100"
                aria-label="Doughnut chart showing transactions by payment method"
            />
        </div>
    </div>
</div>