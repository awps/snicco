<?php

declare(strict_types=1);


namespace Snicco\Bundle\Templating\Tests\fixtures;

use Snicco\Component\Templating\View\View;
use Snicco\Component\Templating\ViewComposer\ViewComposer;
use stdClass;

use function spl_object_hash;

final class ViewComposerWithDependency implements ViewComposer
{

    private stdClass $stdClass;

    public function __construct(stdClass $stdClass)
    {
        $this->stdClass = $stdClass;
    }

    public function compose(View $view): void
    {
        $view->addContext('object_hash', spl_object_hash($this->stdClass));
    }
}