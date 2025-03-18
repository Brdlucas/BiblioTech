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
    public function index(Request $request, EntityManagerInterface $entityManager)
    {
        // Récupérer la valeur dans l'URL
        $value = $request->query->get('value');

        // Récupérer la clé API dans la méthode, pas dans le constructeur
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

        // Recherche dans la base de données
        $queryBuilder = $entityManager->getRepository(Book::class)->createQueryBuilder('b');

        // Appliquer les filtres de recherche sur le titre et l'auteur
        if (!empty($filters['title'])) {
            $queryBuilder->andWhere('b.title LIKE :title')
                ->setParameter('title', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['author'])) {
            $queryBuilder->andWhere('b.author LIKE :author')
                ->setParameter('author', '%' . $filters['author'] . '%');
        }

        // Exécuter la recherche et obtenir les résultats de la BDD
        $booksFromBdd = $queryBuilder->getQuery()->getResult();

        // Recherche via Google Books API si des critères sont remplis
        $googleBooksResults = [];
        if (!empty($filters['title']) || !empty($filters['author'])) {
            $googleBooksResults = $this->searchGoogleBooks($filters, $googleBooksApiKey);
        } else {
            $googleBooksResults = $this->searchGoogleBooks(['title' => 'Harry Potter'], $googleBooksApiKey);
        }

        // Extraire les IDs Google des résultats de l'API pour éviter les doublons
        $googleBookIds = [];
        foreach ($googleBooksResults as $googleBook) {
            if (isset($googleBook['id'])) {
                $googleBookIds[] = $googleBook['id'];
            }
        }

        // Filtrer les livres de la BDD pour ne pas inclure les doublons avec l'API
        $uniqueBddBooks = [];
        foreach ($booksFromBdd as $bookFromBdd) {
            $googleId = $bookFromBdd->getGoogleId();
            if (!$googleId || !in_array($googleId, $googleBookIds)) {
                $uniqueBddBooks[] = $bookFromBdd;
            }
        }

        // Combiner les livres uniques de la BDD et tous ceux de l'API
        $finalBooks = array_merge($googleBooksResults, $uniqueBddBooks);

        // Retourner la vue avec les résultats et le formulaire
        return $this->render('books/index.html.twig', [
            'form' => $form->createView(),
            'results' => $finalBooks,
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
        $url = 'https://www.googleapis.com/books/v1/volumes?q=' . $queryStr . '&maxResults=7&key=' . $googleBooksApiKey;

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
    public function book(Request $request, string $id, EntityManagerInterface $entityManager): Response
    {
        $isFromBDD = $request->query->get('isFromBdd');

        if ($isFromBDD) {
            $book = $entityManager->getRepository(Book::class)->findOneBy(['id' => $id]);
            return $this->render('books/book-bdd.html.twig', [
                'book' => $book ?? [],
            ]);
        } else {
            $googleBooksApiKey = $this->getParameter('google_books_api_key');

            $googleBookIdResult = $this->getBookDetail($id, $googleBooksApiKey);

            return $this->render('books/book-api.html.twig', [
                'book' => $googleBookIdResult ?? [], // Résultats de l'API
            ]);
        }
    }





    #[Route('/borrowing/{id}', name: 'app_borrowing_id', methods: ['POST'])]
    public function borrowing(Request $request, string $id, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }



        $isFromBdd = $request->query->get('isFromBdd');

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

        if ($isFromBdd) {
            // Récupérer le livre à partir de l'ID
            $book = $entityManager->getRepository(Book::class)->findOneBy(['id' => $id]);

            if (!$book) {
                // Si le livre n'est pas trouvé, lancer une exception ou afficher un message d'erreur
                throw $this->createNotFoundException('Livre non trouvé');
            }

            // Vérifier si un emprunt existe déjà pour ce livre et cet utilisateur
            $existingBorrowing = $entityManager->getRepository(Borrowing::class)->findOneBy([
                'book' => $book,
                'userbook' => $user,
            ]);

            if ($existingBorrowing) {
                // Si l'emprunt existe déjà, afficher un message ou rediriger
                $this->addFlash('warning', 'Vous avez déjà emprunté ce livre.');
                return $this->render('books/book-api.html.twig', [
                    'book' => $book, // Résultats de l'API
                ]);
            }

            $newBorrowing = new Borrowing();
            $newBorrowing->setBook($book);
            $newBorrowing->setUserbook($user);
            $newBorrowing->setEmpruntedAt(new \DateTimeImmutable());
            $newBorrowing->setRenderedAt(new \DateTimeImmutable('+10 days'));
            $newBorrowing->setStatus('waiting');

            $entityManager->persist($newBorrowing);
            $entityManager->flush();

            return $this->render('books/book-bdd.html.twig', [
                'book' => $book,
            ]);
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
        $image = $bookInt['imageLinks']['thumbnail'] ?? "/img/no-img.png";
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
            $book->setGoogleId($id ?? null);
            $book->setIsFromBdd(true);


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

        $this->addFlash('success', "Votre emprunt a bien été enregistré et soumis à l'autorisation de l'administrateur !");

        return $this->render('books/book-api.html.twig', [
            'book' => $googleBookIdResult ?? [], // Résultats de l'API
        ]);
    }
}
