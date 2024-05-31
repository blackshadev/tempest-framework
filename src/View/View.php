<?php

declare(strict_types=1);

namespace Tempest\View;

interface View
{
    public function render(): string;

    public function path(string $path): self;

    public function data(...$params): self;

    public function extends(string $path, ...$params): self;

    public function raw(string $name): ?string;

    public function slot(string $name = 'slot'): ?string;

    public function getErrorsFor(string $name): array;

    public function hasErrors(): bool;
}
