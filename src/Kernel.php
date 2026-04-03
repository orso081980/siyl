<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        // Lambda filesystem is read-only; /tmp is the only writable path.
        if (isset($_SERVER['LAMBDA_TASK_ROOT'])) {
            return '/tmp/symfony-cache/' . $this->environment;
        }

        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if (isset($_SERVER['LAMBDA_TASK_ROOT'])) {
            return '/tmp/symfony-logs';
        }

        return parent::getLogDir();
    }
}
