<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestCase',
    'rdbms.DriverManager',
    'rdbms.finder.GenericFinder',
    'net.xp_framework.unittest.rdbms.mock.MockConnection',
    'net.xp_framework.unittest.rdbms.dataset.JobFinder'
  );

  /**
   * TestCase
   *
   * @see      xp://rdbms.finder.Finder
   * @purpose  Unit test
   */
  class FinderTest extends TestCase {
    const MOCK_CONNECTION_CLASS = 'net.xp_framework.unittest.rdbms.mock.MockConnection';
    protected $fixture = NULL;

    /**
     * Mock connection registration
     *
     */  
    #[@beforeClass]
    public static function registerMockConnection() {
      DriverManager::register('mock', XPClass::forName(self::MOCK_CONNECTION_CLASS));
    }

    /**
     * Setup method
     *
     */
    public function setUp() {
      $this->fixture= new JobFinder();
      $this->fixture->getPeer()->setConnection(DriverManager::getConnection('mock://mock/JOBS?autoconnect=1'));
    }

    /**
     * Helper method which invokes the finder's method() method and un-wraps
     * exceptions thrown.
     *
     * @param   string name
     * @return  rdbms.finder.FinderMethod
     * @throws  lang.Throwable
     */
    protected function method($name) {
      try {
        return $this->fixture->method($name);
      } catch (FinderException $e) {
        throw $e->getCause();
      }
    }

    /**
     * Helper methods
     *
     * @return  net.xp_framework.unittest.rdbms.mock.MockConnection
     */
    protected function getConnection() {
      return $this->fixture->getPeer()->getConnection();
    }

    /**
     * Tests the getPeer() method
     *
     */
    #[@test]
    public function peerObject() {
      $this->assertClass($this->fixture->getPeer(), 'rdbms.Peer');
    }

    /**
     * Tests the getPeer() method returns the same Peer instance that 
     * Job::getPeer() returns.
     *
     */
    #[@test]
    public function jobPeer() {
      $this->assertEquals($this->fixture->getPeer(), Job::getPeer());
    }

    /**
     * Tests the entityMethods() method
     *
     */
    #[@test]
    public function entityMethods() {
      $methods= $this->fixture->entityMethods();
      $this->assertEquals(1, sizeof($methods));
      $this->assertClass($methods[0], 'rdbms.finder.FinderMethod');
      $this->assertEquals(ENTITY, $methods[0]->getKind());
      $this->assertEquals('byPrimary', $methods[0]->getName());
      $this->assertSubClass($methods[0]->invoke(array($pk= 1)), 'rdbms.SQLExpression');
    }

    /**
     * Tests the collectionMethods() method
     *
     */
    #[@test]
    public function collectionMethods() {
      static $invocation= array(
        'all'         => array(),
        'newestJobs'  => array(),
        'expiredJobs' => array(),
        'similarTo'   => array('Test')
      );

      $methods= $this->fixture->collectionMethods();
      $this->assertEquals(4, sizeof($methods)); // three declared plu all()
      foreach ($methods as $method) {
        $this->assertClass($method, 'rdbms.finder.FinderMethod');
        $name= $method->getName();
        $this->assertEquals(COLLECTION, $method->getKind(), $name);
        $this->assertEquals(TRUE, isset($invocation[$name]), $name);
        $this->assertSubClass($method->invoke($invocation[$name]), 'rdbms.SQLExpression', $name);
      }
    }

    /**
     * Tests the allMethods() method
     *
     */
    #[@test]
    public function allMethods() {
      $methods= $this->fixture->allMethods(); // four declared plu all()
      $this->assertEquals(5, sizeof($methods));
    }

    /**
     * Tests the method() method
     *
     */
    #[@test]
    public function byPrimaryMethod() {
      $method= $this->fixture->method('byPrimary');
      $this->assertClass($method, 'rdbms.finder.FinderMethod');
      $this->assertEquals('byPrimary', $method->getName());
      $this->assertEquals(ENTITY, $method->getKind());
    }
    
    /**
     * Tests the method() method throws an exception when passed a 
     * non-existant method name
     *
     */
    #[@test, @expect('lang.MethodNotImplementedException')]
    public function nonExistantMethod() {
      $this->method('@@NON-EXISTANT@@');
    }

    /**
     * Tests the method() method throws an exception when passed a 
     * method name which refers to a non-finder method.
     *
     */
    #[@test, @expect('lang.IllegalArgumentException')]
    public function notAFinderMethod() {
      $this->method('getPeer');
    }
    
    /**
     * Tests find(byPrimary())
     *
     */
    #[@test]
    public function findByExistingPrimary() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $entity= $this->fixture->find($this->fixture->byPrimary(1));
      $this->assertClass($entity, 'net.xp_framework.unittest.rdbms.dataset.Job');
    }

    /**
     * Tests find()->byPrimary()
     *
     */
    #[@test]
    public function findByExistingPrimaryFluent() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $entity= $this->fixture->find()->byPrimary(1);
      $this->assertClass($entity, 'net.xp_framework.unittest.rdbms.dataset.Job');
    }

    /**
     * Tests find(byPrimary()) for the situation when nothing is returned.
     *
     */
    #[@test]
    public function findByNonExistantPrimary() {
      $this->assertNull($this->fixture->find($this->fixture->byPrimary(0)));
    }

    /**
     * Tests find(byPrimary()) for the situation when more than one result
     * is returned.
     *
     */
    #[@test, @expect('rdbms.finder.FinderException')]
    public function findUnexpectedResults() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $this->fixture->find($this->fixture->byPrimary(1));
    }

    /**
     * Tests get(byPrimary())
     *
     */
    #[@test]
    public function getByExistingPrimary() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $entity= $this->fixture->get($this->fixture->byPrimary(1));
      $this->assertClass($entity, 'net.xp_framework.unittest.rdbms.dataset.Job');
    }

    /**
     * Tests get()->byPrimary()
     *
     */
    #[@test]
    public function getByExistingPrimaryFluent() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $entity= $this->fixture->get()->byPrimary(1);
      $this->assertClass($entity, 'net.xp_framework.unittest.rdbms.dataset.Job');
    }

    /**
     * Tests find(byPrimary()) for the situation when nothing is returned.
     *
     */
    #[@test, @expect('rdbms.finder.NoSuchEntityException')]
    public function getByNonExistantPrimary() {
      $this->fixture->get($this->fixture->byPrimary(0));
    }

    /**
     * Tests find(byPrimary()) for the situation when more than one result
     * is returned.
     *
     */
    #[@test, @expect('rdbms.finder.FinderException')]
    public function getUnexpectedResults() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $this->fixture->get($this->fixture->byPrimary(1));
    }

    /**
     * Tests findAll(newestJobs())
     *
     */
    #[@test]
    public function findNewestJobs() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $collection= $this->fixture->findAll($this->fixture->newestJobs());
      $this->assertEquals(2, sizeof($collection));
    }

    /**
     * Tests findAll()->newestJobs()
     *
     */
    #[@test]
    public function findNewestJobsFluent() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $collection= $this->fixture->findAll()->newestJobs();
      $this->assertEquals(2, sizeof($collection));
    }

    /**
     * Tests getAll(newestJobs())
     *
     */
    #[@test]
    public function getNewestJobs() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $collection= $this->fixture->getAll($this->fixture->newestJobs());
      $this->assertEquals(2, sizeof($collection));
    }

    /**
     * Tests getAll()->newestJobs()
     *
     */
    #[@test]
    public function getNewestJobsFluent() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $collection= $this->fixture->getAll()->newestJobs();
      $this->assertEquals(2, sizeof($collection));
    }

    /**
     * Tests getAll(newestJobs())
     *
     */
    #[@test, @expect('rdbms.finder.NoSuchEntityException')]
    public function getNothingFound() {
      $this->fixture->getAll($this->fixture->newestJobs());
    }

    /**
     * Tests find() wraps SQLExceptions into FinderExceptions
     *
     */
    #[@test, @expect('rdbms.finder.FinderException')]
    public function findWrapsSQLException() {
      $this->getConnection()->makeQueryFail(6010, 'Not enough power');
      $this->fixture->find(new Criteria());
    }

    /**
     * Tests findAll() wraps SQLExceptions into FinderExceptions
     *
     */
    #[@test, @expect('rdbms.finder.FinderException')]
    public function findAllWrapsSQLException() {
      $this->getConnection()->makeQueryFail(6010, 'Not enough power');
      $this->fixture->findAll(new Criteria());
    }

    /**
     * Tests findAll() wraps SQLExceptions into FinderExceptions
     *
     */
    #[@test, @expect(class= 'lang.Error', withMessage= '/Call to undefined method .+JobFinder::nonExistantMethod/')]
    public function fluentNonExistantFinder() {
      $this->fixture->findAll()->nonExistantMethod(new Criteria());
    }

    /**
     * Test GenericFinder
     *
     */
    #[@test]
    public function genericFinderGetAll() {
      $this->getConnection()->setResultSet(new MockResultSet(array(
        0 => array(   // First row
          'job_id'      => 1,
          'title'       => $this->getName(),
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        ),
        1 => array(   // Second row
          'job_id'      => 2,
          'title'       => $this->getName().' #2',
          'valid_from'  => Date::now(),
          'expire_at'   => NULL
        )
      )));
      $all= create(new GenericFinder(Job::getPeer()))->getAll(new Criteria());
      $this->assertEquals(2, sizeof($all));
      $this->assertClass($all[0], 'net.xp_framework.unittest.rdbms.dataset.Job');
      $this->assertClass($all[1], 'net.xp_framework.unittest.rdbms.dataset.Job');
    }
  }
?>
