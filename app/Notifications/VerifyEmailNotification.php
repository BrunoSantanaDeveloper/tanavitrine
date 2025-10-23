<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification
{
    use Queueable;

    /**
     * The callback that should be used to create the verify email URL.
     *
     * @var \Closure|null
     */
    public static $createUrlCallback;

    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $verificationUrl);
        }

        return $this->buildMailMessage($verificationUrl);
    }

    /**
     * Get the verify email notification mail message for the given URL.
     */
    protected function buildMailMessage(string $url): MailMessage
    {
        $actionText = __('auth.verification.email.action');
        
        return (new MailMessage)
            ->theme('vetfun')
            ->subject(__('auth.verification.email.subject'))
            ->greeting(__('auth.verification.email.greeting'))
            ->line(__('auth.verification.email.line1'))
            ->action($actionText, $url)
            ->line(__('auth.verification.email.line2'))
            ->salutation(__('auth.verification.email.salutation') . ' ' . __('auth.verification.email.team', ['app' => config('app.name')]))
            ->from(config('mail.from.address'), config('app.name'));
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl(object $notifiable): string
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    /**
     * Set a callback that should be used when creating the email verification URL.
     */
    public static function createUrlUsing(callable $callback): void
    {
        static::$createUrlCallback = $callback;
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     */
    public static function toMailUsing(callable $callback): void
    {
        static::$toMailCallback = $callback;
    }
}
