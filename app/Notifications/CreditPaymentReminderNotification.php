<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CreditPaymentReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public float $currentBalance,
        public ?string $dueDate = null,
        public bool $isOverdue = false
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $balance = number_format($this->currentBalance, 2);
        $subject = $this->isOverdue 
            ? '⚠️ Recordatorio: Cuenta Pendiente Vencida - JSFood' 
            : '🔔 Recordatorio de Pago: Crédito Próximo a Vencer - JSFood';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Estimado(a) {$notifiable->name},")
            ->line($this->isOverdue 
                ? "Le escribimos para recordarle que su crédito en JSFood Restaurant presenta saldo vencido pendiente de pago:"
                : "Le saludamos cordialmente para recordarle la fecha límite de su crédito en JSFood Restaurant:")
            ->line("💳 **Saldo Pendiente:** \${$balance} USD");

        if ($this->dueDate) {
            $mail->line("📅 **Fecha Límite / Vencimiento:** {$this->dueDate}");
        }

        return $mail
            ->line("Agradecemos coordinar su abono o pago en caja o mediante nuestros canales de pago electrónico.")
            ->line("¡Gracias por su confianza!")
            ->salutation('Atentamente, JSFood Restaurant.');
    }
}
