<?php

declare(strict_types=1);

namespace Tests\Tempest\Integration\View;

use Tempest\Http\Status;
use function Tempest\uri;
use function Tempest\view;
use Tempest\View\GenericView;
use Tests\Tempest\Fixtures\Controllers\TestController;
use Tests\Tempest\Fixtures\Views\ViewModel;
use Tests\Tempest\Integration\FrameworkIntegrationTestCase;

/**
 * @internal
 * @small
 */
class ViewTest extends FrameworkIntegrationTestCase
{
    public function test_render()
    {
        $view = new GenericView(
            'Views/overview.php',
            params: [
                'name' => 'Brent',
            ],
        );

        $html = $view->render();

        $expected = <<<HTML
<html lang="en">
<head>
    <title></title>
</head>
<body>Hello Brent!</body>
</html>
HTML;

        $this->assertEquals($expected, $html);
    }

    public function test_render_with_view_model()
    {
        $view = new ViewModel('Brent');

        $html = $view->render();

        $expected = <<<HTML

ViewModel Brent, 2020-01-01
HTML;

        $this->assertEquals($expected, $html);
    }

    public function test_with_view_function()
    {
        $view = view('Views/overview.php')->data(
            name: 'Brent',
        );

        $html = $view->render();

        $expected = <<<HTML
<html lang="en">
<head>
    <title></title>
</head>
<body>Hello Brent!</body>
</html>
HTML;

        $this->assertEquals($expected, $html);
    }

    public function test_raw_and_escaping()
    {
        $html = view('Views/rawAndEscaping.php')->data(
            property: '<h1>hi</h1>',
        )->render();

        $expected = <<<HTML
        &lt;h1&gt;hi&lt;/h1&gt;<h1>hi</h1>
        HTML;

        $this->assertSame(trim($expected), trim($html));
    }

    public function test_extends_parameters()
    {
        $html = view('Views/extendsWithVariables.php')->render();

        $this->assertStringContainsString('<title>Test</title>', $html);
        $this->assertStringContainsString('<h1>Hello</h1>', $html);
    }

    public function test_named_slots()
    {
        $html = view('Views/extendsWithNamedSlot.php')->render();

        $this->assertStringContainsString(
            needle: <<<HTML
            <div class="defaultSlot"><h1>beginning</h1>
            <p>in between</p>
            <p>default slot</p></div>
            HTML,
            haystack: $html
        );

        $this->assertStringContainsString(
            needle: <<<HTML
            <div class="namedSlot"><h1>named slot</h1></div>
            HTML,
            haystack: $html
        );

        $this->assertStringContainsString(
            needle: <<<HTML
            <div class="namedSlot2"><h1>named slot 2</h1></div>
            HTML,
            haystack: $html
        );
    }

    public function test_include_parameters()
    {
        $html = view('Views/include-parent.php')
            ->data(prop: 'test')
            ->render();

        $expected = <<<HTML
        parent test 
        child test
        HTML;

        $this->assertSame(trim($expected), trim($html));
    }

    public function test_view_model_with_response_data()
    {
        $this->http
            ->get(uri([TestController::class, 'viewModelWithResponseData']))
            ->assertHasHeader('x-from-viewmodel')
            ->assertStatus(Status::CREATED);
    }
}
