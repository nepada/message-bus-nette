<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use Nepada\MessageBus\StaticAnalysis\StaticAnalysisFailedException;
use NepadaTests\TestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';


/**
 * @testCase
 */
class BleedingEdgeTest extends TestCase
{

    public function testNotReadOnlyMessageClassAllowedByDefault(): void
    {
        Assert::noError(
            function (): void {
                $this->createContainer('mutableMessage.neon');
            },
        );
    }

    public function testNotReadOnlyMessageClassNotAllowedInBleedingEdge(): void
    {
        Assert::error(
            function (): void {
                $this->createContainer('mutableMessage.neon', true);
            },
            StaticAnalysisFailedException::class,
            'Static analysis failed for class "NepadaTests\MessageBusNette\Fixtures\Extra\MutableCommand": Property shouldFail must be readonly',
        );
    }

    private function createContainer(string $configFile, ?bool $bleedingEdge = null): Nette\DI\Container
    {
        return (new ConfiguratorFactory())->create($configFile, $bleedingEdge)->createContainer();
    }

}


(new BleedingEdgeTest())->run();
