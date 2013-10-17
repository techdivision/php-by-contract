<?php

namespace TechDivision\PBC\Interfaces;

interface StructureDefinition
{
    /**
     * Will return a list of all dependencies of a structure like parent class, implemented interfaces, etc.
     *
     * @return array
     */
    public function getDependencies();
}