<?php

namespace TechDivision\PBC\Interfaces;

interface ConfigInterface
{
    public static function getInstance($context = '');

    public function load($file);

    public function validate($file);
}
