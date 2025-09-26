<?php

namespace Presentation;

use Application\UseCases\CreateUserApi;
use Application\UseCases\GetUserApi;
use Application\UseCases\UpdateUserApi;
use Application\UseCases\DeleteUserApi;
use Application\DTO\UserApiDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserApiController
{
    private CreateUserApi $createUserApi;
    private GetUserApi $getUserApi;
    private UpdateUserApi $updateUserApi;
    private DeleteUserApi $deleteUserApi;

    public function __construct(
        CreateUserApi $createUserApi,
        GetUserApi $getUserApi,
        UpdateUserApi $updateUserApi,
        DeleteUserApi $deleteUserApi
    ) {
        $this->createUserApi = $createUserApi;
        $this->getUserApi = $getUserApi;
        $this->updateUserApi = $updateUserApi;
        $this->deleteUserApi = $deleteUserApi;
    }

    public function store(Request $request): JsonResponse
    {
        $dto = new UserApiDTO(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('email'),
            $request->input('phone'),
            '' // le rôle sera assigné par le use case
        );
        $user = $this->createUserApi->execute($dto);
        return response()->json($user, 201);
    }

    public function show($id): JsonResponse
    {
        $user = $this->getUserApi->execute((int)$id);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($user);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $dto = new UserApiDTO(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('email'),
            $request->input('phone'),
            $request->input('role', '')
        );
        $user = $this->updateUserApi->execute((int)$id, $dto);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($user);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->deleteUserApi->execute((int)$id);
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(null, 204);
    }
}
