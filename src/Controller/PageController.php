<?php

namespace App\Controller;

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

        $borrowings = $entityManager->getRepository(Borrowing::class)->findBy(['userbook' => $user->getId(), 'is_verified' => true]);

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
}