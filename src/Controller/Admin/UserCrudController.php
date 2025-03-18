<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('email')
                ->setLabel('Email')
                ->setFormTypeOption('disabled', true), // Affiché mais non modifiable

            TextField::new('firstname')
                ->setLabel('Prénom')
                ->setFormTypeOption('disabled', true), // Affiché mais non modifiable

            TextField::new('lastname')
                ->setLabel('Nom')
                ->setFormTypeOption('disabled', true)
                ->onlyOnIndex(), // Affiché seulement dans la liste

            ArrayField::new('roles')
                ->setLabel('Rôles'), // Seul champ modifiable

            BooleanField::new('isVerified')
                ->setLabel('Vérifié')
                ->setFormTypeOption('disabled', true)
                ->hideOnForm(),

            DateField::new('created_at')
                ->setLabel('Date de création')
                ->hideOnForm() // Affiché mais pas dans les formulaires
                ->setSortable(true),

            DateField::new('updated_at')
                ->setLabel('Date de modification')
                ->hideOnForm() // Affiché mais pas dans les formulaires
                ->setSortable(true),
        ];
    }
}
