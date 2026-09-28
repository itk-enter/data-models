<?php

namespace ItkEnter\DataModels\Generator;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigFactory
{
    public static function create(string $repoRoot): Environment
    {
        return new Environment(new FilesystemLoader($repoRoot.'/templates'), [
            'strict_variables' => true,
            'autoescape' => false,
        ]);
    }
}
