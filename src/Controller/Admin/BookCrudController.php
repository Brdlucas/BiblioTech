<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Borrowing;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use DateTimeImmutable;

class BookCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getEntityFqcn(): string
    {
        return Book::class;
    }

    public function createEntity(string $entityFqcn)
    {
        // Créer un nouveau livre avec des valeurs par défaut
        $book = new Book();
        $book->setIsFromBdd(true);

        // Initialiser les dates pour éviter des erreurs en base de données
        $book->setCreatedAt(new DateTimeImmutable());
        $book->setPublishedAt(new DateTimeImmutable());

        return $book;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entity): void
    {
        if ($entity instanceof Book) {
            // Supprimer les emprunts liés avant de supprimer le livre
            $borrowings = $entityManager->getRepository(Borrowing::class)->findBy(['book' => $entity]);

            foreach ($borrowings as $borrowing) {
                $entityManager->remove($borrowing);
            }

            // Supprimer le livre
            $entityManager->remove($entity);
            $entityManager->flush();
        }
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title', 'Titre'),
            TextField::new('content', 'Contenu'),
            TextField::new('author', 'Auteur'),
            TextField::new('image', 'Image'),
            TextField::new('url', 'Url'),
            AssociationField::new('category', 'Catégorie')
                ->setCrudController(CategoryCrudController::class)
                ->setRequired(true),
            DateField::new('created_at', 'Créé le')->setRequired(true),
            DateField::new('published_at', 'Publié le')->setRequired(true),
        ];
    }
}
