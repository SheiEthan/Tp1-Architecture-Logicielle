# 🏦 Banking Microservices Architecture



## 📋 Vue d'ensemble# 🏦 Banking Microservices Architecture



Ce projet implémente une **architecture microservices complète** pour la gestion des comptes bancaires d'utilisateurs, développée dans le cadre du TP "Architecture Logicielle" - passage de la conception théorique à la mise en œuvre pratique.## 📋 Vue d'ensemble



### 🎯 Objectifs atteintsCe projet implémente une **architecture microservices complète** pour la gestion des comptes bancaires d'utilisateurs, développée dans le cadre du TP "Architecture Logicielle" - passage de la conception théorique à la mise en œuvre pratique.



- ✅ **Microservices réellement indépendants** avec bases de données séparées### 🎯 Objectifs atteints

- ✅ **Communication fiable** entre services via HTTP/REST

- ✅ **Cohérence des données** avec transactions distribuées atomiques- ✅ **Microservices réellement indépendants** avec bases de données séparées

- ✅ **Requêtes transverses** via service d'orchestration- ✅ **Communication fiable** entre services via HTTP/REST

- ✅ **Déploiement containerisé** avec Docker Compose- ✅ **Cohérence des données** avec transactions distribuées atomiques

- ✅ **Requêtes transverses** via service d'orchestration

---- ✅ **Déploiement containerisé** avec Docker Compose

- **Suppression d'utilisateur** → Suppression de tous ses comptes

## 🏗️ Architecture- **Opérations atomiques** : soit tout réussit, soit rien n'est appliqué

- **Requêtes transverses** : récupération agrégée utilisateur + comptes

### Services déployés

## Architecture Microservices

L'architecture comprend 4 microservices indépendants :

| Service | Port | URL | Description |
|---------|------|-----|-------------|
| **User Service** | 8001 | http://localhost:8001 | Gestion des utilisateurs |
| **Account Service** | 8002 | http://localhost:8002 | Gestion des comptes bancaires |
| **Orchestrator Service** | 8003 | http://localhost:8003 | Orchestration des opérations |
| **API Gateway** | 8000 | http://localhost:8000 | Point d'entrée unifié |


## 🚀 Démarrage rapide#### **2. Microservice CompteBancaire**

- **Base de données** : `tp3_accounts`

### Prérequis- **Tables** : `comptes_bancaires`, migrations

- **Responsabilités** : CRUD comptes, gestion soldes, règles métier bancaires

- Docker Desktop installé et démarré- **Endpoints** : `/api/accounts/*`

- Git pour le versioning

- Postman (optionnel, pour les tests)#### **3. API Gateway**

- **Responsabilités** : Agrégation de données, requêtes transverses, monitoring

### Lancement des services- **Endpoints** : `/api/gateway/*`



```bash#### **4. Orchestrateur**

# Cloner le projet- **Responsabilités** : Transactions atomiques entre services

git clone https://github.com/SheiEthan/Tp1-Architecture-Logicielle.git- **Pattern** : Saga/Orchestration

cd Tp1-Architecture-Logicielle- **Endpoints** : `/api/user-accounts/*`

git checkout microservices

## Patterns implémentés

# Démarrer tous les microservices

docker-compose up -d### **CQRS (Command Query Responsibility Segregation)**

- **Commands** : Opérations d'écriture (Create, Update, Delete)

# Vérifier que tous les services sont UP- **Queries** : Opérations de lecture (Get, List, FindByUser)

docker-compose ps- **Mediator** : Dispatch centralisé des commandes/requêtes

```

### **Saga/Orchestration**

### Tests rapides- **Transaction atomique** entre UserApi et CompteBancaire

- **Rollback automatique** en cas d'échec

```bash- **Logging détaillé** de chaque étape

# Health check de tous les services

curl http://localhost:8001/api/health  # User Service### **API Gateway**

curl http://localhost:8002/api/health  # Account Service- **Agrégation** de données provenant de plusieurs services

curl http://localhost:8003/api/health  # Orchestrator- **Requêtes parallèles** pour optimiser les performances

- **Monitoring** de la santé des microservices

# Lister les utilisateurs et comptes

curl http://localhost:8001/api/users## API Endpoints

curl http://localhost:8002/api/accounts

### **Microservices individuels**

# Requête transverse - profil utilisateur complet

curl http://localhost:8003/api/users/1/profile#### Utilisateurs

``````

GET    /api/users           # Lister tous les utilisateurs

---POST   /api/users           # Créer un utilisateur

GET    /api/users/{id}      # Récupérer un utilisateur

## 📚 API DocumentationPUT    /api/users/{id}      # Modifier un utilisateur

DELETE /api/users/{id}      # Supprimer un utilisateur

### User Service (Port 8001)```



| Endpoint | Méthode | Description | Body |#### Comptes bancaires

|----------|---------|-------------|------|```

| `/api/users` | GET | Liste des utilisateurs | - |GET    /api/accounts                # Lister tous les comptes

| `/api/users` | POST | Créer utilisateur + compte | `{"first_name":"John","last_name":"Doe","email":"john@example.com","phone":"0123456789"}` |POST   /api/accounts                # Créer un compte (user_id requis)

| `/api/users/{id}` | GET | Utilisateur par ID | - |GET    /api/accounts/{id}           # Récupérer un compte

| `/api/users/{id}` | DELETE | Supprimer utilisateur + comptes | - |PUT    /api/accounts/{id}           # Modifier un compte

| `/api/health` | GET | Health check | - |DELETE /api/accounts/{id}           # Supprimer un compte

GET    /api/accounts/user/{userId}  # Comptes d'un utilisateur

### Account Service (Port 8002)DELETE /api/accounts/user/{userId}  # Supprimer tous les comptes d'un utilisateur

```

| Endpoint | Méthode | Description | Body |

|----------|---------|-------------|------|### **Orchestration atomique**

| `/api/accounts` | GET | Liste des comptes | - |```

| `/api/accounts` | POST | Créer un compte | `{"user_id":1,"solde":1000.00,"type_compte":"courant"}` |POST   /api/user-accounts           # Créer utilisateur + compte (atomique)

| `/api/accounts/{id}` | GET | Compte par ID | - |DELETE /api/user-accounts/{userId}  # Supprimer utilisateur + comptes (atomique)

| `/api/accounts/user/{userId}` | GET | Comptes d'un utilisateur | - |```

| `/api/accounts/{id}` | DELETE | Supprimer un compte | - |

| `/api/health` | GET | Health check | - |### **API Gateway (requêtes transverses)**

```

### Orchestrator Service (Port 8003)GET /api/gateway/users/{userId}/complete  # Utilisateur + ses comptes + stats

GET /api/gateway/users/complete           # Tous utilisateurs + comptes + résumé

| Endpoint | Méthode | Description | Réponse |GET /api/gateway/stats                    # Statistiques agrégées globales

|----------|---------|-------------|---------|GET /api/gateway/health                   # Santé des microservices

| `/api/users/{id}/profile` | GET | Profil utilisateur avec comptes | Données agrégées utilisateur + comptes |```

| `/api/users/profiles` | GET | Tous les profils | Liste complète avec totaux |

| `/api/health` | GET | Health check avec état des services | Statut de tous les services |## Configuration



---### **Bases de données**

```env

## 🔄 Fonctionnalités métier# Service Utilisateur

DB_DATABASE_USERS=tp3_users

### Transactions atomiques

# Service CompteBancaire  

#### Création d'utilisateurDB_DATABASE_ACCOUNTS=tp3_accounts

```bash```

# 1. Créer un utilisateur → déclenche automatiquement la création d'un compte

curl -X POST http://localhost:8001/api/users \### **Connexions configurées**

  -H "Content-Type: application/json" \- UserApi → `mysql_users` (tp3_users)

  -d '{"first_name":"Alice","last_name":"Martin","email":"alice@example.com"}'- CompteBancaire → `mysql_accounts` (tp3_accounts)



# Réponse avec transaction réussie:## Démarrage

{

  "user": {"id":3,"first_name":"Alice",...},### **1. Installation**

  "account_created": {"id":3,"user_id":3,"numero_compte":"CPT003",...},```bash

  "transaction_status": "SUCCESS"composer install

}```

```

### **2. Configuration environnement**

#### Gestion d'échec (Rollback automatique)```bash

Si la création du compte échoue, l'utilisateur n'est **pas créé** pour maintenir la cohérence.cp .env.example .env

# Configurer les bases de données dans .env

#### Suppression en cascade```

```bash

# Supprimer un utilisateur → supprime automatiquement tous ses comptes### **3. Bases de données**

curl -X DELETE http://localhost:8001/api/users/3```sql

CREATE DATABASE tp3_users;

# Réponse:CREATE DATABASE tp3_accounts;

{```

  "message": "User deleted successfully",

  "user_id": 3,### **4. Migrations**

  "accounts_deleted": [3, 4]```bash

}php artisan migrate --database=mysql_users

```php artisan migrate --database=mysql_accounts

```

### Requêtes transverses

### **5. Démarrage serveur**

#### Profil utilisateur complet```bash

```bashphp artisan serve

curl http://localhost:8003/api/users/1/profile```



# Réponse agrégée:## Exemples d'utilisation

{

  "user_id": 1,### **Créer utilisateur + compte bancaire (atomique)**

  "nom_complet": "John Doe",```bash

  "email": "john@example.com",POST /api/user-accounts

  "comptes_bancaires": [...],{

  "nombre_comptes": 2,  "first_name": "John",

  "solde_total": 3500.50  "last_name": "Doe",

}  "email": "john@example.com",

```  "phone": "0123456789"

}

---```



## 🧪 Tests avec Postman### **Récupérer utilisateur complet**

```bash

### Import de la collectionGET /api/gateway/users/1/complete

# Retourne: utilisateur + comptes + statistiques

1. **Importer la collection** : `postman-collection.json````

2. **Importer l'environnement** : `postman-environment.json`  

3. **Activer l'environnement** "Microservices Local Environment"### **Statistiques globales**

```bash

### Scénarios de test suggérésGET /api/gateway/stats

# Retourne: nb utilisateurs, comptes, soldes moyens, etc.

1. **Workflow complet**```

   - Créer un utilisateur (vérifie la création automatique du compte)

   - Consulter le profil complet via l'orchestrateur## Bénéfices observés

   - Supprimer l'utilisateur (vérifie la suppression en cascade)

### **Séparation des responsabilités**

2. **Test de cohérence**- Chaque service a sa propre base de données

   - Tenter de créer un utilisateur avec des données invalides- Évolutivité indépendante des services

   - Vérifier les rollbacks automatiques- Déploiement séparé possible



3. **Requêtes transverses**### **Résilience**

   - Consulter tous les profils utilisateurs- Échec d'un service n'impacte pas les autres

   - Agréger les données de multiple services- Transactions atomiques garanties

- Rollback automatique en cas d'erreur

---

### **Performance**

## 🛠️ Développement- Requêtes parallèles dans l'API Gateway

- Optimisation spécifique par service

### Structure du projet- Cache indépendant par service



```### **Observabilité**

tp1/- Logging détaillé des transactions

├── docker-compose.yml           # Orchestration des services- Monitoring de santé des services

├── user-service/               # Microservice utilisateurs- Métriques agrégées

│   ├── Dockerfile

│   └── public/index.php## Tests

├── account-service/            # Microservice comptes

│   ├── Dockerfile  ### **Santé du système**

│   └── public/index.php```bash

├── orchestrator-service/       # Service d'orchestrationGET /api/gateway/health

│   ├── Dockerfile```

│   └── public/index.php

├── api-gateway/               # API Gateway### **Validation des transactions atomiques**

├── postman-collection.json   # Collection de tests1. Créer un utilisateur via `/api/user-accounts`

└── POSTMAN_GUIDE.md          # Guide détaillé2. Vérifier que le compte bancaire est créé automatiquement

```3. Tester l'échec de création de compte → rollback utilisateur



### Logs et debugging## Architecture Clean



```bashLe projet conserve les principes **Clean Architecture** :

# Voir les logs de tous les services- **Domain** : Entités métier, règles business

docker-compose logs -f- **Application** : Use cases, Commands/Queries, Services

- **Persistence** : Repositories Eloquent

# Logs d'un service spécifique- **Presentation** : Contrôleurs API

docker-compose logs -f user-service

docker-compose logs -f account-service## Technologies



# Redémarrer un service- **Framework** : Laravel 11

docker-compose restart user-service- **Architecture** : Microservices + CQRS + Clean Architecture

```- **Communication** : HTTP REST

- **Base de données** : MySQL (séparées par service)

### Développement local- **Patterns** : Saga, API Gateway, Mediator, Repository


```bash
# Reconstruire après modifications
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Développement incrémental
docker-compose build user-service
docker-compose up -d
```

---

## 📈 Patterns implémentés

### 🎯 **Saga Pattern**
- Orchestration des transactions distribuées
- Rollback automatique en cas d'échec
- Cohérence des données garantie

### 🏗️ **Service Composition**
- Agrégation de données via l'Orchestrator
- Requêtes transverses optimisées
- Séparation des responsabilités

### 🔗 **API Gateway Pattern**
- Point d'entrée unifié (préparé)
- Routage vers les microservices
- Gestion centralisée des requêtes

### 📦 **Database per Service**
- Chaque service a sa propre base de données
- Isolation complète des données
- Indépendance de déploiement

---

## 🔧 Configuration avancée

### Variables d'environnement

| Variable | Valeur par défaut | Description |
|----------|-------------------|-------------|
| `USER_SERVICE_PORT` | 8001 | Port du service utilisateurs |
| `ACCOUNT_SERVICE_PORT` | 8002 | Port du service comptes |
| `ORCHESTRATOR_PORT` | 8003 | Port de l'orchestrateur |
| `API_GATEWAY_PORT` | 8000 | Port de l'API Gateway |

### Persistence des données

Les données sont stockées dans des fichiers JSON dans chaque container :
- User Service : `/var/www/html/data/users.json`
- Account Service : `/var/www/html/data/accounts.json`

⚠️ **Note** : Les données sont perdues lors du redémarrage des containers. Pour une persistence complète, configurer des volumes Docker.

---

## 🎓 Contexte pédagogique

### Évolution du projet

1. **Phase 1** : Architecture monolithique Laravel
2. **Phase 2** : Migration vers Clean Architecture  
3. **Phase 3** : Implémentation CQRS/MediatR
4. **Phase 4** : **Microservices avec Docker** ← Version actuelle

### Compétences démontrées

- ✅ Conception d'architecture distribuée
- ✅ Gestion de la cohérence dans un système distribué
- ✅ Communication inter-services
- ✅ Patterns microservices (Saga, Service Composition)
- ✅ Containerisation avec Docker
- ✅ Tests d'intégration et documentation

---

## 👥 Équipe

**Développement** : SheiEthan  
**Branch** : `microservices`  
**Repository** : [Tp1-Architecture-Logicielle](https://github.com/SheiEthan/Tp1-Architecture-Logicielle)

---

## 📞 Support

En cas de problème :

1. **Vérifier les logs** : `docker-compose logs -f`
2. **Redémarrer les services** : `docker-compose restart`
3. **Reconstruction complète** : `docker-compose down && docker-compose up --build -d`
4. **Consulter le guide Postman** : `POSTMAN_GUIDE.md`

---

## 🏆 Résultats

✅ **Architecture microservices fonctionnelle**  
✅ **Transactions distribuées cohérentes**  
✅ **Communication inter-services fiable**  
✅ **Requêtes transverses optimisées**  
✅ **Tests complets avec Postman**  
✅ **Documentation technique complète**

**🎯 Objectif TP atteint avec succès !**
