<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Order $order
    ) {
        // Checkout dispatches this from inside a transaction. Holding the job
        // until commit means a rolled-back checkout can never send a
        // confirmation for an order that does not exist. The flag comes from
        // the Queueable trait, so it is set rather than redeclared.
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');
        $currency = $order->currency ?? config('commerce.currency');

        $message = (new MailMessage())
            ->subject("Order {$order->order_number} confirmed")
            ->greeting("Thanks for your order, {$notifiable->name}.")
            ->line("We have received order {$order->order_number} and will email you again when it ships.");

        foreach ($order->items as $item) {
            $message->line("{$item->quantity} x {$item->product_name} — " . $this->money($item->subtotal, $currency));
        }

        return $message
            ->line('Subtotal: ' . $this->money($order->subtotal, $currency))
            ->line('Shipping: ' . $this->money($order->shipping_fee, $currency))
            ->line('Tax: ' . $this->money($order->tax, $currency))
            ->line('Total: ' . $this->money($order->total, $currency))
            ->action('View your order', rtrim(config('app.frontend_url'), '/') . "/orders/{$order->id}")
            ->line('If anything looks wrong, reply to this email and we will sort it out.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }

    protected function money(float|string $amount, string $currency): string
    {
        return $currency . ' ' . number_format((float) $amount, 2);
    }
}
