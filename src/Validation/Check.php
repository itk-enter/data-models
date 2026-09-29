<?php

namespace ItkEnter\DataModels\Validation;

use ItkEnter\DataModels\Config;
use ItkEnter\DataModels\Model\ModelFolder;

interface Check
{
    /**
     * @return ValidationError[]
     */
    public function check(ModelFolder $model, Config $config): array;
}
