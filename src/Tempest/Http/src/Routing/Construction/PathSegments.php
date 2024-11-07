<?php

declare(strict_types=1);

namespace Tempest\Http\Routing\Construction;

use Tempest\Http\Route;

final class PathSegments
{
    /** @param PathSegment[] $traversed */
    public array $traversed = [];

    /** @param PathSegment[] $segments */
    public function __construct(
        public array $segments,
    ) {}

    public static function createFromRoute(Route $route): self
    {
        $parts = explode('/', $route->uri);

        $nonEmptyParts = array_filter($parts, static fn (string $value) => $value !== '');

        return new self(array_map([PathSegment::class, 'createFromUriPart'], $nonEmptyParts));
    }

    public function next(): PathSegment
    {
        $segment = array_shift($this->segments);
        $this->traversed[] = $segment;

        return $segment;
    }

    public function segmentsPath(): string
    {
        return '/' . implode('/', array_map(static fn (PathSegment $segment) => $segment->segment, $this->segments));
    }

    public function traversedPath(): string
    {
        return '/' . implode('/', array_map(static fn (PathSegment $segment) => $segment->segment, $this->traversed));
    }

    public function isDone(): bool
    {
        return count($this->segments) === 0;
    }

    public function shorten(string $fullPath): self
    {
        if (str_starts_with($fullPath, '/')) {
            $fullPath = substr($fullPath, 1);
        }

        $toRemove = mb_substr_count($fullPath, '/');
        $segments = $this->segments;
        array_splice($segments, 0, $toRemove);

        return new self($segments);
    }
}