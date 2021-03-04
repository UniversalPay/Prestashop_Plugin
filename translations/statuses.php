<?php

namespace StatusTranslation;

class Statuses
{
    const EVO_STATUS_SUCCESS = [
        'en' => 'Payment Success',
        'gb' => 'Payment Success',
        'pl' => 'Płatność zrealizowana',
		'es' => 'Pagado con éxito',
		'es-mx' => 'Pagado con éxito',
        'de' => 'Erfolgreiche Zahlung',
        'cs' => 'Platba úspěšná',
        'it' => 'Pagamento eseguito',
        'fr' => 'Votre paiement a été effectué avec succès',
        'nl' => 'Betaling gelukt',
        'hu' => 'Sikeres fizetés',
        'default' => 'Payment Success'
    ];

    const EVO_STATUS_ERROR = [
        'en' => 'Payment Error',
        'gb' => 'Payment Error',
        'pl' => 'Błąd płatności',
		'es' => 'Error en el pago',
		'es-mx' => 'Error en el pago',
        'de' => 'Zahlungsfehler',
        'cs' => 'Chybná platba',
        'it' => 'Errore nel pagamento',
        'fr' => 'Erreur de paiement',
        'nl' => 'Betalingsfout',
        'hu' => 'Hiba a fizetés során',
        'default' => 'Payment Error'
    ];

    const EVO_STATUS_CANCELED = [
        'en' => 'Payment Canceled',
        'gb' => 'Payment Canceled',
        'pl' => 'Płatność anulowana',
		'es' => 'Pago cancelado',
		'es-mx' => 'Pago cancelado',
        'de' => 'Zahlung abgebrochen',
        'cs' => 'Platba zrušena',
        'it' => 'Pagamento annullato',
        'fr' => 'Paiement annulé',
        'nl' => 'Betaling geannuleerd',
        'hu' => 'Fizetés visszavonva',
        'default' => 'Payment Canceled'
    ];

    const EVO_STATUS_INPROGRESS = [
        'en' => 'Payment in Progress',
        'gb' => 'Payment in Progress',
        'pl' => 'Płatność w toku',
		'es' => 'Pago en curso',
		'es-mx' => 'Pago en curso',
        'de' => 'Zahlung in Bearbeitung',
        'cs' => 'Platba probíhá',
        'it' => 'Pagamento in corso',
        'fr' => 'Traitement du paiement en cours',
        'nl' => 'Betaling wordt uitgevoerd',
        'hu' => 'Fizetés folyamatban',
        'default' => 'Payment in Progress'
    ];

    const EVO_STATUS_REFUNDED = [
        'en' => 'Payment Refunded',
        'gb' => 'Payment Refunded',
        'pl' => 'Zwrot płatności',
		'es' => 'Reembolso del pago',
		'es-mx' => 'Pago reembolsado',
        'de' => 'Rückzahlung',
        'cs' => 'Platba vrácena',
        'it' => 'Pagamento rimborsato',
        'fr' => 'Votre paiement a été remboursé',
        'nl' => 'Betaling gerestitueerd',
        'hu' => 'Fizetés visszatérítve',
        'default' => 'Payment Refunded'
    ];
}