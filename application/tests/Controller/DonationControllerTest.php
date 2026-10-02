<?php

namespace App\Tests\Controller;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DonationControllerTest extends WebTestCase
{
    private const WEBHOOK_SECRET = 'whsec_test';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em->createQuery('DELETE FROM ' . Donation::class)->execute();
    }

    public function testDonationRedirectsToStripeCheckout(): void
    {
        $crawler = $this->client->request('GET', '/dons');
        self::assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[name="donation"]')->form([
            'donation[amount]' => 'other',
            'donation[customAmount]' => '15',
            'donation[firstName]' => 'Jeanne',
            'donation[lastName]' => 'Dupont',
            'donation[email]' => 'jeanne@example.com',
            'donation[consent]' => '1',
        ]));

        self::assertResponseStatusCodeSame(303);
        $donation = $this->em->getRepository(Donation::class)->findOneBy(['email' => 'jeanne@example.com']);
        self::assertNotNull($donation);
        self::assertSame(1500, $donation->getAmount());
        self::assertSame(DonationStatus::Pending, $donation->getStatus());
        self::assertResponseRedirects('https://checkout.stripe.test/' . $donation->getReference());
        self::assertSame('cs_test_' . $donation->getReference(), $donation->getStripeCheckoutSessionId());
    }

    public function testInvalidDonationIsRejected(): void
    {
        $crawler = $this->client->request('GET', '/dons');

        $this->client->submit($crawler->filter('form[name="donation"]')->form([
            'donation[amount]' => 'other',
            'donation[customAmount]' => '0',
            'donation[firstName]' => 'Jeanne',
            'donation[lastName]' => 'Dupont',
            'donation[email]' => 'pas-un-email',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em->getRepository(Donation::class)->count([]));
    }

    public function testWebhookWithInvalidSignatureIsRejected(): void
    {
        $this->client->request('POST', '/stripe/webhook', server: ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=nope'], content: '{}');

        self::assertResponseStatusCodeSame(400);
    }

    public function testCompletedCheckoutMarksDonationPaidOnce(): void
    {
        $donation = $this->createPendingDonation();
        $event = $this->event('checkout.session.completed', [
            'object' => 'checkout.session',
            'id' => $donation->getStripeCheckoutSessionId(),
            'payment_status' => 'paid',
            'payment_intent' => 'pi_123',
        ]);

        $this->sendWebhook($event);
        self::assertResponseIsSuccessful();
        self::assertQueuedEmailCount(1);
        $email = self::getMailerMessage();
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'To', 'jeanne@example.com');
        self::assertEmailHtmlBodyContains($email, $donation->getReference());

        // Stripe peut renvoyer le même événement : pas de second email.
        $this->sendWebhook($event);
        self::assertQueuedEmailCount(0);

        $this->em->clear();
        $donation = $this->em->getRepository(Donation::class)->find($donation->getId());
        self::assertNotNull($donation);
        self::assertSame(DonationStatus::Paid, $donation->getStatus());
        self::assertSame('pi_123', $donation->getStripePaymentIntentId());
        self::assertSame('paypal', $donation->getPaymentMethod());

        $this->client->request('GET', '/dons/merci', ['session_id' => $donation->getStripeCheckoutSessionId()]);
        self::assertSelectorTextContains('.main-content', 'a bien été reçu');
    }

    public function testRefundsAndExpiration(): void
    {
        $donation = $this->createPendingDonation();
        $expired = $this->createPendingDonation();

        $this->sendWebhook($this->event('checkout.session.completed', [
            'object' => 'checkout.session',
            'id' => $donation->getStripeCheckoutSessionId(),
            'payment_status' => 'paid',
            'payment_intent' => 'pi_456',
        ]));
        $this->sendWebhook($this->event('charge.refunded', [
            'object' => 'charge',
            'id' => 'ch_1',
            'payment_intent' => 'pi_456',
            'amount_refunded' => 500,
            'refunded' => false,
        ]));

        $this->em->clear();
        $donation = $this->em->getRepository(Donation::class)->find($donation->getId());
        self::assertNotNull($donation);
        self::assertSame(DonationStatus::Paid, $donation->getStatus(), 'Un remboursement partiel laisse le don payé.');
        self::assertSame(500, $donation->getAmountRefunded());

        $this->sendWebhook($this->event('charge.refunded', [
            'object' => 'charge',
            'id' => 'ch_1',
            'payment_intent' => 'pi_456',
            'amount_refunded' => 2000,
            'refunded' => true,
        ]));
        $this->sendWebhook($this->event('checkout.session.expired', [
            'object' => 'checkout.session',
            'id' => $expired->getStripeCheckoutSessionId(),
            'payment_status' => 'unpaid',
        ]));

        $this->em->clear();
        self::assertSame(DonationStatus::Refunded, $this->em->getRepository(Donation::class)->find($donation->getId())?->getStatus());
        self::assertSame(DonationStatus::Expired, $this->em->getRepository(Donation::class)->find($expired->getId())?->getStatus());
    }

    private function createPendingDonation(): Donation
    {
        $donation = new Donation(2000, 'Jeanne', 'Dupont', 'jeanne@example.com');
        $this->em->persist($donation);
        $this->em->flush();
        $donation->setStripeCheckoutSessionId('cs_test_' . $donation->getReference());
        $this->em->flush();

        return $donation;
    }

    /**
     * @param array<string, mixed> $object
     */
    private function event(string $type, array $object): string
    {
        return (string) json_encode([
            'id' => 'evt_' . bin2hex(random_bytes(4)),
            'object' => 'event',
            'type' => $type,
            'data' => ['object' => $object],
        ]);
    }

    /**
     * Signe le payload comme Stripe : HMAC SHA-256 de « timestamp.payload ».
     */
    private function sendWebhook(string $payload): void
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, self::WEBHOOK_SECRET);

        $this->client->request('POST', '/stripe/webhook', server: [
            'HTTP_STRIPE_SIGNATURE' => \sprintf('t=%d,v1=%s', $timestamp, $signature),
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);
    }
}
