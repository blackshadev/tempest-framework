<?php

declare(strict_types=1);

namespace Tempest\Http\Routing\Construction;

use Tempest\Http\Route;

final readonly class PathSegment
{
    public function __construct(public bool $isDynamic, public string $segment) {}

    public static function createFromUriPart(string $uriPart): self
    {
        $regex = '#\{'. Route::ROUTE_PARAM_NAME_REGEX . Route::ROUTE_PARAM_CUSTOM_REGEX .'\}#';

        // Translates a path segment like {id} into it's matching regex. Static segments remain the same
        $replacedSegment = preg_replace_callback(
            $regex,
            static fn ($matches) => trim($matches[2] ?? Route::DEFAULT_MATCHING_GROUP),
            $uriPart,
        );

        return new self(
            $uriPart !== $replacedSegment,
            $replacedSegment,
        );
    }
}