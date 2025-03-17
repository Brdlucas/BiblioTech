<?php

namespace App\Controller\Admin;

use App\Entity\Book;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BookCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Book::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title', 'Titre'),
            TextField::new('content', 'Contenu'),
            TextField::new('image', 'Image'),
            TextField::new('url', 'URL du PDF'),
            AssociationField::new('category', 'Catégorie')
                ->setCrudController(CategoryCrudController::class)  // Lien vers le gestionnaire de catégories
                ->setFormTypeOptions([
                    'by_reference' => false,  // Permet de gérer la relation en tant que nouvelle association
                ])
                ->setRequired(true),  // Facultatif : rendre le champ obligatoire pour un livre
            DateField::new('created_at', 'Créé le'),
            DateField::new('published_at', 'Publié le'),

            // AssociationField pour la catégorie
        ];
    }
}