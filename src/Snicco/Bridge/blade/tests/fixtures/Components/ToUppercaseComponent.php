<?php

declare(strict_types=1);

namespace Snicco\Bridge\Blade\Tests\fixtures\Components;

use Illuminate\Contracts\View\View;
use Snicco\Bridge\Blade\BladeComponent;

class ToUppercaseComponent extends BladeComponent
{

    public function render(): View
    {
        return $this->view('uppercase');
    }

    public function toUpper(string $string): string
    {
        return strtoupper($string);
    }

}