<?php

namespace Presentation;

use Application\DTO\UserApiDTO;
use Application\Mediator\Mediator;
use Application\Command\CreateUserApiCommand;
use Application\Command\UpdateUserApiCommand;
use Application\Command\DeleteUserApiCommand;
use Application\Query\GetUserApiQuery;
use Application\Query\ListUserApiQuery;

class UserApiController
{
    private Mediator $mediator;

    public function __construct()
    {
        $this->mediator = app(\Application\Mediator\Mediator::class);

        // Enregistrer les handlers
        $repository = app(\Application\Ports\IUserApiRepository::class);
        $this->mediator->register(ListUserApiQuery::class, new ListUserApiQuery($repository));
        $this->mediator->register(CreateUserApiCommand::class, new CreateUserApiCommand($repository));
        $this->mediator->register(GetUserApiQuery::class, new GetUserApiQuery($repository));
        $this->mediator->register(UpdateUserApiCommand::class, new UpdateUserApiCommand($repository));
        $this->mediator->register(DeleteUserApiCommand::class, new DeleteUserApiCommand($repository));
    }

    public function index()
    {
        $users = $this->mediator->send(ListUserApiQuery::class);
        return response()->json($users);
    }

    public function store($request)
    {
        try {
            $dto = new UserApiDTO(
                $request->input('first_name'),
                $request->input('last_name'),
                $request->input('email'),
                $request->input('phone'),
                ''
            );
            $user = $this->mediator->send(CreateUserApiCommand::class, $dto);

            // Dans ce microservice, on ne crée PAS automatiquement de compte
            // C'est l'orchestrateur qui s'en charge
            return response()->json([
                'user' => $user,
                'message' => 'Utilisateur créé avec succès'
            ], 201);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'email_unique')) {
                return response()->json([
                    'error' => 'Cet email est déjà utilisé par un autre utilisateur',
                    'code' => 'EMAIL_ALREADY_EXISTS'
                ], 409);
            }

            return response()->json([
                'error' => 'Erreur lors de la création de l\'utilisateur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $user = $this->mediator->send(GetUserApiQuery::class, (int)$id);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($user);
    }

    public function update($request, $id)
    {
        $dto = new UserApiDTO(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('email'),
            $request->input('phone'),
            $request->input('role', '')
        );
        $user = $this->mediator->send(UpdateUserApiCommand::class, (int)$id, $dto);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($user);
    }

    public function destroy($id)
    {
        try {
            // Dans ce microservice, on supprime SEULEMENT l'utilisateur
            // C'est l'orchestrateur qui gère la suppression des comptes
            $deleted = $this->mediator->send(DeleteUserApiCommand::class, (int)$id);
            if (!$deleted) {
                return response()->json(['message' => 'Not found'], 404);
            }

            return response()->json([
                'message' => 'Utilisateur supprimé avec succès'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erreur lors de la suppression',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}