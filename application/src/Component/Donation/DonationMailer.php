<?php

namespace App\Component\Donation;

use App\Entity\Donation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class DonationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire(env: 'DONATION_EMAIL_FROM')]
        private readonly string $from,
    ) {
    }

    public function sendThanks(Donation $donation): void
    {
        $this->mailer->send((new TemplatedEmail())
            ->from(new Address($this->from, 'Pierre-Emmanuel Barré'))
            ->to(new Address($donation->getEmail(), $donation->getFullName()))
            ->subject('Merci pour votre don')
            ->htmlTemplate('emails/donation_thanks.html.twig')
            ->context(['donation' => $donation])
        );
    }
}
