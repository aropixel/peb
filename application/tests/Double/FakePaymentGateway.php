<?php

namespace App\Tests\Double;

use App\Component\Donation\CheckoutSession;
use App\Component\Donation\PaymentGateway;
use App\Entity\Donation;

class FakePaymentGateway implements PaymentGateway
{
    public int $sessionsCreated = 0;

    public function createCheckoutSession(Donation $donation): CheckoutSession
    {
        ++$this->sessionsCreated;

        return new CheckoutSession('cs_test_' . $donation->getReference(), 'https://checkout.stripe.test/' . $donation->getReference());
    }

    public function findPaymentMethod(string $paymentIntentId): ?string
    {
        return 'paypal';
    }
}
