<?php
declare(strict_types = 1);

use Composer\Semver\VersionParser;

// Bypass standard composer API because of collision with libraries bundled inside phpstan.phar
$installed = require __DIR__ . '/../../vendor/composer/installed.php';
$versionParser = new VersionParser();
$isInstalled = function (string $packageName, string $versionConstraint) use ($versionParser, $installed): bool {
    $constraint = $versionParser->parseConstraints($versionConstraint);
    $installedVersion = $installed['versions'][$packageName]['pretty_version']; // @phpstan-ignore offsetAccess.nonOffsetAccessible,offsetAccess.nonOffsetAccessible,offsetAccess.nonOffsetAccessible
    assert(is_string($installedVersion));
    $provided = $versionParser->parseConstraints($installedVersion);
    return $provided->matches($constraint);
};

$config = ['parameters' => ['ignoreErrors' => []]];

if ($isInstalled('psr/log', '<2.0')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#3 \\$context \\(array\\<string, mixed\\>\\) of method NepadaTests\\\\MessageBusNette\\\\Fixtures\\\\TestLogger\\:\\:log\\(\\) should be contravariant with parameter \\$context \\(array(\\<mixed\\>)?\\) of method Psr\\\\Log\\\\LoggerInterface\\:\\:log\\(\\)$#',
        'identifier' => 'method.childParameterType',
        'count' => 2,
        'path' => __DIR__ . '/../../tests/MessageBusNette/Fixtures/TestLogger.php',
    ];
} else {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#3 \\$context \\(array\\<string, mixed\\>\\) of method NepadaTests\\\\MessageBusNette\\\\Fixtures\\\\TestLogger\\:\\:log\\(\\) should be contravariant with parameter \\$context \\(array<mixed>\\) of method Psr\\\\Log\\\\LoggerTrait\\:\\:log\\(\\)$#',
        'identifier' => 'method.childParameterType',
        'count' => 1,
        'path' => __DIR__ . '/../../tests/MessageBusNette/Fixtures/TestLogger.php',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#3 \\$context \\(array\\<string, mixed\\>\\) of method NepadaTests\\\\MessageBusNette\\\\Fixtures\\\\TestLogger\\:\\:log\\(\\) should be contravariant with parameter \\$context \\(array(\\<mixed\\>)?\\) of method Psr\\\\Log\\\\AbstractLogger\\:\\:log\\(\\)$#',
        'identifier' => 'method.childParameterType',
        'count' => 1,
        'path' => __DIR__ . '/../../tests/MessageBusNette/Fixtures/TestLogger.php',
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#3 \\$context \\(array\\<string, mixed\\>\\) of method NepadaTests\\\\MessageBusNette\\\\Fixtures\\\\TestLogger\\:\\:log\\(\\) should be contravariant with parameter \\$context \\(array\\<mixed\\>\\) of method Psr\\\\Log\\\\LoggerInterface\\:\\:log\\(\\)$#',
        'identifier' => 'method.childParameterType',
        'count' => 1,
        'path' => __DIR__ . '/../../tests/MessageBusNette/Fixtures/TestLogger.php',
    ];
}

if ($isInstalled('doctrine/persistence', '<3.1')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Parameter \\#1 \\$classes of method Doctrine\\\\ORM\\\\Tools\\\\SchemaTool\\:\\:getCreateSchemaSql\\(\\) expects list\\<Doctrine\\\\ORM\\\\Mapping\\\\ClassMetadata\\>, array\\<Doctrine\\\\ORM\\\\Mapping\\\\ClassMetadata\\> given\\.$#',
        'identifier' => 'argument.type',
        'count' => 1,
        'path' => __DIR__ . '/../../tests/MessageBusNette/IntegrationTest.phpt',
    ];
}

if (! $isInstalled('doctrine/orm', '<3.4')) {
    $config['parameters']['ignoreErrors'][] = [
        'message' => '#^Call to function method_exists\\(\\) with Doctrine\\\\ORM\\\\Configuration and \'enableNativeLazy.*\' will always evaluate to true\\.$#',
        'path' => __DIR__ . '/../../tests/MessageBusNette/DI/ConfiguratorFactory.php',
        'count' => 1,
    ];
}

return $config;
