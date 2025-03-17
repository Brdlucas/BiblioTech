# BiblioTech
---
Application d'emprunt et réservation de livres en ligne

### Commandes
---

lancement du serveur : symfony server:start
arret du serveur : symfony server:stop
tailwindcss : php bin/console tailwind:build --watch

### Stack
---
Framework Symfony, Tailwindcss, Google Fonts, Google Fonts Icons


### Fonctionnalités
---
#### Rôles et Permissions

###### Utilisateur :
    - Création d’un compte pour accéder aux emprunts.
    - Consultation du catalogue et détails des livres.
    - Emprunt et restitution des ouvrages numériques.
  
###### Administrateur :
    - Gestion du catalogue de livres.
    - Gestion des utilisateurs et des emprunts.
    - Suivi des emprunts en retard et envoi de rappels.

###### Recherche par :
    - Titre / Auteur
    - Genre
    - Popularité

### Assets
---
Parmi les éléments que nous avons besoins pour les médias ou autre de l'application, il nous faut :
 - Icons
 - Images
 - Polices d'écritures
 - Codes couleurs : #38b6ff #fba708 #0c274e

### UI Design
---

La liste des compositions de l'interface de l'application :

- Navbar
- Filter bar
- Modals (forms, infos, etc.)
- Cards
- header, footer
- Dashboard Admin
  
### Diagramme BDD et UML
---
 - diagramme : https://app.diagrams.net/?libs=general;flowchart
 - BDD : https://drawsql.app/teams/not-26/diagrams/bibliotech

### Homepage
---
- Header :
    > - bouton se connecter 
    > - bouton inscription
    > - logo du site web

- Hero :
    > - 2 lignes de 4 livres avec un bouton "voir plus".

- Footer :
  > - RGPD
  > - Mention légale
  > - Politique de confidentialitées


A faire : 

  - ~~Trello~~
  - ~~Checker l'api des livres (https://developers.google.com/books/docs/v1/libraries?hl=fr)~~
  - ~~Sécuriser les accès suivant les roles~~
  - ~~Installer tailwindcss~~
  - Design avec IA
  - Page d'historique d'emprunt
  - ~~Recherche et Filtres Avancés~~
  - Support de présentation (fin)