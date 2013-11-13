<?php
/**
 * Created by JetBrains PhpStorm.
 * User: wickb
 * Date: 29.08.13
 * Time: 13:21
 * To change this template use File | Settings | File Templates.
 */

namespace Wicked\salesman\Sales\Stack;

class StackSale
{
    /**
     *
     */
    public function sell()
    {
        $someStrings = array('sdfsafsf', 'rzutrzutfzj', 'OUHuISGZduisd0', 'skfse', 'd', 'fdghdfg', 'srfxcf');

        // Do some string stuff
        $stringStack = new StringStack();
        // push the strings into the stack
        foreach ($someStrings as $someString) {

            $stringStack->push($someString);
        }
        // and pop some of them again
        $stringStack->pop();
        $stringStack->pop();
        $stringStack->pop();
        $stringStack->pop();
        $stringStack->pop();
        $stringStack->pop();
        $stringStack->pop();

        // Work with our unique stacks
        $uniqueStack1 = new UniqueStack1();
        $uniqueStack2 = new UniqueStack2();

        foreach ($someStrings as $someString) {

            $uniqueStack1->push($someString);
            $uniqueStack2->push($someString);
        }
    }
}