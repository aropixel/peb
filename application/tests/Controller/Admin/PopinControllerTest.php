<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Popin;
use Aropixel\AdminBundle\Entity\Publishable;
use Aropixel\AdminBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PopinControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $this->em->createQuery('DELETE FROM ' . Popin::class)->execute();
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

    public function testCreatePopinShownOnSelectedPagesOnly(): void
    {
        $this->client->request('GET', '/admin/popin/');
        self::assertResponseIsSuccessful();

        $crawler = $this->client->request('GET', '/admin/popin/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="popin"]')->form([
            'popin[title]' => 'Nouvelle date',
            'popin[type]' => 'content',
            'popin[content]' => '<p>Une date en plus à Paris</p>',
            'popin[link]' => 'https://pebarre.bleucitron.net/',
            'popin[urls]' => "/spectacles\nhttps://pebarre.com/nova*",
            'popin[status]' => Publishable::STATUS_ONLINE,
        ]);
        $form['popin[displayAll]']->untick();
        $this->client->submit($form);
        self::assertResponseRedirects();

        $popin = $this->em->getRepository(Popin::class)->findOneBy(['title' => 'Nouvelle date']);
        self::assertNotNull($popin);
        self::assertSame(['/spectacles', 'https://pebarre.com/nova*'], $popin->getUrls());
        self::assertFalse($popin->isForceDisplay());

        $this->client->request('GET', '/spectacles');
        self::assertSelectorExists('[data-controller="popin"][data-popin-cookie-name-value="' . $popin->getCookieName() . '"]');
        self::assertSelectorTextContains('#modal-default', 'Une date en plus à Paris');

        $this->client->request('GET', '/nova-2025-2026');
        self::assertSelectorExists('[data-controller="popin"]');

        $this->client->request('GET', '/contacts');
        self::assertSelectorNotExists('[data-controller="popin"]');
    }

    public function testPopinNeedsUrlsWhenNotDisplayedEverywhere(): void
    {
        $crawler = $this->client->request('GET', '/admin/popin/new');

        $form = $crawler->filter('form[name="popin"]')->form([
            'popin[title]' => 'Sans URL',
            'popin[type]' => 'content',
            'popin[content]' => '<p>Contenu</p>',
            'popin[urls]' => '',
        ]);
        $form['popin[displayAll]']->untick();
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->em->getRepository(Popin::class)->findOneBy(['title' => 'Sans URL']));
    }

    public function testOfflinePopinIsNotShownAndCanBeDeleted(): void
    {
        $popin = (new Popin())
            ->setTitle('Hors ligne')
            ->setContent('<p>Contenu</p>')
        ;
        $this->em->persist($popin);
        $this->em->flush();

        $this->client->request('GET', '/');
        self::assertSelectorNotExists('[data-controller="popin"]');

        $id = $popin->getId();
        // Les lignes de la liste sont chargées en AJAX par la DataTable.
        $this->client->xmlHttpRequest('GET', '/admin/popin/', ['draw' => 1, 'start' => 0, 'length' => 10]);
        self::assertResponseIsSuccessful();
        $rows = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Hors ligne', $rows);

        self::assertSame(1, preg_match('/name=\\\?"_token\\\?" value=\\\?"([^"\\\]+)/', $rows, $matches));
        $token = $matches[1];
        $this->client->request('POST', '/admin/popin/' . $id, ['_token' => $token, '_method' => 'DELETE']);
        self::assertResponseRedirects('/admin/popin/');

        $this->em->clear();
        self::assertNull($this->em->getRepository(Popin::class)->find($id));
    }
}
