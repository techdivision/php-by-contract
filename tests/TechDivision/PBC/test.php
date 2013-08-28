<?php use TechDivision\PBC\Exceptions\BrokenPreConditionException;
        use TechDivision\PBC\Exceptions\BrokenPostConditionException;
        use TechDivision\PBC\Exceptions\BrokenInvariantException;
        /**
 * Class Stack
 *
 * @invariant $this->size() >= 0
 * @invariant $this->size() < 100
 */class ParentTestClass
        {
        /**
            * @var mixed
            */
            private $pbcOld;
            /**
            * @var array
            */
            private $attributes = array();
        private function pbcClassInvariant() {if (!($this->size() >= 0 && $this->size() < 100)){throw new BrokenInvariantException('Assertion $this->size() >= 0 && $this->size() < 100 failed in pbcClassInvariant.');}}
        /**
         * Magic function to forward writing property access calls if within visibility boundaries.
         *
         * @throws InvalidArgumentException
         */
        public function __set($name, $value)
        {
            // Does this property even exist? If not, throw an exception
            if (isset($this->attributes[$name])) {

                throw new \InvalidArgumentException;

            }

            // Check if the invariant holds
            list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        

            // Now check what kind of visibility we would have
            $attribute = $this->attributes[$name];
            switch ($attribute["visibility"]) {

                case "protected" :

                    if (is_subclass_of(get_called_class(), "ParentTestClass")) {

                        $this->$name = $value;

                    } else {

                        throw new \InvalidArgumentException;
                    }
                    break;

                case "public" :

                    $this->$name = $value;
                    break;

                default :

                    throw new \InvalidArgumentException;
                    break;
            }

            // Check if the invariant holds
            list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        
        }
        public function size() {list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        $passedOne = false;
                $failedAssertion = array();$pbcResult = $this->sizePBCOriginal();list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        return $pbcResult;}private function sizePBCOriginal() {

    }/**
     * @requires $this->size() >= 1
     */public function peek() {list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        $passedOne = false;
                $failedAssertion = array();if ($passedOne === false && !(($this->size() >= 1))){$failedAssertion[] = '($this->size() >= 1)';} else {$passedOne = true;}if ($passedOne === false){throw new BrokenPreConditionException('Assertions ' . implode(", ", $failedAssertion) . ' failed in peek.');}$pbcResult = $this->peekPBCOriginal();list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        return $pbcResult;}private function peekPBCOriginal() {

    }/**
     * @requires $this->size() >= 1
     * @ensures $this->size() == $pbcOld->size() - 1
     * @ensures $pbcResult == $pbcOld->peek()
     */public function pop() {list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        $passedOne = false;
                $failedAssertion = array();if ($passedOne === false && !(($this->size() >= 1))){$failedAssertion[] = '($this->size() >= 1)';} else {$passedOne = true;}if ($passedOne === false){throw new BrokenPreConditionException('Assertions ' . implode(", ", $failedAssertion) . ' failed in pop.');}$this->$pbcOld = clone $this;$pbcResult = $this->popPBCOriginal();if (!(($this->size() == $pbcOld->size() - 1) && ($pbcResult == $pbcOld->peek()))){throw new BrokenPostConditionException('Assertion ($this->size() == $pbcOld->size() - 1) && ($pbcResult == $pbcOld->peek()) failed in pop.');}list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        return $pbcResult;}private function popPBCOriginal() {

    }/**
     * @ensures $this->size() == $pbcOld->size() + 1
     * @ensures $this->peek() == $obj
     */public function push(object $obj) {list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        $passedOne = false;
                $failedAssertion = array();$this->$pbcOld = clone $this;$pbcResult = $this->pushPBCOriginal($obj);if (!(($this->size() == $pbcOld->size() + 1) && ($this->peek() == $obj))){throw new BrokenPostConditionException('Assertion ($this->size() == $pbcOld->size() + 1) && ($this->peek() == $obj) failed in push.');}list(, $caller) = debug_backtrace(false);
        if (isset($caller["class"]) && $caller["class"] !== __CLASS__) {

            $this->pbcClassInvariant();
        }
        return $pbcResult;}private function pushPBCOriginal($obj) {

    }}