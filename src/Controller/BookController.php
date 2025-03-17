<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Borrowing;
use App\Entity\Category;
use App\Form\SearchType;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BookController extends AbstractController
{
    private $client;
    private $logger;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }
    #[Route('/books', name: 'app_books', methods: ['GET', 'POST'])]
    public function index(Request $request)
    {
        // Récupération de la valeur dans l'URL
        $value = $request->query->get('value');

        // Récupération de la clé API dans la méthode, pas dans le constructeur
        $googleBooksApiKey = $this->getParameter('google_books_api_key');

        // Valeurs par défaut pour les filtres
        $defaultFilters = [
            'title' => $value ?? '', // Utilise la valeur de l'URL ou une chaîne vide si rien n'est passé
            'author' => null,
        ];

        // Création et gestion du formulaire de recherche
        $form = $this->createForm(SearchType::class, $defaultFilters);
        $form->handleRequest($request);

        // Vérifier si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Si le formulaire est valide, on récupère les données du formulaire
            $filters = [
                'title' => $form->get('title')->getData() ?? $defaultFilters['title'],
                'author' => $form->get('author')->getData() ?? $defaultFilters['author'],
            ];
        } else {
            // Si le formulaire n'est pas soumis ou pas valide, on utilise les filtres par défaut
            $filters = $defaultFilters;
        }

        // Recherche basée sur les filtres
        if (!empty($filters['title'])) {
            // Si un titre est fourni (soit par le formulaire, soit par l'URL), on effectue la recherche
            $googleBooksResults = $this->searchGoogleBooks($filters, $googleBooksApiKey);
        } else {
            // Si aucun titre n'est spécifié, recherche une valeur par défaut
            $googleBooksResults = $this->searchGoogleBooks(['title' => 'Harry Potter'], $googleBooksApiKey);
        }

        // Retourne la vue avec les résultats et le formulaire
        return $this->render('books/index.html.twig', [
            'form' => $form->createView(),
            'results' => $googleBooksResults ?? [],
        ]);
    }



    private function searchGoogleBooks(array $filters, string $googleBooksApiKey)
    {
        $query = [];

        // Si l'utilisateur a renseigné un titre, on l'ajoute à la requête
        if ($filters['title'] && $filters['title']) {
            $query[] = 'intitle:' . urlencode($filters['title']);
        }

        // Si l'utilisateur a renseigné un auteur, on l'ajoute à la requête
        if (isset($filters['author']) && $filters['author']) {
            $query[] = 'inauthor:' . urlencode($filters['author']);
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

    #[Route('/borrowing/{id}', name: 'app_borrowing_id', methods: ['POST'])]
    public function borrowing(Request $request, string $id, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        $googleBooksApiKey = $this->getParameter('google_books_api_key');
        $googleBookIdResult = $this->getBookDetail($id, $googleBooksApiKey);
        $bookInt = $googleBookIdResult['volumeInfo'];

        // Supposons que le nom de la catégorie soit 'Horror'
        $categoryName = $bookInt['categories'][0] ?? 'Aucune catégorie';

        // Vérifie si la catégorie existe déjà
        $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);

        if (!$category) {
            // Si la catégorie n'existe pas, on la crée
            $category = new Category();
            $category->setName($categoryName);

            // Persiste et enregistre la nouvelle catégorie
            $entityManager->persist($category);
            $entityManager->flush();
        }


        $title = $bookInt['title'] ?? 'Unknown Title';
        $author = $bookInt['authors'][0] ?? 'Unknown Author';
        $image = $bookInt['imageLinks']['smallThumbnail'] ?? "/img/no-img.png";
        $url = $bookInt['infoLink'] ?? "aucun lien";
        $content = $bookInt['description'] ?? 'No description available';
        $publishedDate = $bookInt['publishedDate'] ?? '1970-01-01'; // Par défaut une vieille date

        $existingBook = $entityManager->getRepository(Book::class)->findOneBy(['title' => $bookInt['title'] ?? null]);

        if (!$existingBook) {
            $book = new Book();
            $book->setTitle($title);
            $book->setAuthor($author);
            $book->setImage($image);
            $book->setUrl($url);
            $book->setCategory($category);
            $book->setContent($content);
            $book->setPublishedAt(new \DateTime($publishedDate));
            $book->setCreatedAt(new \DateTime());

            $entityManager->persist($book);
            $entityManager->flush();
        } else {
            $book = $existingBook;
        }

        $borrowing = new Borrowing();
        $borrowing->setBook($book);
        $borrowing->setUserbook($this->getUser());
        $borrowing->setEmpruntedAt(new \DateTimeImmutable());
        $borrowing->setRenderedAt(new \DateTimeImmutable('+10 days'));

        $entityManager->persist($borrowing);
        $entityManager->flush();

        return $this->render('books/book.html.twig', [
            'book' => $googleBookIdResult ?? [], // Résultats de l'API
        ]);
    }
}