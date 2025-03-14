<?php
// src/Controller/BookController.php
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

    // Injection des services nécessaires
    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    #[Route('/books', name: 'app_books', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Récupération de la clé API dans la méthode, pas dans le constructeur
        $googleBooksApiKey = $this->getParameter('google_books_api_key');

        // Valeurs par défaut pour les filtres
        $defaultFilters = [
            'title' => 'Harry Potter',  // Exemple de titre par défaut
            'author' => null,           // Pas de valeur par défaut pour l'auteur
            'publication_date' => null  // Pas de valeur par défaut pour la date
        ];

        // Création et gestion du formulaire de recherche
        $form = $this->createForm(SearchType::class, $defaultFilters);  // Passer les valeurs par défaut au formulaire
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide, on récupère les filtres
        $filters = [
            'title' => $form->get('title')->getData(),
            'author' => $form->get('author')->getData(),
            'publication_date' => $form->get('publication_date')->getData(),
        ];

        // Si le formulaire n'est pas soumis, on applique les valeurs par défaut
        if (!$form->isSubmitted() || !$form->isValid()) {
            // Si pas soumis ou valide, on effectue une recherche avec les valeurs par défaut
            $googleBooksResults = $this->searchGoogleBooks($defaultFilters, $googleBooksApiKey);
        } else {
            // Si le formulaire est soumis et valide, on effectue la recherche avec les filtres donnés
            $googleBooksResults = $this->searchGoogleBooks($filters, $googleBooksApiKey);
        }

        return $this->render('books/index.html.twig', [
            'form' => $form->createView(),
            'results' => $googleBooksResults ?? [], // Résultats de l'API
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
            return []; // Retourne un tableau vide si aucun filtre n'est sélectionné
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
            // Log l'erreur en cas d'échec de la requête
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