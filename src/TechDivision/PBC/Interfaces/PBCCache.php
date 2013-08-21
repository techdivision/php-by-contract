<?php

namespace TechDivision\PBC\Interfaces;

interface PBCCache
{
    public function get();

    public function add($className, $fileName);

    public function isCached($className);

    public function getFiles();
}