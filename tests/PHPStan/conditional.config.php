<?php
declare(strict_types = 1);

use Composer\Semver\VersionParser;

$config = ['parameters' => ['ignoreErrors' => []]];

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

if ($isInstalled('nette/schema', '>=1.3.4')) {
    // Type pre-validated
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~^Parameter \\#1 \\$handler of method Nette\\\\Schema\\\\Elements\\\\Structure::assert\\(\\) expects callable\\(mixed\\): bool, Closure\\(array\\): bool given\\.$~',
        'path' => __DIR__ . '/../../src/MessageBusNette/DI/MessageBusExtension.php',
        'count' => 1,
    ];
    $config['parameters']['ignoreErrors'][] = [
        'message' => '~^Parameter \\#1 \\$handler of method Nette\\\\Schema\\\\Elements\\\\Type::assert\\(\\) expects callable\\(mixed\\): bool, Closure\\(string\\): bool given\\.$~',
        'path' => __DIR__ . '/../../src/MessageBusNette/DI/MessageBusExtension.php',
        'count' => 1,
    ];
}

return $config;
