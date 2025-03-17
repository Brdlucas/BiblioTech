<?php

namespace App\Controller\Admin;

use App\Entity\Borrowing;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;


class BorrowingCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Borrowing::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('book')  // Affiche la relation avec Book
                ->setFormTypeOption('choice_label', 'title') // Affiche le titre du livre dans le formulaire
                ->setLabel('Titre du livre')
                ->setCrudController(BookCrudController::class), // Lié à la gestion des livres
            AssociationField::new('userbook')  // Affiche la relation avec Book
                ->setFormTypeOption('choice_label', 'email') // Affiche l'email de l'utilisateur dans le formulaire
                ->setLabel('Email de l\'utilisateur')
                ->setCrudController(UserCrudController::class), // Lié à la gestion des utilisateurs
            ChoiceField::new('status', 'Statut')
                ->setChoices([
                    'En attente' => 'waiting',
                    'Approuvé' => 'approved',   // Correspond à la valeur qui sera définie dans la base
                    'Refusé' => 'refused',
                    'Retourné' => 'returned',
                ]),
            DateField::new('emprunted_at', 'Emprunté le'),
            DateField::new('rendered_at', 'A Rendre le'),
        ];
    }

    /**
     * Ajouter l'action "Approuver" dans la colonne d'actions
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Ajouter l'action "Approuver" dans la liste d'actions
            ->add(
                Crud::PAGE_INDEX,
                Action::new('approve', 'Approuver')
                    ->linkToCrudAction('approveAction')  // Lien vers la méthode approveAction
                    ->setCssClass('btn btn-success')     // Class CSS pour un bouton stylisé
            );
    }

    /**
     * Méthode d'action pour approuver l'emprunt
     */
    public function approveAction(EntityManagerInterface $entityManager, Request $request, AdminUrlGenerator $adminUrlGenerator): Response
    {
        // Récupérer l'ID de l'emprunt depuis l'URL
        $id = $request->query->get('entityId');

        if (!$id) {
            $this->addFlash('error', 'ID de l\'emprunt non trouvé.');
            return $this->redirectToRoute('admin');
        }

        // Récupérer l'entité Borrowing
        $borrowing = $entityManager->getRepository(Borrowing::class)->find($id);

        if (!$borrowing) {
            $this->addFlash('error', 'Emprunt non trouvé.');
            return $this->redirectToRoute('admin', [
                'entity' => 'Borrowing',
            ]);
        }

        // Modifier le statut de l'emprunt
        $borrowing->setStatus('approved');  // Remplacer 'approve' par 'approved'
        $entityManager->persist($borrowing);
        $entityManager->flush();

        // Ajouter un message flash de succès
        $this->addFlash('success', 'L\'emprunt a été approuvé avec succès.');

        $url = $adminUrlGenerator
            ->setController(crudControllerFqcn: self::class) // Rediriger vers BorrowingCrudController
            ->setAction(Crud::PAGE_INDEX) // Aller vers la page INDEX
            ->generateUrl();

        // Rediriger vers la liste des emprunts
        return $this->redirect($url);
    }
}