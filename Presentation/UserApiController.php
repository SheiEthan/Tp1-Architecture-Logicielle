<?php

namespace Presentation;

use Application\DTO\UserApiDTO;
use Application\Mediator\Mediator;
use Application\Command\CreateUserApiCommand;
use Application\Command\UpdateUserApiCommand;
use Application\Command\DeleteUserApiCommand;
use Application\Query\GetUserApiQuery;
use Application\Query\ListUserApiQuery;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserApiController
{
    private Mediator $mediator;

    public function __construct(Mediator $mediator)
    {
        $this->mediator = $mediator;
        // Enregistrement des handlers
        $this->mediator->register(ListUserApiQuery::class, new ListUserApiQuery(app(\Application\Ports\IUserApiRepository::class)));
        $this->mediator->register(CreateUserApiCommand::class, new CreateUserApiCommand(app(\Application\Ports\IUserApiRepository::class)));
        $this->mediator->register(GetUserApiQuery::class, new GetUserApiQuery(app(\Application\Ports\IUserApiRepository::class)));
        $this->mediator->register(UpdateUserApiCommand::class, new UpdateUserApiCommand(app(\Application\Ports\IUserApiRepository::class)));
        $this->mediator->register(DeleteUserApiCommand::class, new DeleteUserApiCommand(app(\Application\Ports\IUserApiRepository::class)));
    }

    public function index(): JsonResponse
    {
    $users = $this->mediator->send(ListUserApiQuery::class);
    return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $dto = new UserApiDTO(
            $request->input('first_name'),
            $request->input('last_name'),
            $request->input('email'),
            $request->input('phone'),
            ''
        );
        $user = $this->mediator->send(CreateUserApiCommand::class, $dto);
        return response()->json($user, 201);
    }

    public function show($id): JsonResponse
    {
        $user = $this->mediator->send(GetUserApiQuery::class, (int)$id);
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
        $user = $this->mediator->send(UpdateUserApiCommand::class, (int)$id, $dto);
        if (!$user) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($user);
    }

    public function destroy($id): JsonResponse
    {
        $deleted = $this->mediator->send(DeleteUserApiCommand::class, (int)$id);
        if (!$deleted) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(null, 204);
    }
}
