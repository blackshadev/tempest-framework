<?php

declare(strict_types=1);

namespace Tempest\Http;

use Tempest\Http\Routing\MarkedRoute;
use Tempest\Http\Routing\RoutingTree;
use Tempest\Reflection\MethodReflector;

final class RouteConfig
{
    /** @var string The mark to give the next route in the matching Regex */
    private string $regexMark = 'a';

    /** @var array<string, string> */
    public array $matchingRegexes = [];

    public RoutingTree $routingTree;

    public function __construct(
        /** @var array<string, array<string, \Tempest\Http\Route>> */
        public array $staticRoutes = [],
        /** @var array<string, array<string, \Tempest\Http\Route>> */
        public array $dynamicRoutes = [],
    ) {
        $this->routingTree = new RoutingTree();
    }

    public function addRoute(MethodReflector $handler, Route $route): self
    {
        $route->setHandler($handler);

        if ($route->isDynamic) {
            $this->regexMark = str_increment($this->regexMark);
            $this->dynamicRoutes[$route->method->value][$this->regexMark] = $route;

            $this->routingTree->add(
                new MarkedRoute(
                    mark: $this->regexMark,
                    route: $route,
                )
            );

            $this->matchingRegexes[$route->method->value] = $this->routingTree->regexForMethod($route->method);
        } else {
            $uriWithTrailingSlash = rtrim($route->uri, '/');

            $this->staticRoutes[$route->method->value][$uriWithTrailingSlash] = $route;
            $this->staticRoutes[$route->method->value][$uriWithTrailingSlash . '/'] = $route;
        }

        return $this;
    }

    /**
     * Build one big regex for matching request URIs.
     * See https://github.com/tempestphp/tempest-framework/pull/175 for the details
     */
    private function addToMatchingRegex(Route $route, string $routeMark): void
    {
        // Each route, say "/posts/{postId}", which would have the regex "/posts/[^/]+", is marked.
        // e.g "/posts/[^/]+ (*MARK:a)".
        // This mark can then be used to find the matched route via a hashmap-lookup.
        $routeRegexPart = "{$route->matchingRegex} (*" . GenericRouter::REGEX_MARK_TOKEN . ":{$routeMark})";

        if (! array_key_exists($route->method->value, $this->matchingRegexes)) {
            // initialize matching regex for method
            $this->matchingRegexes[$route->method->value] = "#^(?|{$routeRegexPart})$#x";

            return;
        }

        // insert regex part of this route into the matching group of the regex for the method
        $this->matchingRegexes[$route->method->value] = substr_replace($this->matchingRegexes[$route->method->value], "|{$routeRegexPart}", -4, 0);
    }

    public function __serialize(): array
    {
        return [
            'staticRoutes' => serialize($this->staticRoutes),
            'dynamicRoutes' => serialize($this->dynamicRoutes),
            'matchingRegexes' => serialize($this->matchingRegexes),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->staticRoutes = unserialize($data['staticRoutes']);
        $this->dynamicRoutes = unserialize($data['dynamicRoutes']);
        $this->matchingRegexes = unserialize($data['matchingRegexes']);
    }
}
