<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use App\Entity\Category;
use App\Entity\Borrowing;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class BookCrudController extends AbstractCrudController
{
    private $entityManager;

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
        // Créer un livre
        $book = new Book();
        $book->setIsFromBdd(true); // Définir isFromBdd à true lors de la création

        // Assurez-vous que la catégorie est bien persistée
        $category = new Category();
        $this->entityManager->persist($category); // Assurez-vous que la catégorie est gérée avant de l'assigner

        // Tu peux ensuite assigner la catégorie au livre
        $book->setCategory($category);

        return $book;
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entity): void
    {
        if ($entity instanceof Book) {
            // Supprimer les emprunts associés à ce livre
            $borrowings = $entityManager->getRepository(Borrowing::class)->findBy(['book' => $entity]);

            foreach ($borrowings as $borrowing) {
                $entityManager->remove($borrowing);
            }

            // Détacher la catégorie avant de supprimer le livre (optionnel si cascade={"persist", "remove"} est configuré)
            $entity->setCategory(null);

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
            TextField::new('url', 'URL du PDF'),
            AssociationField::new('category', 'Catégorie')
                ->setCrudController(CategoryCrudController::class) // Lien vers le gestionnaire de catégories
                ->setRequired(true), // Facultatif : rendre le champ obligatoire pour un livre
            DateField::new('created_at', 'Créé le'),
            DateField::new('published_at', 'Publié le'),
        ];
    }
}
