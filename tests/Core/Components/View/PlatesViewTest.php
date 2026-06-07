<?php declare(strict_types=1);

namespace Tests\Core\Components\View;

use Concept\Core\Components\View\PlatesView;
use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

final class PlatesViewTest extends TestCase
{
    public function testRenderPassesViewNameToEngine(): void
    {
        $engine = $this->createMock(Engine::class);
        $engine->expects(self::once())
            ->method('render')
            ->with('dashboard', ['x' => 1])
            ->willReturn('ok');

        $view = new PlatesView($engine, null);

        self::assertSame('ok', $view->render('dashboard', ['x' => 1]));
    }
}
