<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses('rdbms.SQLExpression');

  /**
   * Represents an SQL statement
   *
   * <code>
   *  with ($peer= News::getPeer()); {
   *    $statement= new Statement('select * from news where news_id < 10');
   * 
   *    // Use doSelect()
   *    $objects= $peer->doSelect($statement);
   * 
   *    // Use iteratorFor()
   *    for ($iterator= $peer->iteratorFor($statement); $iterator->hasNext(); ) {
   *      $object= $iterator->next();
   *      // ...
   *    }
   *  }
   * </code>
   *
   * @purpose  Expression
   */
  class Statement extends Object implements SQLExpression {
    public
      $arguments = array();

    /**
     * Constructor
     *
     * @param   string format
     * @param   var* args
     */
    public function __construct() {
      $this->arguments= func_get_args();
    }

    /**
     * Creates a string representation
     *
     * @return  string
     */
    public function toString() {
      return $this->getClassName()."@{\n  ".$this->arguments[0]."\n}";
    }
        
    /**
     * test if the Expression is a projection
     *
     * @return  bool
     */
    public function isProjection() {
      return FALSE;
    }

    /**
     * test if the Expression is a join
     *
     * @return  bool
     */
    public function isJoin() {
      return FALSE;
    }

    /**
     * Executes an SQL SELECT statement
     *
     * @param   rdbms.DBConnection conn
     * @param   rdbms.Peer peer
     * @param   rdbms.join.Joinprocessor jp optional
     * @param   bool buffered default TRUE
     * @return  rdbms.ResultSet
     */
    public function executeSelect(DBConnection $conn, Peer $peer, $jp= NULL, $buffered= TRUE) {
      $this->arguments[0]= preg_replace(
        '/object\(([^\)]+)\)/i', 
        '$1.'.implode(', $1.', array_keys($peer->types)),
        $this->arguments[0]
      );
      return call_user_func_array(array($conn, $buffered ? 'query' : 'open'), $this->arguments);
    }

  } 
?>
