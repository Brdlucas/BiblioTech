<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookController extends AbstractController
{
    private $googleBooksService;

    public function __construct(GoogleBooksService $googleBooksService)
    {
        $this->googleBooksService = $googleBooksService;
    }

    #[Route(path: '/recherche', name: 'app_recherche_livres', methods: ['GET'])]
    public function rechercheLivres(Request $request): Response
    {
        $query = $request->query->get('q', 'Symfony'); // Récupère le paramètre 'q'
        $results = $this->googleBooksService->rechercherLivres($query);

        return $this->render('books/recherche.html.twig', [
            'results' => $results,
        ]);
    }
}
