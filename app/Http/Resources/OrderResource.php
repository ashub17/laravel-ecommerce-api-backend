<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'order_number' => $this->order_number,
            'subtotal' => $this->subtotal,
            'tax' => $this->tax,
            'shipping_fee' => $this->shipping_fee,
            'total' => $this->total,
            'currency' => config('commerce.currency'),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'is_cancellable' => $this->isCancellable(),
            'shipping_address_id' => $this->shipping_address_id,
            'billing_address_id' => $this->billing_address_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_histories' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'shipping_address' => new AddressResource($this->whenLoaded('shippingAddress')),
            'billing_address' => new AddressResource($this->whenLoaded('billingAddress')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
