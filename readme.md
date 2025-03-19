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
 - Codes couleurs : #63918b #FFFF 

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

### DEPLOIEMENT Documentation

# Comment déployer son projet

Ce n'est pas parce que l'application est terminée qu'elle est en ligne. Il faut la déployer sur un serveur de production.

## Pourquoi déployer une application Symfony ?

Le déploiement d'une application Symfony permet de mettre en ligne une application web. Cela permet de rendre accessible l'application à tous les utilisateurs à partir d'un navigateur web.

## Achat de nom de domaine + hébergement web

Il est indispensable d'avoir un serveur web pour pouvoir déployer une application. Le mieux est de l'accompagner avec un nom de domaine pour se rendre plus facilement à l'adresse de l'application plutôt qu'une adresse IP.

### Liste des hébergeurs web de confiance en fiabilité et de performances :

#### Hostinger
Hostinger, que vous connaissez probablement avec ses nombreuses campagnes de promotion sur YouTube et auprès d'influenceurs en tout genre. Ils ont une bonne réputation en France et sont très rapides à répondre en cas de problème. Le prix des offres bouge beaucoup avec les promos, opter pour un achat long terme est donc une bonne idée.
**Lien :** Prendre une offre sur Hostinger

#### Infomaniak
Infomaniak est un hébergeur de confiance en fiabilité et de performances. Basé en Suisse, c'est l'acteur n°1 du secteur en matière d'exploitation zéro carbone. Ils proposent une offre unique qui est bien taillée pour les applications web Symfony.
**Lien :** Prendre une offre sur Infomaniak

#### o2Switch
o2Switch n'est pas le dernier de la liste, loin de l'être. Avec une offre unique aussi, vous avez également tout ce qu'il faut pour gérer votre application web Symfony. L'interface de gestion n'est pas une faite-maison, ils sont équipés de cPanel.

### Bons plans
- Parmi les solutions pas chères pour les petits budgets, il y a **Obambu**, plusieurs offres adaptées à la demande. Peu cher veut aussi dire peu de services.
- Pour se procurer des noms de domaine au prix d'un ou deux cafés, visitez **Amen.fr**, c'est les soldes presque toute l'année. Il suffira de faire pointer les DNS de votre nom de domaine vers l'adresse IP de serveur de production.

## Préparation du serveur de production

- Rattacher un nom de domaine au serveur
- Créer une base de données correspondante
- Vérifier la version PHP correspondante
- Vérifier la version de composer
- Identifiants de connexion SSH

Ces informations sont à récolter avant de créer le projet pour éviter les surprises lors du déploiement.

## Préparation du projet en local

- Réinitialiser les migrations avec un seul fichier de version, afin de les migrer en une seule fois sur le serveur de production
- Compiler les éléments nécessaires à la production (*tailwindcss, assets, webpack, etc.*)
- Renseigner les informations du serveur de base de données, `MAILER_DSN`, `API_KEY` dans le fichier `.env` (**Attention** à ne pas mettre le `.env` à jour pour un dépôt de code public sur GitHub)
- Mettre en place le fichier `.htaccess` du dossier `public/` avec le contenu suivant :
  ```
  public/.htaccess
  ```
- Dans le cas où vous devez aussi rediriger la racine du nom de domaine vers le dossier `public/`, il faut mettre en place le fichier `.htaccess` du dossier `public/` avec le contenu suivant :
  ```
  /.htaccess
  ```
- Commit et push de l'ensemble du projet sur **GitHub**. Veillez à ce que la branche principale soit celle de production. Cela permettra de cloner le projet sur le serveur de production avec la commande `git clone`.

## Déploiement du projet sur le serveur de production

### Vérifier la version de composer

Vérifier auprès de votre hébergeur la version de **composer**. Dans le cas de la version `2+` la commande sera probablement `composer2` au lieu de `composer`.

### Les étapes

1. Cloner le projet sur le serveur de production avec la commande :
   ```sh
   git clone <repository-url>
   ```
2. Renommer le dossier du projet si nécessaire.
3. Modifier le fichier `.env` avec l'environnement de production dans le cas où votre dépôt GitHub est public.
4. Se rendre dans le terminal du serveur de production, naviguer dans le dossier du projet et exécuter les commandes suivantes :
   ```sh
   composer install ou composer2 install
   ```
5. Si vous n'avez pas de fichier de migration :
   ```sh
   php bin/console make:migration
   php bin/console d:m:m si besoin (lancez les fixtures)
   ```
6. Passer l'environnement en production dans le fichier `.env`
7. Nettoyer et préparer le cache :
   ```sh
   php bin/console cache:clear && php bin/console cache:warmup
   ```
8. Installer les dépendances en mode production :
   ```sh
   composer install --no-dev --optimize-autoloader
   ```
9. Vérifier que l'application est bien en ligne

### En cas d'erreur

- **Erreur `500`** : Réactiver le mode `dev` afin de voir l'erreur et la corriger ou consulter les logs.
- **Erreur `400`** : Votre application ne pointe pas sur le bon dossier, vérifiez que la racine de l'application est bien le dossier `/public`.


