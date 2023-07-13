<?php

namespace Tests\Tempest\View;

use App\Views\ViewModel;
use Tempest\AppConfig;
use Tempest\View\GenericView;
use Tests\Tempest\TestCase;

class ViewTest extends TestCase
{
    /** @test */
    public function test_render()
    {
        $appConfig = $this->container->get(AppConfig::class);

        $view = new GenericView(
            'Views/overview.php',
            params: [
                'name' => 'Brent',
            ],
        );

        $html = $view->render($appConfig);

        $expected = <<<HTML
<html lang="en">
<head>
    <title></title>
</head>
<body>
Hello Brent!</body>
</html>
HTML;

        $this->assertEquals($expected, $html);
    }

    /** @test */
    public function test_render_with_view_model()
    {
        $appConfig = $this->container->get(AppConfig::class);

        $view = new ViewModel('Brent');

        $html = $view->render($appConfig);

        $expected = <<<HTML

ViewModel Brent, 2020-01-01
HTML;

        $this->assertEquals($expected, $html);
    }

    /** @test */
    public function test_with_view_function()
    {
        $appConfig = $this->container->get(AppConfig::class);

        $view = view('Views/overview.php')->data(
            name: 'Brent',
        );

        $html = $view->render($appConfig);

        $expected = <<<HTML
<html lang="en">
<head>
    <title></title>
</head>
<body>
Hello Brent!</body>
</html>
HTML;

        $this->assertEquals($expected, $html);
    }

    /** @test */
    public function test_raw_and_escaping()
    {
        $html = view('Views/rawAndEscaping.php')->data(
            property: '<h1>hi</h1>',
        )->render($this->container->get(AppConfig::class));

        $expected = <<<HTML
        &lt;h1&gt;hi&lt;/h1&gt;<h1>hi</h1>
        HTML;

        $this->assertSame(trim($expected), trim($html));
    }

    /** @test */
    public function test_extends_parameters()
    {
        $html = view('Views/extendsWithVariables.php')->render($this->container->get(AppConfig::class));

        $this->assertStringContainsString('<title>Test</title>', $html);
    }

    /** @test */
    public function test_include_parameters()
    {
        $html = view('Views/include-parent.php')
            ->data(prop: 'test')
            ->render($this->container->get(AppConfig::class));

        $expected = <<<HTML
        parent test 
        child test
        HTML;

        $this->assertSame(trim($expected), trim($html));
    }
}
