<?php
declare(strict_types = 1);

namespace NepadaTests\MessageBusNette\DI;

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;
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

    public function testNotReadOnlyMessageClassAllowedByDefaultInV2(): void
    {
        if (! $this->installedMessageBusVersionSatisfies('2.*')) {
            $this->skip('nepada/message-bus 2.* only');
        }
        Assert::noError(
            function (): void {
                $this->createContainer('mutableMessage.neon');
            },
        );
    }

    public function testNotReadOnlyMessageClassNotAllowedInBleedingEdgeInV2(): void
    {
        if (! $this->installedMessageBusVersionSatisfies('2.*')) {
            $this->skip('nepada/message-bus 2.* only');
        }
        Assert::error(
            function (): void {
                $this->createContainer('mutableMessage.neon', true);
            },
            StaticAnalysisFailedException::class,
            'Static analysis failed for class "NepadaTests\MessageBusNette\Fixtures\Extra\MutableCommand": Property shouldFail must be readonly',
        );
    }

    public function testNotReadOnlyMessageClassNotAllowedByDefaultSinceV3(): void
    {
        if (! $this->installedMessageBusVersionSatisfies('>= 3.0')) {
            $this->skip('nepada/message-bus >= 3.0 only');
        }
        Assert::error(
            function (): void {
                $this->createContainer('mutableMessage.neon', true);
            },
            StaticAnalysisFailedException::class,
            'Static analysis failed for class "NepadaTests\MessageBusNette\Fixtures\Extra\MutableCommand": Class must be readonly',
        );
    }

    private function createContainer(string $configFile, ?bool $bleedingEdge = null): Nette\DI\Container
    {
        return (new ConfiguratorFactory())->create($configFile, $bleedingEdge)->createContainer();
    }

    private function installedMessageBusVersionSatisfies(string $versionConstraint): bool
    {
        return InstalledVersions::satisfies(new VersionParser(), 'nepada/message-bus', $versionConstraint);
    }

}


(new BleedingEdgeTest())->run();
