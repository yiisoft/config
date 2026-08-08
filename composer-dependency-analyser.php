<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // This package is a Composer plugin: "composer/composer" classes are always available at runtime
    // because the plugin executes inside a running Composer process, so it must stay a dev dependency
    // (used for type checking / tests) rather than move to "require".
    ->ignoreErrorsOnPackage('composer/composer', [ErrorType::DEV_DEPENDENCY_IN_PROD]);
