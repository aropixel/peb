<?php

namespace App\Component\Donation;

use App\Entity\Donation;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsAlias(PaymentGateway::class)]
class StripeGateway implements PaymentGateway
{
    private ?StripeClient $client = null;

    public function __construct(
        #[Autowire(env: 'STRIPE_SECRET_KEY')]
        private readonly string $secretKey,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function createCheckoutSession(Donation $donation): CheckoutSession
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            // Les moyens de paiement (carte, PayPal…) se configurent dans le dashboard Stripe.
            'customer_email' => $donation->getEmail(),
            'client_reference_id' => $donation->getReference(),
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $donation->getCurrency(),
                    'unit_amount' => $donation->getAmount(),
                    'product_data' => ['name' => 'Don à Pierre-Emmanuel Barré'],
                ],
            ]],
            'metadata' => [
                'donation_id' => (string) $donation->getId(),
                'reference' => $donation->getReference(),
            ],
            'payment_intent_data' => [
                'description' => 'Don ' . $donation->getReference(),
                'metadata' => ['reference' => $donation->getReference()],
            ],
            'success_url' => $this->urlGenerator->generate('donation_thanks', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator->generate('donation', ['annule' => 1], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new CheckoutSession($session->id, (string) $session->url);
    }

    public function findPaymentMethod(string $paymentIntentId): ?string
    {
        try {
            $paymentIntent = $this->client()->paymentIntents->retrieve($paymentIntentId, ['expand' => ['latest_charge']]);
        } catch (ApiErrorException $e) {
            $this->logger->warning('Moyen de paiement Stripe introuvable', ['payment_intent' => $paymentIntentId, 'exception' => $e]);

            return null;
        }

        return $paymentIntent->latest_charge->payment_method_details->type ?? null;
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient($this->secretKey);
    }
}
