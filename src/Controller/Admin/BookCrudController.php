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
            TextEditorField::new('Content', 'Contenu'),
            TextEditorField::new('Image', 'Image'),
            TextEditorField::new('Url', 'URL du PDF'),
            DateField::new('created_at', 'Créé le'),
            DateField::new('published_at', 'Publié le'),
            AssociationField::new('category', 'Catégorie')
                ->setCrudController(CategoryCrudController::class)
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
        ];
    }
}
