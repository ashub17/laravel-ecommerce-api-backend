<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected string $field,
        protected ?string $from,
        protected string $to
    ) {
        // Dispatched inside the status-change transaction; hold until commit.
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
        $order = $this->order;
        $subject = $this->field === 'payment_status'
            ? "Payment {$this->to} for order {$order->order_number}"
            : "Order {$order->order_number} is now {$this->to}";

        $message = (new MailMessage())
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},");

        $message->line($this->field === 'payment_status'
            ? "The payment status for order {$order->order_number} changed to {$this->to}."
            : "Order {$order->order_number} has moved to {$this->to}.");

        return $message
            ->action('View your order', rtrim(config('app.frontend_url'), '/') . "/orders/{$order->id}")
            ->line('Thank you for shopping with us.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'field' => $this->field,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }
}
