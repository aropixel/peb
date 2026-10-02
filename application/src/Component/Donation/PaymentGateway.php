<?php

namespace App\Component\Donation;

use App\Entity\Donation;

/**
 * Ce que l'application attend du prestataire de paiement (Stripe en production).
 */
interface PaymentGateway
{
    /**
     * Ouvre une session de paiement hébergée vers laquelle rediriger le donateur.
     */
    public function createCheckoutSession(Donation $donation): CheckoutSession;

    /**
     * Moyen de paiement réellement utilisé (card, paypal…), null s'il est inconnu.
     */
    public function findPaymentMethod(string $paymentIntentId): ?string;
}
