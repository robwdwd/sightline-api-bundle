<?php
/*
 * This file is part of the Sightline API Bundle.
 *
 * Copyright 2022-2024 Robert Woodward
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Robwdwd\SightlineApiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Sightline API Symfony Bundle.
 */
class SightlineApiBundle extends Bundle
{
    /**
     * Get path for bundle.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
