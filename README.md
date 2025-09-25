# TP1 – Architecture N-Layer (PHP - Laravel 12)

## Structure du projet

Ce projet utilise une architecture n-layer :
- **Controllers** : Gèrent les requêtes HTTP et la logique de présentation (`app/Http/Controllers/`).
- **Services** : Contiennent la logique métier, indépendamment des contrôleurs (`app/Services/`).
- **Repositories** : Gèrent l’accès aux données (base de données, modèles) (`app/Repositories/`).
- **Models** : Représentent les entités de la base de données (`app/Models/`).

## API Utilisateur

- Les routes API sont définies dans `routes/api.php`.
- Exemple d’URL : `/api/users` (CRUD utilisateur via UserApiController).

## Démarrage

1. Installer les dépendances :  
	`composer install`
2. Configurer la base de données dans `.env`. Voir exemple `.env.example`
3. Lancer les migrations :  
	`php artisan migrate`
4. Démarrer le serveur :  
	`php artisan serve`

## Fonctionnalités

- CRUD utilisateur via API REST.
- Architecture claire et évolutive (n-layer).
