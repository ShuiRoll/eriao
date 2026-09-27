<?php

use Livewire\Component;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public $totalProducts = 0;
    public $totalStock = 0;
    public $inventoryValue = 0;
    public $restockNeeded = 0;

    public $salesLabels = [];
    public $salesData = [];

    public $restockRecommendations = [];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->loadStatistics();
        $this->loadSalesChart();
        $this->loadRecommendations();
    }

    protected function loadStatistics()
    {
        $this->totalProducts = DB::table('product_items')->count();

        $this->totalStock = (int) DB::table('product_items')
            ->sum('quantity');

        $this->inventoryValue = (float) DB::table('product_items')
            ->selectRaw('COALESCE(SUM(quantity * price), 0) as value')
            ->value('value');
    }

    protected function loadSalesChart()
    {
        $this->salesLabels = [];
        $this->salesData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);

            $this->salesLabels[] = $date->format('M Y');

            $sales = DB::table('orders')
                ->join(
                    'transactions',
                    'transactions.id',
                    '=',
                    'orders.transaction_id'
                )
                ->whereIn('transactions.status', ['completed', 'paid'])
                ->whereBetween('orders.created_at', [
                    $date->copy()->startOfMonth(),
                    $date->copy()->endOfMonth(),
                ])
                ->count();

            $this->salesData[] = (int) $sales;
        }
    }

    protected function loadRecommendations()
    {
        $products = DB::table('product_items')
            ->leftJoin(
                'product_categories',
                'product_categories.id',
                '=',
                'product_items.category_id'
            )
            ->select([
                'product_items.id',
                'product_items.name',
                'product_items.quantity',
                'product_items.max',
                'product_items.price',
                'product_items.status',
                'product_categories.name as category_name',
            ])
            ->get();

        if ($products->isEmpty()) {
            $this->restockRecommendations = [];
            $this->restockNeeded = 0;

            return;
        }

        $productSales = DB::table('orders')
            ->join(
                'transactions',
                'transactions.id',
                '=',
                'orders.transaction_id'
            )
            ->select(
                'orders.product_id',
                DB::raw('COUNT(*) as total_sold')
            )
            ->whereIn('transactions.status', ['completed', 'paid'])
            ->where(
                'orders.created_at',
                '>=',
                now()->subMonths(3)->startOfMonth()
            )
            ->groupBy('orders.product_id')
            ->pluck('total_sold', 'orders.product_id');

        $recommendations = $products
            ->map(function ($product) use ($productSales) {
                $currentStock = max(0, (int) $product->quantity);
                $maximumStock = max(0, (int) $product->max);

                if ($maximumStock <= 0) {
                    return null;
                }

                $soldLastThreeMonths = (int) (
                    $productSales[$product->id] ?? 0
                );

                $averageMonthlySales = round(
                    $soldLastThreeMonths / 3,
                    1
                );

                $stockPercentage = (
                    $currentStock / $maximumStock
                ) * 100;

                $restockQuantity = max(
                    0,
                    $maximumStock - $currentStock
                );

                $estimatedMonthsRemaining = $averageMonthlySales > 0
                    ? round($currentStock / $averageMonthlySales, 1)
                    : null;

                $score = 0;

                if ($currentStock <= 0) {
                    $score += 100;
                } elseif ($stockPercentage <= 10) {
                    $score += 95;
                } elseif ($stockPercentage <= 20) {
                    $score += 85;
                } elseif ($stockPercentage <= 30) {
                    $score += 70;
                } elseif ($stockPercentage <= 40) {
                    $score += 50;
                } elseif ($stockPercentage <= 60) {
                    $score += 25;
                }

                if ($averageMonthlySales > 0) {
                    $score += min(
                        50,
                        $averageMonthlySales * 5
                    );
                }

                if (
                    $currentStock > 0 &&
                    $averageMonthlySales > 0 &&
                    $estimatedMonthsRemaining <= 1
                ) {
                    $score += 30;
                }

                if ($currentStock <= 0 && $averageMonthlySales > 0) {
                    $reason = 'Out of stock with recent sales activity.';
                    $priority = 'Critical';
                    $intent = 'danger';
                } elseif (
                    $averageMonthlySales > 0 &&
                    $estimatedMonthsRemaining <= 1
                ) {
                    $reason = 'Stock may run out within the next month.';
                    $priority = 'High';
                    $intent = 'danger';
                } elseif ($stockPercentage <= 20) {
                    $reason = 'Stock is already below the recommended level.';
                    $priority = 'High';
                    $intent = 'warning';
                } elseif ($stockPercentage <= 40) {
                    $reason = 'Stock is declining and may need replenishment soon.';
                    $priority = 'Medium';
                    $intent = 'warning';
                } else {
                    return null;
                }

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category_name' => $product->category_name ?? 'Uncategorized',
                    'current_stock' => $currentStock,
                    'maximum_stock' => $maximumStock,
                    'average_monthly_sales' => $averageMonthlySales,
                    'restock_quantity' => $restockQuantity,
                    'estimated_months_remaining' => $estimatedMonthsRemaining,
                    'stock_percentage' => round($stockPercentage),
                    'priority' => $priority,
                    'intent' => $intent,
                    'reason' => $reason,
                    'score' => $score,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();

        $this->restockRecommendations = $recommendations->all();
        $this->restockNeeded = $recommendations->count();
    }
};
?>

<div class="flex flex-col gap-8 h-[calc(100vh-8rem)] min-h-0 overflow-hidden">
    <div class="shrink-0">
        <x-wirekit::stats cols="4" stagger>
            <x-wirekit::stat
                animateIn="slide-up"
                animate
                label="Total Products"
                value="{{ number_format($totalProducts) }}"
            />

            <x-wirekit::stat
                animateIn="slide-up"
                animate
                label="Current Stock"
                value="{{ number_format($totalStock) }}"
            />

            <x-wirekit::stat
                animateIn="slide-up"
                animate
                label="Inventory Value"
                value="₱{{ number_format($inventoryValue, 2) }}"
            />

            <x-wirekit::stat
                animateIn="slide-up"
                animate
                label="Restock Needed"
                value="{{ number_format($restockNeeded) }}"
            />
        </x-wirekit::stats>
    </div>

    <div class="grid grid-cols-3 w-full flex-1 min-h-0 gap-8 overflow-hidden">
        <div class="flex flex-col col-span-2 w-full min-h-0 overflow-hidden">
            <x-wirekit-chart
                library="apexcharts"
                type="line"
                height="100%"
                valueDecimals="0"
                valueSuffix=" units"
                :labels="$salesLabels"
                :datasets="[
                    [
                        'label' => 'Units sold',
                        'data' => $salesData,
                    ]
                ]"
                aria-label="Line chart showing units sold over the last six months"
            />
        </div>

        <div class="flex flex-col w-full min-h-0 overflow-hidden border border-zinc-200 rounded-xl p-6 gap-6">
            <div class="flex items-center justify-between gap-4 shrink-0">
                <div class="flex flex-col">
                    <p class="text-xl font-bold">
                        Recommended Restock
                    </p>

                    <p class="text-sm text-zinc-500">
                        Products that may need replenishment
                    </p>
                </div>

                @if ($restockNeeded > 0)
                    <x-wirekit::badge intent="accent">
                        {{ number_format($restockNeeded) }}
                    </x-wirekit::badge>
                @endif
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto overscroll-contain pr-1">
                @if (count($restockRecommendations))
                    <div class="flex flex-col gap-4">
                        @foreach ($restockRecommendations as $recommendation)
                            <div class="flex flex-col gap-4 border border-zinc-200 rounded-xl p-4 shrink-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex flex-col min-w-0">
                                        <p class="font-semibold truncate">
                                            {{ $recommendation['name'] }}
                                        </p>

                                        <p class="text-sm text-zinc-500 truncate">
                                            {{ $recommendation['category_name'] }}
                                        </p>
                                    </div>

                                    <x-wirekit::badge
                                        intent="{{ $recommendation['intent'] }}"
                                    >
                                        {{ $recommendation['priority'] }}
                                    </x-wirekit::badge>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-zinc-500">
                                            Stock Level
                                        </span>

                                        <span class="font-medium">
                                            {{ number_format($recommendation['current_stock']) }}
                                            /
                                            {{ number_format($recommendation['maximum_stock']) }}
                                        </span>
                                    </div>

                                    <div class="w-full h-2 rounded-full bg-zinc-100 overflow-hidden">
                                        <div
                                            class="h-full rounded-full bg-zinc-900"
                                            style="width: {{ min(100, $recommendation['stock_percentage']) }}%"
                                        ></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-zinc-500">
                                            Avg. Monthly Sales
                                        </span>

                                        <span class="font-semibold">
                                            {{ number_format($recommendation['average_monthly_sales'], 1) }}
                                        </span>
                                    </div>

                                    <div class="flex flex-col">
                                        <span class="text-xs text-zinc-500">
                                            Recommended Restock
                                        </span>

                                        <span class="font-semibold">
                                            +{{ number_format($recommendation['restock_quantity']) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="border-t border-zinc-200 pt-3">
                                    <p class="text-sm text-zinc-500">
                                        {{ $recommendation['reason'] }}
                                    </p>

                                    @if ($recommendation['estimated_months_remaining'] !== null)
                                        <p class="text-xs text-zinc-400 mt-1">
                                            Estimated stock remaining:
                                            {{ number_format($recommendation['estimated_months_remaining'], 1) }}
                                            month(s)
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center justify-center h-full text-center py-8">
                        <p class="font-medium">
                            Inventory looks good
                        </p>

                        <p class="text-sm text-zinc-500 mt-1 max-w-xs">
                            No products currently meet the criteria for a restock recommendation.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>