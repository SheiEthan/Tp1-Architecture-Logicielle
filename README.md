
# TP1 – Clean Architecture (v2)

## Structure du projet (5 couches hexagonales)

- **Domain/** : Entités métier, value objects, règles métier, invariants. Aucun accès ORM/framework.
- **Application/** : Use cases, ports (interfaces), DTO, mapping. Gère la logique applicative et les transactions.
- **Persistence/** : Implémentations des repositories/UoW, accès base de données (Eloquent, SQL, etc.).
- **Presentation/** : Contrôleurs, endpoints API. Appellent uniquement les use cases, gèrent la sérialisation et les erreurs.
- **External/** : Intégrations externes (API, services tiers, etc.).

## Règles d’architecture

- Les dépendances vont uniquement vers le bas : Presentation → Application → Domain ; Persistence/External → Application (impl. des ports).
- Le domaine ne dépend d’aucun framework, ORM ou DTO.
- Les use cases ne contiennent pas d’accès direct à la base ou à l’ORM.
- Les dépendances sont injectées par constructeur et résolues via le conteneur Laravel.

## Choix de conception

- Respect strict de la séparation des responsabilités.
- Mapping centralisé entre entités et DTO.
- Tests unitaires sur les invariants métier dans Domain.
- Remplaçabilité des implémentations (ports/interfaces).

## Démarrage

1. Installer les dépendances :  
	`composer install`
2. Configurer la base de données dans `.env`.
3. Lancer les migrations :  
	`php artisan migrate`
4. Démarrer le serveur :  
	`php artisan serve`

## Fonctionnalités

- CRUD utilisateur via API REST.
- Architecture hexagonale, évolutive et testable.

## API Utilisateur

- Les routes API sont définies dans `routes/api.php`.
- Exemple d’URL : `/api/users` (CRUD utilisateur via UserApiController).
