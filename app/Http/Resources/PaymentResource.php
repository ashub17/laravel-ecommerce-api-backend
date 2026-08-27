<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Payment
 */
class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'gateway' => $this->gateway,
            'reference' => $this->gateway_reference,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'captured_at' => $this->captured_at,
            'created_at' => $this->created_at,
        ];
    }
}
