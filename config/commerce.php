<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | The currency every price, order total and payment is denominated in.
    | Nothing in the codebase hardcodes a currency, so changing this plus the
    | admin panel's formatter is all that a switch requires.
    |
    */

    'currency' => env('COMMERCE_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Tax
    |--------------------------------------------------------------------------
    |
    | A single flat rate applied to the order subtotal. Shipping is not taxed.
    | Express the rate as a decimal fraction: 0.08 is 8%.
    |
    */

    'tax' => [
        'rate' => (float) env('COMMERCE_TAX_RATE', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    |
    | A flat fee per order, waived once the subtotal reaches the free-shipping
    | threshold. Set 'free_over' to null to charge the flat fee on every order.
    |
    */

    'shipping' => [
        'flat_fee' => (float) env('COMMERCE_SHIPPING_FLAT_FEE', 0),
        'free_over' => env('COMMERCE_SHIPPING_FREE_OVER') !== null
            ? (float) env('COMMERCE_SHIPPING_FREE_OVER')
            : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Order lifecycle
    |--------------------------------------------------------------------------
    |
    | 'cancellable_from' lists the order statuses a customer may cancel from.
    | 'restock_on' lists the transitions that return reserved stock to the
    | catalog; both are keyed by the field the transition applies to.
    |
    */

    'orders' => [
        'cancellable_from' => ['pending'],

        'restock_on' => [
            'status' => ['cancelled'],
            'payment_status' => ['refunded'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments
    |--------------------------------------------------------------------------
    |
    | 'default' names the gateway bound to the PaymentGateway contract. The
    | mock gateway implements the same interface a real one would — signed
    | webhooks included — so swapping in a live provider is a new class plus
    | a binding, with nothing else in the application changing.
    |
    */

    'payments' => [
        'default' => env('COMMERCE_PAYMENT_GATEWAY', 'mock'),

        'gateways' => [
            'mock' => [
                'webhook_secret' => env('MOCK_GATEWAY_WEBHOOK_SECRET', 'mock-webhook-secret'),

                // Set false to make every capture fail, for exercising the
                // unhappy path without touching code.
                'always_succeed' => filter_var(
                    env('MOCK_GATEWAY_ALWAYS_SUCCEED', true),
                    FILTER_VALIDATE_BOOLEAN
                ),
            ],
        ],
    ],

];
