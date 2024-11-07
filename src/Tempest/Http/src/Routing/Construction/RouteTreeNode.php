<?php

declare(strict_types=1);

namespace Tempest\Http\Routing\Construction;

use Tempest\Http\Route;

/**
 * @internal
 */
final class RouteTreeNode
{
    /** @var array<string, RouteTreeNode> */
    private array $staticPaths = [];

    /** @var array<string, RouteTreeNode> */
    private array $dynamicPaths = [];

    private ?MarkedRoute $leaf = null;

    private function __construct(
        public readonly string $fullPath,
        public readonly RouteTreeNodeType $type,
        public readonly ?string $segment = null
    ) {
    }

    public static function createRootRoute(): self
    {
        return new self('/', RouteTreeNodeType::Root);
    }

    public function createDynamicRouteNode(string $fullPath, string $regex): self
    {
        return new self($fullPath, RouteTreeNodeType::Dynamic, $regex);
    }

    public function createStaticRouteNode(string $fullPath, string $name): self
    {
        return new self($fullPath, RouteTreeNodeType::Static, $name);
    }

    public function addPath(PathSegments $pathSegments, MarkedRoute $markedRoute): array
    {
        // If path segments is empty this node should target to given marked route
        if ($pathSegments->isDone()) {
            if ($this->leaf !== null) {
                throw new DuplicateRouteException($markedRoute->route);
            }

            $this->leaf = $markedRoute;

            return [$this];
        }

        // Removes the first element of the pathSegments and use it to determine the next routing node
        $currentPathSegment = $pathSegments->next();

        // Find or create the next node to recurse into
        if ($currentPathSegment->isDynamic) {
            $node = $this->dynamicPaths[$currentPathSegment->segment] ??= $this->createDynamicRouteNode($pathSegments->traversedPath(), $currentPathSegment->segment);
        } else {
            $node = $this->staticPaths[$currentPathSegment->segment] ??= $this->createStaticRouteNode($pathSegments->traversedPath(), $currentPathSegment->segment);
        }

        // Recurse into the newly created node to add the remainder of the path segments
        $traversedNodes = $node->addPath($pathSegments, $markedRoute);
        $traversedNodes[] = $this;

        return $traversedNodes;
    }

    private static function convertDynamicSegmentToRegex(string $uriPart): string
    {
        $regex = '#\{'. Route::ROUTE_PARAM_NAME_REGEX . Route::ROUTE_PARAM_CUSTOM_REGEX .'\}#';

        return preg_replace_callback(
            $regex,
            static fn ($matches) => trim($matches[2] ?? Route::DEFAULT_MATCHING_GROUP),
            $uriPart,
        );
    }

    /**
     * Return the matching regex of this path and it's children by means of recursion
     */
    public function toRegex(): string
    {
        $regexp = $this->regexSegment();

        if ($this->staticPaths !== [] || $this->dynamicPaths !== []) {
            // The regex uses "Branch reset group" to match different available paths.
            // two available routes /a and /b will create the regex (?|a|b)
            $regexp .= "(?";

            // Add static route alteration
            foreach ($this->staticPaths as $path) {
                $regexp .= '|' . $path->toRegex();
            }

            // Add dynamic route alteration, for example routes {id:\d} and {id:\w} will create the regex (?|(\d)|(\w)).
            // Both these parameter matches will end up on the same index in the matches array.
            foreach ($this->dynamicPaths as $path) {
                $regexp .= '|' . $path->toRegex();
            }

            // Add a leaf alteration with an optional slash and end of line match `$`.
            // The `(*MARK:x)` is a marker which when this regex is matched will cause the matches array to contain
            // a key `"MARK"` with value `"x"`, it is used to track which route has been matched
            if ($this->leaf !== null) {
                $regexp .= '|\/?$(*' . MarkedRoute::REGEX_MARK_TOKEN . ':' . $this->leaf->mark . ')';
            }

            $regexp .= ")";
        } elseif ($this->leaf !== null) {
            // Add a singular leaf regex without alteration
            $regexp .= '\/?$(*' . MarkedRoute::REGEX_MARK_TOKEN . ':' . $this->leaf->mark . ')';
        }

        return $regexp;
    }

    /**
     * Translates the only current node segment into regex. This does not recurse into it's child nodes.
     */
    private function regexSegment(): string
    {
        return match($this->type) {
            RouteTreeNodeType::Root => '^',
            RouteTreeNodeType::Static => "/{$this->segment}",
            RouteTreeNodeType::Dynamic => '/(' . $this->segment . ')',
        };
    }
}
