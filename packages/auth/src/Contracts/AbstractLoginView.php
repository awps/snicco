<?php

declare(strict_types=1);

namespace Snicco\Auth\Contracts;

use Snicco\Auth\Traits\UsesCurrentRequest;
use Snicco\Core\Contracts\Responsable;

abstract class AbstractLoginView implements Responsable
{
    
    use UsesCurrentRequest;
}