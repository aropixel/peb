<?php

namespace App\Controller;

use App\Component\Donation\PaymentGateway;
use App\Entity\Donation;
use App\Form\DonationType;
use App\Form\Model\DonationRequest;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ExceptionInterface as StripeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class DonationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PaymentGateway $paymentGateway,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/dons', name: 'donation', methods: ['GET', 'POST'])]
    public function index(Request $request, #[MapQueryParameter] bool $annule = false): Response
    {
        $donationRequest = new DonationRequest();
        $form = $this->createForm(DonationType::class, $donationRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $donation = new Donation(
                $donationRequest->getAmountInCents(),
                (string) $donationRequest->firstName,
                (string) $donationRequest->lastName,
                (string) $donationRequest->email,
                $donationRequest->message ?: null,
            );
            $this->em->persist($donation);
            $this->em->flush();

            try {
                $session = $this->paymentGateway->createCheckoutSession($donation);
            } catch (StripeException $e) {
                $this->logger->error('Création de la session Stripe impossible', ['donation' => $donation->getReference(), 'exception' => $e]);
                // Sans session Stripe, aucun webhook ne fera jamais évoluer ce don.
                $this->em->remove($donation);
                $this->em->flush();
                $this->addFlash('error', 'Le paiement est momentanément indisponible. Merci de réessayer dans quelques minutes.');

                return $this->redirectToRoute('donation');
            }

            $donation->setStripeCheckoutSessionId($session->id);
            $this->em->flush();

            return new RedirectResponse($session->url, Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/dons.html.twig', [
            'form' => $form,
            'cancelled' => $annule,
        ]);
    }

    #[Route('/dons/merci', name: 'donation_thanks', methods: ['GET'])]
    public function thanks(DonationRepository $donationRepository, #[MapQueryParameter('session_id')] ?string $sessionId = null): Response
    {
        $donation = null !== $sessionId ? $donationRepository->findOneBy(['stripeCheckoutSessionId' => $sessionId]) : null;

        if (null === $donation) {
            return $this->redirectToRoute('donation');
        }

        // Le webhook Stripe peut arriver quelques secondes après le retour du donateur :
        // la page s'affiche dans tous les cas, le statut payé n'y est qu'informatif.
        return $this->render('pages/dons-merci.html.twig', [
            'donation' => $donation,
        ]);
    }
}
