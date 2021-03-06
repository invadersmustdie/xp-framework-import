<?php
/* This class is part of the XP framework
 *
 * $Id$
 */
 
  uses('rdbms.DataSet');
 
  /**
   * Class wrapper for table cc, database bugs
   * (Auto-generated on Tue, 19 Oct 2004 13:05:24 +0200 by clang)
   *
   * @purpose  Datasource accessor
   */
  class BugzillaCcList extends DataSet {
    public
      $bug_id             = 0,
      $who                = 0;

    /**
     * Static initializer
     *
     */
    public static function __static() { 
      with ($peer= BugzillaCcList::getPeer()); {
        $peer->setTable('cc');
        $peer->setConnection('bugzilla');
        $peer->setPrimary(array('bug_id', 'who'));
        $peer->setTypes(array(
          'bug_id'              => '%d',
          'who'                 => '%d'
        ));
      }
    }  
  
    /**
     * Retrieve associated peer
     *
     * @return  &rdbms.Peer
     */
    public function getPeer() {
      return Peer::forName(__CLASS__);
    }
  
    /**
     * Gets an instance of this object by index "bug_id"
     *
     * @param   int bug_id
     * @return  &BugzillaCcList object
     * @throws  rdbms.SQLException in case an error occurs
     */
    public function getByBug_id($bug_id) {
      $peer= BugzillaCcList::getPeer();
      return $peer->doSelect(new Criteria(array('bug_id', $bug_id, EQUAL)));
    }

    /**
     * Gets an instance of this object by index "who"
     *
     * @param   int who
     * @return  &BugzillaCcList[] object
     * @throws  rdbms.SQLException in case an error occurs
     */
    public function getByWho($who) {
      $peer= BugzillaCcList::getPeer();
      return $peer->doSelect(new Criteria(array('who', $who, EQUAL)));
    }

    /**
     * Retrieves bug_id
     *
     * @return  int
     */
    public function getBug_id() {
      return $this->bug_id;
    }
      
    /**
     * Sets bug_id
     *
     * @param   int bug_id
     * @return  int the previous value
     */
    public function setBug_id($bug_id) {
      return $this->_change('bug_id', $bug_id);
    }

    /**
     * Retrieves who
     *
     * @return  int
     */
    public function getWho() {
      return $this->who;
    }
      
    /**
     * Sets who
     *
     * @param   int who
     * @return  int the previous value
     */
    public function setWho($who) {
      return $this->_change('who', $who);
    }
  }
?>
