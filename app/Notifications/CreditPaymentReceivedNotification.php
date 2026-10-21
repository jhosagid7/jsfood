<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\CustomerTransaction;

class CreditPaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CustomerTransaction $transaction,
        public float $newBalance
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amount = number_format($this->transaction->amount_usd, 2);
        $balance = number_format($this->newBalance, 2);

        return (new MailMessage)
            ->subject('✅ Comprobante de Abono Recibido - JSFood Restaurant')
            ->greeting("Estimado(a) {$notifiable->name},")
            ->line("Hemos recibido satisfactoriamente su abono a su cuenta corriente:")
            ->line("💵 **Monto Abonado:** \${$amount} USD")
            ->line("📄 **Referencia:** " . ($this->transaction->reference ?? 'Pago en Caja'))
            ->line("💳 **Saldo Restante:** \${$balance} USD")
            ->line("Agradecemos su puntualidad y preferencia.")
            ->salutation('Atentamente, el equipo de JSFood Restaurant.');
    }
}
