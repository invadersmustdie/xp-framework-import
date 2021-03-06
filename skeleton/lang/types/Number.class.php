<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  /**
   * The abstract class Number is the superclass of classes representing
   * numbers
   *
   * @test     xp://net.xp_framework.unittest.core.types.NumberTest
   * @purpose  Base class
   */
  abstract class Number extends Object {
    public
      $value = '';

    
    /**
     * Constructor
     *
     * @param   string value
     */
    public function __construct($value) {
      $this->value= (string)$value;
    }
    
    /**
     * Returns the value of this number as an int.
     *
     * @return  int
     */
    public function intValue() {
      return (int)$this->value;
    }

    /**
     * Returns the value of this number as a double.
     *
     * @deprecated Inconsistent with XP type system - use doubleValue() instead
     * @return  double
     */
    public function floatValue() {
      return (double)$this->value;
    }

    /**
     * Returns the value of this number as a float.
     *
     * @return  double
     */
    public function doubleValue() {
      return (double)$this->value;
    }
    
    /**
     * Returns a hashcode for this number
     *
     * @return  string
     */
    public function hashCode() {
      return $this->value;
    }

    /**
     * Returns a string representation of this number object
     *
     * @return  string
     */
    public function toString() {
      return $this->getClassName().'('.$this->value.')';
    }
    
    /**
     * Indicates whether some other object is "equal to" this one.
     *
     * @param   lang.Object cmp
     * @return  bool TRUE if the compared object is equal to this object
     */
    public function equals($cmp) {
      return $cmp instanceof $this && $this->value === $cmp->value;
    }
  }
?>
