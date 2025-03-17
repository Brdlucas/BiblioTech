<?php

namespace App\Controller;

use App\Form\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;

class BookController extends AbstractController
{
    private $client;
    private $logger;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    #[Route('/books', name: 'app_books', methods: ['GET', 'POST'])] public function index(Request $request)
    {

        $value = $request->query->get('value');

        // Récupération de la clé API dans la méthode, pas dans le constructeur
        $googleBooksApiKey = $this->getParameter('google_books_api_key');

        // Valeurs par défaut pour les filtres
        $defaultFilters = [
            'title' => $value ?? '',
            'author' => null,
            'publication_date' => null
        ];

        // Création et gestion du formulaire de recherche
        $form = $this->createForm(SearchType::class, $defaultFilters);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on récupère les filtres
        $filters = [
            'title' => $form->get('title')->getData(),
            'author' => $form->get('author')->getData(),
            'publication_date' => $form->get('publication_date')->getData(),
        ];


        if ($request->headers->get('referer') != "https://127.0.0.1:8000/books") {
            if (!empty($value)) {
                $googleBooksResults = $this->searchGlobalGoogleBooks($value, $googleBooksApiKey);
            } else {
                $googleBooksResults = $this->searchGlobalGoogleBooks("Harry Potter", $googleBooksApiKey);
            }
        } else {

            if (!$form->isSubmitted() || !$form->isValid()) {

                $googleBooksResults = $this->searchGoogleBooks($defaultFilters, $googleBooksApiKey);
            } else {

                $googleBooksResults = $this->searchGoogleBooks($filters, $googleBooksApiKey);
            }
        }
        return $this->render('books/index.html.twig', [
            'form' => $form->createView(),
            'results' => $googleBooksResults ?? [],
        ]);
    }


    private function searchGoogleBooks(array $filters, string $googleBooksApiKey)
    {
        $query = [];

        // Si l'utilisateur a renseigné un titre, on l'ajoute à la requête
        if ($filters['title']) {
            $query[] = 'intitle:' . urlencode($filters['title']);
        }

        // Si l'utilisateur a renseigné un auteur, on l'ajoute à la requête
        if ($filters['author']) {
            $query[] = 'inauthor:' . urlencode($filters['author']);
        }

        // Si l'utilisateur a renseigné une date de publication, on l'ajoute à la requête
        if ($filters['publication_date']) {
            $query[] = 'publishedDate:' . $filters['publication_date']->format('Y');
        }

        if (empty($query)) {
            return [];
        }

        // Construire l'URL de la requête
        $queryStr = implode('+', $query);
        $url = 'https://www.googleapis.com/books/v1/volumes?q=' . $queryStr . '&maxResults=3&key=' . $googleBooksApiKey;

        try {
            $response = $this->client->request('GET', $url);
            $data = $response->toArray();

            // Renvoi des résultats obtenus de l'API
            return $data['items'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Google Books API error: ' . $e->getMessage());
            return [];
        }
    }

    private function searchGlobalGoogleBooks(string $query, string $googleBooksApiKey)
    {
        if (empty($query)) {
            return [];
        }

        // Encoder la chaîne de recherche
        $queryStr = urlencode($query);

        // Construire l'URL de la requête vers l'API Google Books
        $url = 'https://www.googleapis.com/books/v1/volumes?q=' . $queryStr . '&maxResults=3&key=' . $googleBooksApiKey;

        try {
            // Effectuer la requête HTTP
            $response = $this->client->request('GET', $url);
            $data = $response->toArray();

            return $data['items'] ?? [];
        } catch (\Exception $e) {
            $this->logger->error('Google Books API error: ' . $e->getMessage());
            return [];
        }
    }


    private function getBookDetail(string $id, string $googleBooksApiKey)
    {
        $url = "https://www.googleapis.com/books/v1/volumes/" . urlencode($id) . "?key=" . $googleBooksApiKey;
        try {
            $response = $this->client->request('GET', $url);
            $data = $response->toArray();
            // Renvoi des résultats obtenus de l'API
            return $data ?? [];
        } catch (\Exception $e) {
            // Log l'erreur en cas d'échec de la requête
            $this->logger->error('Google Books API error: ' . $e->getMessage());
            return [];
        }
    }


    #[Route('/book/{id}', name: 'app_book_id', methods: ['GET'])]
    public function book(Request $request, string $id): Response
    {
        $googleBooksApiKey = $this->getParameter('google_books_api_key');

        $googleBookIdResult = $this->getBookDetail($id, $googleBooksApiKey);

        return $this->render('books/book.html.twig', [
            'book' => $googleBookIdResult ?? [], // Résultats de l'API
        ]);
    }
}
