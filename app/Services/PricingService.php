<?php

namespace App\Services;

/**
 * Turns an order subtotal into the full set of monetary figures an order
 * carries. Every rate lives in config/commerce.php so pricing can change
 * without a deploy, and nothing here assumes a particular currency.
 */
class PricingService
{
    /**
     * @return array{subtotal: float, tax: float, shipping_fee: float, total: float, currency: string}
     */
    public function calculate(float $subtotal): array
    {
        $subtotal = $this->round($subtotal);
        $tax = $this->taxFor($subtotal);
        $shippingFee = $this->shippingFor($subtotal);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_fee' => $shippingFee,
            'total' => $this->round($subtotal + $tax + $shippingFee),
            'currency' => $this->currency(),
        ];
    }

    /**
     * Tax applies to the subtotal only; shipping is not taxed.
     */
    public function taxFor(float $subtotal): float
    {
        $rate = (float) config('commerce.tax.rate', 0);

        if ($rate <= 0) {
            return 0.0;
        }

        return $this->round($subtotal * $rate);
    }

    /**
     * A flat fee per order, waived once the subtotal reaches the configured
     * free-shipping threshold.
     */
    public function shippingFor(float $subtotal): float
    {
        $flatFee = (float) config('commerce.shipping.flat_fee', 0);

        if ($flatFee <= 0) {
            return 0.0;
        }

        $freeOver = config('commerce.shipping.free_over');

        if ($freeOver !== null && $subtotal >= (float) $freeOver) {
            return 0.0;
        }

        return $this->round($flatFee);
    }

    public function currency(): string
    {
        return (string) config('commerce.currency', 'USD');
    }

    protected function round(float $value): float
    {
        return round($value, 2);
    }
}
