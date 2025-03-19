<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Borrowing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class PageController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('page/index.html.twig', [
            'controller_name' => 'PageController',
        ]);
    }

    #[Route('/borrowing/history', name: 'app_borrowing_history')]
    public function borrowing(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_homepage');
        }

        $borrowings = $entityManager->getRepository(Borrowing::class)->findBy(['userbook' => $user->getId()]);

        return $this->render('page/borrowing.html.twig', [
            'controller_name' => 'PageController',
            'borrowings' => $borrowings,
        ]);
    }
    #[Route('/borrowing/history/delete/{id}', name: 'app_delete_borrowing_id')]
    public function deleteBorrowing(EntityManagerInterface $entityManager, string $id)
    {
        $user = $this->getUser();

        $borrowing = $entityManager->getRepository(Borrowing::class)->find($id);

        if ($borrowing) {
            $entityManager->remove($borrowing);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_borrowing_history');
    }



    #[Route('/borrowing/{id}/pdf/view', name: 'borrowing_pdf_view')]
    public function viewPdf(EntityManagerInterface $entityManager, int $id): Response
    {
        // Récupérer l'emprunt en base de données
        $borrowing = $entityManager->getRepository(Borrowing::class)->find($id);

        if (!$borrowing) {
            throw $this->createNotFoundException('Emprunt non trouvé.');
        }

        // Options DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);

        // Initialisation de DomPDF
        $dompdf = new Dompdf($options);

        // Générer le HTML avec Twig
        $html = $this->renderView('page/pdf-generator.html.twig', [
            'borrowing' => $borrowing,
        ]);

        // Charger le HTML
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Obtenir le contenu du PDF en sortie
        $pdfOutput = $dompdf->output();

        // Afficher le PDF dans une nouvelle page (sans téléchargement)
        return new Response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}