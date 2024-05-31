<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\View;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use function Tempest\view;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 * @small
 */
class ViewComponentTest extends FrameworkIntegrationTestCase
{
    #[DataProvider('view_components')]
    public function test_view_components(string $component, string $rendered): void
    {
        $this->assertSame(
            expected: $rendered,
            actual: view($component)->render(),
        );
    }

    public function test_view_component_with_php_code(): void
    {
        $this->assertSame(
            expected: '<div foo="hello" bar="barValue"></div>',
            actual: view(<<<'HTML'
            <x-my :foo="$this->input" bar="barValue"></x-my>',
            HTML)->data(input: 'hello')->render(),
        );
    }

    public static function view_components(): Generator
    {
        yield [
            '<x-my>body</x-my>',
            '<div>body</div>',
        ];

        yield [
            '<x-my>body</x-my><x-my>body</x-my>',
            '<div>body</div><div>body</div>',
        ];

        yield [
            '<x-my></x-my>',
            '<div></div>',
        ];

        yield [
            '<x-my foo="fooValue" bar="barValue">body</x-my>',
            '<div foo="fooValue" bar="barValue">body</div>',
        ];

        yield [
            <<<'HTML'
            <x-my>
            body
            
            multiline
            </x-my>
            HTML,
            <<<'HTML'
            <div>
            body
            
            multiline
            </div>
            HTML,
        ];
    }
}
