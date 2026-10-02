<?php

namespace App\Controller\Admin;

use App\Entity\Popin;
use App\Form\PopinType;
use Aropixel\AdminBundle\Component\DataTable\DataTableFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/popin', name: 'admin_popin_')]
class PopinController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(DataTableFactory $dataTableFactory): Response
    {
        return $dataTableFactory
            ->create(Popin::class)
            ->setColumns([
                ['label' => 'Titre', 'orderBy' => 'title'],
                ['label' => 'Affichage', 'orderBy' => 'displayAll', 'style' => 'width:160px;'],
                ['label' => 'Période', 'orderBy' => 'publishAt', 'style' => 'width:260px;'],
                ['label' => 'Statut', 'orderBy' => 'status', 'style' => 'width:110px;'],
                ['label' => '', 'orderBy' => '', 'class' => 'no-sort'],
            ])
            ->searchIn(['title'])
            ->setOrderColumn(0)
            ->setOrderDirection('asc')
            ->renderJson(fn (Popin $popin) => [
                $this->renderView('admin/popin/_link.html.twig', ['item' => $popin]),
                $popin->isDisplayAll() ? 'Tout le site' : \count($popin->getUrls()) . ' page(s)',
                $this->renderView('admin/popin/_period.html.twig', ['item' => $popin]),
                $this->renderView('admin/popin/_status.html.twig', ['item' => $popin]),
                $this->renderView('admin/popin/_actions.html.twig', ['item' => $popin]),
            ])
            ->render('admin/popin/index.html.twig')
        ;
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $popin = new Popin();
        $form = $this->createForm(PopinType::class, $popin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($popin);
            $this->em->flush();

            $this->addFlash('notice', 'La popin a bien été enregistrée.');

            return $this->redirectToRoute('admin_popin_edit', ['id' => $popin->getId()]);
        }

        return $this->render('admin/popin/form.html.twig', [
            'popin' => $popin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Popin $popin): Response
    {
        $form = $this->createForm(PopinType::class, $popin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('notice', 'La popin a bien été enregistrée.');

            return $this->redirectToRoute('admin_popin_edit', ['id' => $popin->getId()]);
        }

        return $this->render('admin/popin/form.html.twig', [
            'popin' => $popin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, Popin $popin): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete__popin' . $popin->getId(), \is_string($token) ? $token : null)) {
            $this->em->remove($popin);
            $this->em->flush();

            $this->addFlash('notice', 'La popin a bien été supprimée.');
        }

        return $this->redirectToRoute('admin_popin_index');
    }
}
