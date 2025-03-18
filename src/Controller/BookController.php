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
use Symfony\Component\Validator\Constraints\Length;

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
        if (!empty($filters['title']) || !empty($filters['author'])) {
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

        // Vérification du nombre d'emprunts
        $user = $this->getUser();
        $borrowings = $entityManager->getRepository(Borrowing::class)->findBy(['userbook' => $user->getId()]);

        // Filtrer uniquement les emprunts avec le statut "approved" ou "waiting"
        $validBorrowings = array_filter($borrowings, function ($borrowing) {
            return in_array($borrowing->getStatus(), ['approved', 'waiting']); // Vérifie les deux statuts
        });

        if (count($validBorrowings) >= 5) {
            $this->addFlash('danger', 'Vous avez déjà emprunté 5 livres (approuvés ou en attente).');
            return $this->redirectToRoute('app_books');
        }


        // Récupération des informations du livre via l'API Google Books
        $googleBooksApiKey = $this->getParameter('google_books_api_key');
        $googleBookIdResult = $this->getBookDetail($id, $googleBooksApiKey);
        $bookInt = $googleBookIdResult['volumeInfo'];


        // Vérification et récupération ou création de la catégorie
        $categoryName = $bookInt['categories'][0] ?? 'Aucune catégorie';
        $category = $entityManager->getRepository(Category::class)->findOneBy(['name' => $categoryName]);
        if (!$category) {
            $category = new Category();
            $category->setName($categoryName);
            $entityManager->persist($category);
            $entityManager->flush();
        }

        // Récupération des données du livre ou création du livre
        $title = $bookInt['title'] ?? 'Aucun titre';
        $author = $bookInt['authors'][0] ?? 'Aucun auteur';
        $image = $bookInt['imageLinks']['smallThumbnail'] ?? "/img/no-img.png";
        $url = $bookInt['infoLink'] ?? "aucun lien";
        $content = $bookInt['description'] ?? 'Aucune description disponible';
        $publishedDate = $bookInt['publishedDate'] ?? '1970-01-01'; // Par défaut une vieille date

        $existingBook = $entityManager->getRepository(Book::class)->findOneBy(['title' => $title]);
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

        // Vérifier si l'utilisateur a déjà emprunté ce livre
        $existingBorrowing = $entityManager->getRepository(Borrowing::class)->findOneBy([
            'userbook' => $this->getUser(),
            'book' => $book
        ]);

        if ($existingBorrowing) {
            $this->addFlash('danger', 'Vous avez déjà emprunté ce livre.');
            return $this->redirectToRoute('app_books');
        }

        // Création d'un nouvel emprunt
        $borrowing = new Borrowing();
        $borrowing->setBook($book);
        $borrowing->setUserbook($this->getUser());
        $borrowing->setEmpruntedAt(new \DateTimeImmutable());
        $borrowing->setRenderedAt(new \DateTimeImmutable('+10 days'));
        $borrowing->setStatus('waiting');

        $entityManager->persist($borrowing);
        $entityManager->flush();

        $this->addFlash('success', "Votre emprunt a bien été enregistré et soumis à l'autosisation de l'administrateur !");

        return $this->render('books/book.html.twig', [
            'book' => $googleBookIdResult ?? [], // Résultats de l'API
        ]);
    }
}
