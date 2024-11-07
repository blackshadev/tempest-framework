<?php

declare(strict_types=1);

namespace Tempest\Http\Routing\Construction;

/**
 * @internal
 */
final class RoutingTree
{
    /** @var array<string, RouteTreeNode> */
    private array $roots;

    /** @var array<string, RouteTreeNode> */
    private array $insertCache;

    public function __construct()
    {
        $this->roots = [];
        $this->insertCache = [];
    }

    public function add(MarkedRoute $markedRoute): void
    {
        $method = $markedRoute->route->method;

        $segments = PathSegments::createFromRoute($markedRoute->route);
        $routePath = $segments->segmentsPath();

        if (isset($this->insertCache[$method->value]) && str_starts_with($routePath, $this->insertCache[$method->value]->fullPath)) {
            $segments = $segments->shorten($this->insertCache[$method->value]->fullPath);

            $traversedNodes = $this->insertCache[$method->value]->addPath($segments, $markedRoute);

            $this->insertCache[$method->value] = $traversedNodes[2] ?? $this->insertCache[$method->value] ?? null;
            return;
        }

        // Find the root tree node based on HTTP method
        $root = $this->roots[$method->value] ??= RouteTreeNode::createRootRoute();

        // Add path to tree using recursion
        $traversedNodes = $root->addPath($segments, $markedRoute);

        $this->insertCache[$method->value] = $traversedNodes[2] ?? $this->insertCache[$method->value] ?? null;
    }

    /** @return array<string, string> */
    public function toMatchingRegexes(): array
    {
        return array_map(static fn (RouteTreeNode $node) => "#{$node->toRegex()}#", $this->roots);
    }
}
