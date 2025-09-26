

# TP1 – Clean Architecture & CQRS (v3)

## Structure du projet

- **Domain/** : Entités métier, value objects, règles métier, invariants.
- **Application/** :  
  - **Command/** : gestion des écritures (Create, Update, Delete)  
  - **Query/** : gestion des lectures (Get, List)  
  - **Mediator/** : médiateur pour dispatcher Command/Query
  - **DTO, Mapping, Ports** : interfaces, objets de transfert, mappers
- **Persistence/** : Implémentations des repositories, accès base de données.
- **Presentation/** : Contrôleurs API, endpoints REST.
- **External/** : Intégrations externes.

## Pattern CQRS (Command Query Responsibility Segregation)

- **Command** : toute opération d’écriture (création, modification, suppression)
- **Query** : toute opération de lecture (récupération, listing)
- **Mediator** : centralise l’acheminement des commandes et requêtes, découple le contrôleur de la logique métier

### Bénéfices observés

- Séparation stricte des responsabilités (lecture/écriture)
- Scalabilité : optimisation indépendante des lectures et écritures
- Testabilité accrue
- Facilité d’ajout de comportements transverses (logs, validation, etc.) via le médiateur

## Démarrage

1. Installer les dépendances :  
	`composer install`
2. Configurer la base de données dans `.env`
3. Lancer les migrations :  
	`php artisan migrate`
4. Démarrer le serveur :  
	`php artisan serve`

## Fonctionnalités

- CRUD utilisateur via API REST
- Architecture hexagonale, évolutive et testable
- CQRS et médiateur pour la couche Application

## API Utilisateur

- Les routes API sont définies dans `routes/api.php`.
- Exemple d’URL : `/api/users` (CRUD utilisateur via UserApiController).
