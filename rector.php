<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPhpSets()
    ->withPreparedSets(codeQuality: true, codingStyle: true, instanceOf: true, deadCode: true, naming: true, typeDeclarations: true)
    ->withImportNames()
    ->withPaths([
        __DIR__ . '/src',
    ])

;
