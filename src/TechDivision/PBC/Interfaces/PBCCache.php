<?php

namespace TechDivision\PBC\Interfaces;

use TechDivision\PBC\Entities\Definitions\ClassDefinition;

interface PBCCache
{
    public function get();

    public function add($classIdentifier, ClassDefinition $classDefinition, $fileName);

    public function isCached($className);

    public function isCurrent($className);

    public function getFiles();

    /**
     * Returns all classes that depend on $classIdentifier.
     *
     * With this method it should be possible to see which cached classes are depending on
     * a certain other one.
     * This is used to know if any dependants have to be updated as well.
     *
     * @param $classIdentifier
     * @return array
     */
    public function getDependants($classIdentifier);

    public function touch($classname);
}