<?php

namespace Application\Mediator;

class Mediator
{
    private array $handlers = [];

    public function register(string $type, $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function send($request, ...$args)
    {
        $type = is_object($request) ? get_class($request) : $request;
        if (!isset($this->handlers[$type])) {
            throw new \Exception("No handler registered for $type");
        }
        return $this->handlers[$type]->handle(...$args);
    }
}
