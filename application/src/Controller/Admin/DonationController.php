<?php

namespace App\Controller\Admin;

use App\Entity\Donation;
use App\Enum\DonationStatus;
use App\Repository\DonationRepository;
use Aropixel\AdminBundle\Component\DataTable\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Consultation des dons. Pas de création ni d'édition : un don n'évolue que par
 * les webhooks Stripe (paiement, remboursement fait depuis le dashboard Stripe).
 */
#[Route('/admin/don', name: 'admin_donation_')]
class DonationController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DataTableFactory $dataTableFactory): Response
    {
        return $dataTableFactory
            ->create(Donation::class)
            ->setColumns([
                ['label' => 'Date', 'orderBy' => 'createdAt', 'style' => 'width:140px;'],
                ['label' => 'Référence', 'orderBy' => 'reference', 'style' => 'width:120px;'],
                ['label' => 'Donateur', 'orderBy' => 'lastName'],
                ['label' => 'Email', 'orderBy' => 'email'],
                ['label' => 'Montant', 'orderBy' => 'amount', 'style' => 'width:110px;'],
                ['label' => 'Moyen', 'orderBy' => 'paymentMethod', 'style' => 'width:90px;'],
                ['label' => 'Statut', 'orderBy' => 'status', 'style' => 'width:110px;'],
                ['label' => '', 'orderBy' => '', 'class' => 'no-sort'],
            ])
            ->searchIn(['reference', 'firstName', 'lastName', 'email'])
            ->setOrderColumn(0)
            ->setOrderDirection('desc')
            ->renderJson(fn (Donation $donation) => [
                $donation->getCreatedAt()?->format('d/m/Y H:i'),
                $this->renderView('admin/donation/_link.html.twig', ['item' => $donation]),
                $donation->getFullName(),
                $donation->getEmail(),
                $this->renderView('admin/donation/_amount.html.twig', ['item' => $donation]),
                $donation->getPaymentMethod() ?? '—',
                $this->renderView('admin/donation/_status.html.twig', ['item' => $donation]),
                $this->renderView('admin/donation/_actions.html.twig', ['item' => $donation]),
            ])
            ->render('admin/donation/index.html.twig', [
                'statuses' => DonationStatus::cases(),
            ])
        ;
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(DonationRepository $donationRepository, #[MapQueryParameter] ?DonationStatus $status = null): StreamedResponse
    {
        $response = new StreamedResponse(static function () use ($donationRepository, $status): void {
            $out = fopen('php://output', 'w');
            if (false === $out) {
                return;
            }

            // BOM pour qu'Excel lise l'UTF-8, et « ; » comme séparateur (Excel FR).
            fwrite($out, "\u{FEFF}");
            fputcsv($out, ['Date', 'Référence', 'Prénom', 'Nom', 'Email', 'Montant (€)', 'Remboursé (€)', 'Moyen', 'Statut', 'Payé le', 'Message', 'Paiement Stripe'], ';', escape: '');

            foreach ($donationRepository->iterateForExport($status) as $donation) {
                fputcsv($out, [
                    $donation->getCreatedAt()?->format('d/m/Y H:i'),
                    $donation->getReference(),
                    $donation->getFirstName(),
                    $donation->getLastName(),
                    $donation->getEmail(),
                    number_format($donation->getAmount() / 100, 2, ',', ''),
                    number_format($donation->getAmountRefunded() / 100, 2, ',', ''),
                    $donation->getPaymentMethod(),
                    $donation->getStatus()->label(),
                    $donation->getPaidAt()?->format('d/m/Y H:i'),
                    $donation->getMessage(),
                    $donation->getStripePaymentIntentId(),
                ], ';', escape: '');
            }

            fclose($out);
        });

        $filename = \sprintf('dons-%s%s.csv', $status ? $status->value . '-' : '', date('Y-m-d'));
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Donation $donation): Response
    {
        return $this->render('admin/donation/show.html.twig', [
            'donation' => $donation,
        ]);
    }
}
