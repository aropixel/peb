<?php

namespace App\Component\Donation;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\StripeObject;

/**
 * Applique les événements Stripe aux dons. Stripe pouvant renvoyer un même événement
 * plusieurs fois, chaque transition ne s'applique qu'une fois.
 */
class StripeWebhookHandler
{
    public function __construct(
        private readonly DonationRepository $donationRepository,
        private readonly PaymentGateway $paymentGateway,
        private readonly DonationMailer $mailer,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function handle(Event $event): void
    {
        /** @var StripeObject $object */
        $object = $event->data->object;

        match ($event->type) {
            'checkout.session.completed' => 'paid' === $object->payment_status ? $this->onPaid($object) : null,
            'checkout.session.async_payment_succeeded' => $this->onPaid($object),
            'checkout.session.async_payment_failed' => $this->onSessionClosed($object, failed: true),
            'checkout.session.expired' => $this->onSessionClosed($object, failed: false),
            'charge.refunded' => $this->onRefunded($object),
            default => null,
        };
    }

    private function onPaid(StripeObject $session): void
    {
        $donation = $this->findBySession($session);
        if (null === $donation || DonationStatus::Pending !== $donation->getStatus()) {
            return;
        }

        $paymentIntentId = \is_string($session->payment_intent) ? $session->payment_intent : null;
        $donation->markPaid(
            $paymentIntentId,
            null !== $paymentIntentId ? $this->paymentGateway->findPaymentMethod($paymentIntentId) : null,
        );
        $this->em->flush();

        $this->mailer->sendThanks($donation);
    }

    private function onSessionClosed(StripeObject $session, bool $failed): void
    {
        $donation = $this->findBySession($session);
        if (null === $donation || DonationStatus::Pending !== $donation->getStatus()) {
            return;
        }

        $failed ? $donation->markFailed() : $donation->markExpired();
        $this->em->flush();
    }

    private function onRefunded(StripeObject $charge): void
    {
        if (!\is_string($charge->payment_intent)) {
            return;
        }

        $donation = $this->donationRepository->findOneBy(['stripePaymentIntentId' => $charge->payment_intent]);
        if (null === $donation || $donation->getAmountRefunded() === $charge->amount_refunded) {
            return;
        }

        $donation->registerRefund($charge->amount_refunded);
        $this->em->flush();
    }

    private function findBySession(StripeObject $session): ?Donation
    {
        $donation = $this->donationRepository->findOneBy(['stripeCheckoutSessionId' => $session->id]);

        if (null === $donation) {
            $this->logger->warning('Session Stripe sans don correspondant', ['session' => $session->id]);
        }

        return $donation;
    }
}
