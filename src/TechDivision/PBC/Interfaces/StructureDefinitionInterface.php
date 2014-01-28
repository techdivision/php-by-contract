<?php

namespace TechDivision\PBC\Interfaces;

interface StructureDefinitionInterface
{
    /**
     * Will return the qualified name of a structure
     *
     * @return string
     */
    public function getQualifiedName();

    /**
     * Will return the type of the definition.
     *
     * @return string
     */
    public function getType();

    /**
     * Will return a list of all dependencies of a structure like parent class, implemented interfaces, etc.
     *
     * @return array
     */
    public function getDependencies();

    /**
     * Will return true if the structure has (a) parent structure(s).
     * Will return false if not.
     *
     * @return bool
     */
    public function hasParents();

    /**
     * Will return all invariants of a structure.
     *
     * @return TypedListLists
     */
    public function getInvariants();
}
