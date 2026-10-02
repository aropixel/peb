<?php

namespace App\Controller;

use App\Component\Donation\StripeWebhookHandler;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController
{
    public function __construct(
        private readonly StripeWebhookHandler $handler,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'STRIPE_WEBHOOK_SECRET')]
        private readonly string $webhookSecret,
    ) {
    }

    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->headers->get('Stripe-Signature'),
                $this->webhookSecret,
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            $this->logger->warning('Webhook Stripe refusé', ['exception' => $e]);

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }

        $this->handler->handle($event);

        return new Response('OK');
    }
}
