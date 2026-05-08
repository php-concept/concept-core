<?php declare(strict_types=1);

namespace Tests\Core\Components\View;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\View;
use PHPUnit\Framework\TestCase;
use Twig\Environment as Twig;

final class ViewTest extends TestCase
{
    public function testRenderAppendsTwigExtensionWhenMissing(): void
    {
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('home' . ViewInterface::DEFAULT_EXTENSION, ['name' => 'Ada'])
            ->willReturn('<h1>Ada</h1>');

        $view = new View($twig);

        self::assertSame('<h1>Ada</h1>', $view->render('home', ['name' => 'Ada']));
    }

    public function testRenderKeepsTwigExtensionWhenProvided(): void
    {
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('dashboard.twig', ['x' => 1])
            ->willReturn('ok');

        $view = new View($twig);

        self::assertSame('ok', $view->render('dashboard.twig', ['x' => 1]));
    }
}
