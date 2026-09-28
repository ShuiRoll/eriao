<?php

namespace App\Http\Controllers\Agents;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class GabAI
{
    protected string $model;
    protected string $ollamaUrl;

    public function __construct()
    {
        $this->model = (string) config('services.ollama.model', 'gemma3:27b');
        $this->ollamaUrl = rtrim((string) config('services.ollama.url', 'http://127.0.0.1:11434'), '/') . '/api/chat';
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['required', 'string', 'in:user,assistant'],
            'history.*.content' => ['required', 'string', 'max:20000'],
        ]);

        $streamId = (string) Str::uuid();

        Cache::put('gab-ai:' . $streamId, [
            'user_id' => $request->user()->getAuthIdentifier(),
            'message' => trim($validated['message']),
            'history' => $validated['history'] ?? [],
        ], now()->addMinutes(5));

        return response()->json([
            'status' => 'ready',
            'model' => $this->model,
            'stream_url' => route('gab-ai.stream', ['streamId' => $streamId]),
        ], 202);
    }

    public function stream(Request $request, string $streamId)
    {
        $userId = (string) $request->user()->getAuthIdentifier();

        if (function_exists('session_write_close')) {
            @session_write_close();
        }

        return response()->stream(
            function () use ($userId, $streamId) {
                ignore_user_abort(false);
                set_time_limit(0);
                $this->closeAllOutputBuffers();

                $payloadData = Cache::pull('gab-ai:' . $streamId);

                if (!is_array($payloadData)) {
                    $this->sendEvent(
                        'stream-error',
                        ['message' => 'This AI stream is no longer available.'],
                        true
                    );

                    return;
                }

                if ((string) ($payloadData['user_id'] ?? '') !== $userId) {
                    $this->sendEvent(
                        'stream-error',
                        ['message' => 'This AI stream is not authorized.'],
                        true
                    );

                    return;
                }

                $message = trim((string) ($payloadData['message'] ?? ''));
                $history = is_array($payloadData['history'] ?? null)
                    ? $payloadData['history']
                    : [];

                $this->sendEvent(
                    'ready',
                    ['status' => 'connected'],
                    true
                );

                $this->sendEvent(
                    'thinking',
                    ['status' => 'thinking'],
                    true
                );

                $context = $this->context();

                $messages = $this->buildMessages(
                    $history,
                    $message,
                    $context
                );

                $payload = json_encode(
                    [
                        'model' => $this->model,
                        'messages' => $messages,
                        'stream' => true,
                        'options' => [
                            'temperature' => 0.2,
                        ],
                    ],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );

                if ($payload === false) {
                    $this->sendEvent(
                        'stream-error',
                        ['message' => 'Unable to prepare the Ollama request.'],
                        true
                    );

                    return;
                }

                $curl = curl_init($this->ollamaUrl);

                if ($curl === false) {
                    $this->sendEvent(
                        'stream-error',
                        ['message' => 'Unable to initialize the Ollama connection.'],
                        true
                    );

                    return;
                }

                $buffer = '';
                $httpStatus = 0;
                $curlError = '';
                $lastHeartbeat = microtime(true);
                $sentDone = false;

                $write = function (
                    $handle,
                    string $chunk
                ) use (
                    &$buffer,
                    &$lastHeartbeat,
                    &$sentDone
                ): int {
                    if (connection_aborted()) {
                        return 0;
                    }

                    $buffer .= $chunk;

                    while (
                        (
                            $position = strpos(
                                $buffer,
                                "\n"
                            )
                        ) !== false
                    ) {
                        $line = trim(
                            substr(
                                $buffer,
                                0,
                                $position
                            )
                        );

                        $buffer = substr(
                            $buffer,
                            $position + 1
                        );

                        if ($line === '') {
                            continue;
                        }

                        $data = json_decode(
                            $line,
                            true
                        );

                        if (!is_array($data)) {
                            continue;
                        }

                        if (isset($data['error'])) {
                            $this->sendEvent(
                                'stream-error',
                                [
                                    'message' => (string) $data['error'],
                                ],
                                true
                            );

                            continue;
                        }

                        $content =
                            $data['message']['content']
                            ?? '';

                        if ($content !== '') {
                            $this->sendEvent(
                                'token',
                                [
                                    'content' => $content,
                                ],
                                true
                            );
                        }

                        if (($data['done'] ?? false) === true) {
                            $this->sendEvent(
                                'done',
                                [
                                    'status' => 'complete',
                                ],
                                true
                            );

                            $sentDone = true;
                        }

                        if (
                            microtime(true) -
                            $lastHeartbeat >=
                            5
                        ) {
                            $this->sendComment(
                                'heartbeat',
                                true
                            );

                            $lastHeartbeat =
                                microtime(true);
                        }
                    }

                    return strlen($chunk);
                };

                $progress = function () use (
                    &$lastHeartbeat
                ): int {
                    if (connection_aborted()) {
                        return 1;
                    }

                    $now =
                        microtime(true);

                    if (
                        $now -
                        $lastHeartbeat >=
                        5
                    ) {
                        $this->sendComment(
                            'heartbeat',
                            true
                        );

                        $lastHeartbeat =
                            $now;
                    }

                    return 0;
                };

                curl_setopt_array(
                    $curl,
                    [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $payload,
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'Accept: application/x-ndjson',
                            'Connection: keep-alive',
                        ],
                        CURLOPT_RETURNTRANSFER => false,
                        CURLOPT_HEADER => false,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_CONNECTTIMEOUT => 15,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_TCP_NODELAY => true,
                        CURLOPT_BUFFERSIZE => 1024,
                        CURLOPT_WRITEFUNCTION => $write,
                        CURLOPT_PROGRESSFUNCTION => $progress,
                        CURLOPT_NOPROGRESS => false,
                    ]
                );

                $result = curl_exec($curl);

                $httpStatus =
                    (int) curl_getinfo(
                        $curl,
                        CURLINFO_HTTP_CODE
                    );

                $curlError =
                    curl_error($curl);

                curl_close($curl);

                if ($result === false) {
                    if (!connection_aborted()) {
                        $this->sendEvent(
                            'stream-error',
                            [
                                'message' =>
                                    $curlError !== ''
                                        ? $curlError
                                        : 'Unable to connect to Ollama.',
                            ],
                            true
                        );
                    }

                    return;
                }

                if (
                    $httpStatus < 200 ||
                    $httpStatus >= 300
                ) {
                    $this->sendEvent(
                        'stream-error',
                        [
                            'message' =>
                                'Ollama returned HTTP ' .
                                $httpStatus .
                                '.',
                        ],
                        true
                    );

                    return;
                }

                if (trim($buffer) !== '') {
                    foreach (
                        preg_split(
                            '/\r\n|\n|\r/',
                            trim($buffer)
                        ) as $line
                    ) {
                        $line = trim($line);

                        if ($line === '') {
                            continue;
                        }

                        $data = json_decode(
                            $line,
                            true
                        );

                        if (!is_array($data)) {
                            continue;
                        }

                        if (isset($data['error'])) {
                            $this->sendEvent(
                                'stream-error',
                                [
                                    'message' =>
                                        (string) $data['error'],
                                ],
                                true
                            );

                            continue;
                        }

                        $content =
                            $data['message']['content']
                            ?? '';

                        if ($content !== '') {
                            $this->sendEvent(
                                'token',
                                [
                                    'content' => $content,
                                ],
                                true
                            );
                        }

                        if (
                            ($data['done'] ?? false) === true &&
                            !$sentDone
                        ) {
                            $this->sendEvent(
                                'done',
                                [
                                    'status' => 'complete',
                                ],
                                true
                            );

                            $sentDone = true;
                        }
                    }
                }

                if (
                    !$sentDone &&
                    !connection_aborted()
                ) {
                    $this->sendEvent(
                        'done',
                        [
                            'status' => 'complete',
                        ],
                        true
                    );
                }
            },
            200,
            [
                'Content-Type' =>
                    'text/event-stream; charset=UTF-8',
                'Cache-Control' =>
                    'no-cache, no-store, must-revalidate, no-transform',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
                'X-LiteSpeed-Cache-Control' => 'no-cache',
                'Content-Encoding' => 'identity',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    protected function closeAllOutputBuffers(): void
    {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        @ini_set(
            'zlib.output_compression',
            '0'
        );

        @ini_set(
            'output_buffering',
            '0'
        );

        @ini_set(
            'implicit_flush',
            '1'
        );

        if (function_exists('apache_setenv')) {
            @apache_setenv(
                'no-gzip',
                '1'
            );
        }
    }

    protected function flushOutput(): void
    {
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }

        @flush();
    }

    protected function sendComment(
        string $comment,
        bool $pad = false
    ): void {
        $frame =
            ': ' .
            $comment .
            "\n\n";

        $this->outputFrame(
            $frame,
            $pad
        );
    }

    protected function sendEvent(
        string $event,
        array $data,
        bool $pad = false
    ): void {
        $json =
            json_encode(
                $data,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

        if ($json === false) {
            return;
        }

        $frame =
            'event: ' .
            $event .
            "\n";

        $frame .=
            'data: ' .
            $json .
            "\n\n";

        $this->outputFrame(
            $frame,
            $pad
        );
    }

    protected function outputFrame(
        string $frame,
        bool $pad = false
    ): void {
        if ($pad) {
            $target = 4096;

            $padding = max(
                0,
                $target -
                strlen($frame)
            );

            if ($padding > 0) {
                $frame .=
                    ': ' .
                    str_repeat(
                        ' ',
                        $padding
                    ) .
                    "\n\n";
            }
        }

        echo $frame;

        $this->flushOutput();
    }

    protected function buildMessages(
        array $history,
        string $message,
        array $context
    ): array {
        $messages = [
            [
                'role' => 'system',
                'content' =>
                    implode(
                        "\n",
                        $this->businessRules()
                    ),
            ],
            [
                'role' => 'system',
                'content' =>
                    "Current business data:\n\n" .
                    json_encode(
                        $context,
                        JSON_PRETTY_PRINT |
                        JSON_UNESCAPED_UNICODE |
                        JSON_UNESCAPED_SLASHES
                    ),
            ],
        ];

        foreach (
            array_slice(
                $history,
                -20
            ) as $item
        ) {
            $messages[] = [
                'role' => $item['role'],
                'content' => $item['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    protected function businessRules(): array
    {
        return [
            'You are Gab AI, the intelligent business assistant for this inventory and point-of-sale management system.',
            'You are also known as ERIAO AI.',
            'Use the supplied business data as your source of truth.',
            'Never invent inventory quantities, products, prices, sales, transactions, or financial figures.',
            'If information is unavailable, say that it is unavailable.',
            'Use Philippine Peso (₱) for monetary values.',
            'Completed and paid transactions are valid sales.',
            'Pending and void transactions are not completed sales.',
            'Use actual supplied data for calculations.',
            'Use actual supplied data for recommendations.',
            'Answer clearly and directly.',
            'Return responses as plain text or simple HTML.',
            'Do not use Markdown code fences.',
            'Do not use html or body tags.',
            'Do not use script, style, iframe, object, embed, form, input, button, svg, or event-handler attributes.',
            'Allowed HTML tags are p, strong, em, ul, ol, li, br, code, pre, h3, h4, table, thead, tbody, tr, th, and td.',
            'Do not entertain non-related sales and inventory or out of scope inquiries',
        ];
    }

    protected function context(): array
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
            ->get()
            ->map(
                fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' =>
                        $product->category_name ??
                        'Uncategorized',
                    'current_stock' =>
                        (int) $product->quantity,
                    'maximum_stock' =>
                        (int) $product->max,
                    'price' =>
                        (float) $product->price,
                    'status' =>
                        $product->status,
                ]
            )
            ->values()
            ->all();

        $inventoryValue =
            DB::table('product_items')
                ->selectRaw(
                    'COALESCE(SUM(quantity * price), 0) as value'
                )
                ->value('value');

        $totalStock =
            DB::table('product_items')
                ->sum('quantity');

        $restockNeeded =
            collect($products)
                ->filter(
                    function ($product) {
                        if (
                            $product['maximum_stock'] <= 0
                        ) {
                            return false;
                        }

                        $percentage =
                            (
                                $product['current_stock'] /
                                $product['maximum_stock']
                            ) *
                            100;

                        return $percentage <= 40;
                    }
                )
                ->count();

        $transactionSummary =
            DB::table('transactions')
                ->selectRaw(
                    "COUNT(*) as total_transactions,
                    SUM(
                        CASE
                            WHEN status IN ('completed', 'paid')
                            THEN 1
                            ELSE 0
                        END
                    ) as successful_transactions,
                    SUM(
                        CASE
                            WHEN status = 'pending'
                            THEN 1
                            ELSE 0
                        END
                    ) as pending_transactions,
                    SUM(
                        CASE
                            WHEN status = 'void'
                            THEN 1
                            ELSE 0
                        END
                    ) as void_transactions"
                )
                ->first();

        $monthlySales = [];

        for (
            $i = 5;
            $i >= 0;
            $i--
        ) {
            $date =
                now()->subMonths($i);

            $units =
                DB::table('orders')
                    ->join(
                        'transactions',
                        'transactions.id',
                        '=',
                        'orders.transaction_id'
                    )
                    ->whereIn(
                        'transactions.status',
                        [
                            'completed',
                            'paid',
                        ]
                    )
                    ->whereBetween(
                        'orders.created_at',
                        [
                            $date
                                ->copy()
                                ->startOfMonth(),
                            $date
                                ->copy()
                                ->endOfMonth(),
                        ]
                    )
                    ->count();

            $monthlySales[] = [
                'month' =>
                    $date->format('M Y'),
                'units_sold' =>
                    (int) $units,
            ];
        }

        return [
            'inventory' => [
                'total_products' =>
                    count($products),
                'total_stock' =>
                    (int) $totalStock,
                'inventory_value' =>
                    (float) $inventoryValue,
                'restock_needed' =>
                    $restockNeeded,
                'products' =>
                    $products,
            ],
            'sales' =>
                $monthlySales,
            'transactions' => [
                'total_transactions' =>
                    (int) (
                        $transactionSummary
                            ->total_transactions ??
                        0
                    ),
                'successful_transactions' =>
                    (int) (
                        $transactionSummary
                            ->successful_transactions ??
                        0
                    ),
                'pending_transactions' =>
                    (int) (
                        $transactionSummary
                            ->pending_transactions ??
                        0
                    ),
                'void_transactions' =>
                    (int) (
                        $transactionSummary
                            ->void_transactions ??
                        0
                    ),
            ],
        ];
    }
}