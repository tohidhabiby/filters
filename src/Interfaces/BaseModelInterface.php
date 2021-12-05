<?php

namespace Habibi\App\Interfaces;

use Habibi\Interfaces\HasIdInterface;

interface BaseModelInterface extends HasIdInterface
{
    /**
     * @return array
     */
    public function exportColumns(): array;
}
