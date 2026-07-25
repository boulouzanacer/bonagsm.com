<?php

namespace App\Notifications;

use App\Models\Cmd1;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StatutCommandeChange extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Cmd1 $commande,
        public string $nouveauStatut
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $label = match ($this->nouveauStatut) {
            'en_attente' => 'En attente',
            'validee', 'confirmee' => 'Validee',
            'expediee' => 'Expediee',
            'livree' => 'Livree',
            'annulee' => 'Annulee',
            default => $this->nouveauStatut,
        };

        return [
            'type' => 'statut_commande_change',
            'commande_id' => $this->commande->id,
            'nouveau_statut' => $this->nouveauStatut,
            'montant_total' => (float) $this->commande->montant_total,
            'date_cmd' => (string) $this->commande->date_cmd,
            'message' => "Le statut de votre commande #{$this->commande->id} est maintenant: {$label}.",
        ];
    }
}
