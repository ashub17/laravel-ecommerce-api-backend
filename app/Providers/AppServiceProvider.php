<?php

namespace App\Providers;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Gateways\MockGateway;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Gateway implementations, keyed by the name used in
     * config('commerce.payments.default'). A real provider is added here and
     * nowhere else.
     *
     * @var array<string, class-string<PaymentGateway>>
     */
    protected array $paymentGateways = [
        'mock' => MockGateway::class,
    ];

    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $name = (string) config('commerce.payments.default', 'mock');

            if (!isset($this->paymentGateways[$name])) {
                throw new InvalidArgumentException("Unknown payment gateway [{$name}].");
            }

            return $this->app->make($this->paymentGateways[$name]);
        });
    }

    public function boot(): void
    {
        // The reset journey finishes in the SPA, so the emailed link points
        // there rather than at a Blade route this API does not serve. The
        // email is included so the reset form can submit it back unchanged.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $query = http_build_query([
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return rtrim(config('app.frontend_url'), '/') . '/reset-password?' . $query;
        });
    }
}
