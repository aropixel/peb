<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Donation;
use Aropixel\AdminBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DonationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->em->createQuery('DELETE FROM ' . Donation::class)->execute();
        $this->em->createQuery('DELETE FROM ' . User::class)->execute();

        $admin = new User();
        $admin->setEmail('admin@peb.test');
        $admin->setPassword('unused');
        $admin->setEnabled(true);
        $admin->setInitialized(true);
        $admin->setLastPasswordUpdate(new \DateTime());
        $this->em->persist($admin);
        $this->em->flush();

        // Le firewall « backoffice » partage le contexte de session « primary_auth ».
        $this->client->loginUser($admin, 'primary_auth');
    }

    public function testAdminCanBrowseAndExportDonations(): void
    {
        $donation = new Donation(5000, 'Jeanne', 'Dupont', 'jeanne@example.com', 'Bravo !');
        $donation->setStripeCheckoutSessionId('cs_test_admin');
        $donation->markPaid('pi_admin', 'card');
        $this->em->persist($donation);
        $this->em->persist(new Donation(1000, 'Paul', 'Martin', 'paul@example.com'));
        $this->em->flush();

        $this->client->request('GET', '/admin/don/');
        self::assertResponseIsSuccessful();

        $this->client->xmlHttpRequest('GET', '/admin/don/', ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'dupont']]);
        self::assertResponseIsSuccessful();
        $rows = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString($donation->getReference(), $rows);
        self::assertStringNotContainsString('paul@example.com', $rows);

        $this->client->request('GET', '/admin/don/' . $donation->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Bravo !');
        self::assertSelectorExists('a[href="https://dashboard.stripe.com/payments/pi_admin"]');

        $this->client->request('GET', '/admin/don/export', ['status' => 'paid']);
        $csv = $this->client->getInternalResponse()->getContent();
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString($donation->getReference(), $csv);
        self::assertStringContainsString('50,00', $csv);
        self::assertStringNotContainsString('paul@example.com', $csv);
    }
}
