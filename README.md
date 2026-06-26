# InnovShop

## Présentation

InnovShop est une application e-commerce développée avec Symfony dans le cadre d’un projet de formation / examen Développeur Web et Web Mobile.

L’application permet de consulter des produits, gérer un panier, créer un compte utilisateur, passer commande et consulter un espace client. Elle contient aussi un back-office administrateur pour gérer la boutique.

## Fonctionnalités principales

- Page d’accueil avec produits à la une et derniers produits ajoutés
- Catalogue produits
- Fiche détail produit
- Panier
- Inscription et connexion
- Passage de commande
- Espace client
- Historique des commandes
- Back-office administrateur
- Gestion des produits
- Gestion des commandes
- Gestion des utilisateurs
- Espace vendeur / marketplace
- Paiement Stripe
- Envoi d’email de confirmation de commande

## Technologies utilisées

- PHP
- Symfony
- Twig
- Doctrine ORM
- MySQL / MariaDB
- EasyAdmin
- Stripe
- HTML / CSS
- JavaScript
- Asset Mapper

## Prérequis

Pour installer le projet en local, il faut disposer de :

- PHP compatible avec le projet
- Composer
- Symfony CLI si possible
- MySQL ou MariaDB
- Un serveur local type XAMPP, WAMP ou équivalent

Le projet indique dans `composer.json` une version PHP `>= 8.4`.

## Installation du projet en local

Cloner le dépôt :

```bash
git clone URL_DU_DEPOT
cd innovshop
composer install
```

Configurer l’environnement :

- copier le fichier `.env` si nécessaire ;
- configurer la variable `DATABASE_URL` avec les informations de la base de données locale ;
- ne pas mettre de mot de passe ou de clé secrète dans un dépôt public.

Créer la base de données :

```bash
php bin/console doctrine:database:create
```

Lancer les migrations :

```bash
php bin/console doctrine:migrations:migrate
```

Compiler les assets si nécessaire :

```bash
php bin/console asset-map:compile
```

## Lancer le projet

Avec Symfony CLI :

```bash
symfony server:start
```

Ou avec le serveur PHP intégré :

```bash
php -S localhost:8000 -t public
```

L’application est ensuite accessible dans le navigateur.

## Accès aux espaces

- Les visiteurs peuvent consulter la page d’accueil, le catalogue et les fiches produits.
- Les utilisateurs connectés peuvent gérer leur panier, passer commande et consulter leur espace client.
- Les administrateurs peuvent accéder au back-office pour gérer la boutique.
- Les vendeurs peuvent accéder à l’espace vendeur si leur compte possède le rôle nécessaire.

Identifiants de test à compléter.

## Structure rapide du projet

- `src/Controller` : contrôleurs Symfony
- `src/Entity` : entités Doctrine
- `src/Repository` : requêtes vers la base de données
- `src/Service` : logique métier ou services spécifiques
- `templates` : vues Twig
- `assets/styles` : fichiers CSS
- `migrations` : migrations Doctrine
- `public` : fichiers accessibles publiquement

## Base de données

La base de données est gérée avec Doctrine ORM et les migrations Doctrine.

Les entités Doctrine décrivent les tables principales du projet. Les migrations permettent d’appliquer les modifications de structure dans la base de données.

Les principales entités du projet sont notamment :

- `User`
- `Product`
- `Category`
- `Cart`
- `CartItem`
- `Order`
- `OrderItem`
- `Variant`
- `SellerProfile`

## Déploiement

Le projet peut être déployé sur un hébergeur compatible PHP/Symfony, par exemple AlwaysData.

Étapes générales :

- transférer les fichiers du projet ;
- configurer les variables d’environnement ;
- installer les dépendances avec Composer ;
- configurer la base de données ;
- exécuter les migrations ;
- vider le cache ;
- compiler les assets si nécessaire ;
- vérifier que le serveur pointe vers le dossier `public`.

URL du site déployé à compléter.

## Auteur

Projet réalisé par Alexandre Luis dans le cadre de la formation Développeur Web et Web Mobile.

## Objectif examen

Ce projet sert de support pour démontrer les compétences RNCP :

- développement front-end avec Twig, HTML et CSS ;
- développement back-end avec Symfony ;
- gestion d’une base de données relationnelle ;
- sécurité, authentification et rôles ;
- déploiement d’une application web dynamique.

## Informations à compléter

- URL du dépôt GitHub ou GitLab
- URL du site déployé
- Identifiants de test
- Informations de configuration propres à l’environnement local ou à l’hébergement
