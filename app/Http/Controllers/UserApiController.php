<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserApiController extends Controller
{
    private UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->listUsers());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'nullable|string|max:30',
        ]);

        $user = $this->service->createUser($data);
        return response()->json($user, Response::HTTP_CREATED);
    }

    public function show($id): JsonResponse
    {
        $user = $this->service->getUser((int)$id);
        if (!$user) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($user);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name'  => 'sometimes|required|string|max:255',
            'email'      => 'sometimes|required|email|unique:users,email,'.$id,
            'phone'      => 'nullable|string|max:30',
        ]);

        $user = $this->service->updateUser((int)$id, $data);
        if (!$user) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json($user);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->service->deleteUser((int)$id);
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
