<?php
/* This class is part of the XP framework
 * 
 * $Id$
 */

  uses(
    'io.IOException',
    'io.File',
    'util.Hashmap'
  );
  
  /**
   * An interface to property-files (aka "ini-files")
   *
   * Property-files syntax is easy.
   * <pre>
   * [section]
   * key1=value
   * key2="value"
   * key3="value|value|value"
   * key4="a:value|b:value"
   * ; comment
   *
   * [section2]
   * key=value
   * </pre>
   *
   * @test      xp://net.xp_framework.unittest.util.PropertiesTest
   * @purpose   Wrapper around parse_ini_file
   */
  class Properties extends Object {
    public
      $_file    = '',
      $_data    = NULL;

    private static $INI_PARSE_BUG= FALSE;
    static function __static() {
      self::$INI_PARSE_BUG= version_compare(PHP_VERSION, '5.3.0', 'lt');
    }
      
    /**
     * Constructor
     *
     * @param   string filename
     */
    public function __construct($filename) {
      $this->_file= $filename;
    }
    
    /**
     * Create a property file from an io.File object
     *
     * @param   io.File file
     * @return  util.Properties
     * @throws  io.IOException in case the file given does not exist
     */
    public static function fromFile(File $file) {
      if (!$file->exists()) {
        throw new IOException('The file "'.$file->getURI().'" could not be read');
      }
      return new self($file->getURI());
    }

    /**
     * Create a property file from a string
     *
     * @param   string str
     * @return  util.Properties
     */
    public static function fromString($str) {
      $self= new self(NULL);
      $self->_data= self::parse($str);
      return $self;
    }
    
    /**
     * Parse from a string
     *
     * @param   string str
     * @return  [:var] data
     */
    protected static function parse($str) {
      $section= NULL;
      $data= array();
      if ($t= strtok($str, "\r\n")) do {
        switch ($t{0}) {
          case ';':
          case '#':
            break;

          case '[':
            $p= strpos($t, '[');
            $section= substr($t, $p+ 1, strpos($t, ']', $p)- 1);
            $data[$section]= array();
            break;

          default:
            if (FALSE === ($p= strpos($t, '='))) break;
            $key= trim(substr($t, 0, $p));
            $value= trim(substr($t, $p+ 1), ' ');

            // Check for string quotations
            if (strlen($value) && ('"' == ($quote= $value{0}))) {

              // For multiline values, get next token by quote
              if (FALSE === strrpos($value, $quote, 1))
                $value.= "\n".strtok($quote);

              $value= trim($value, $quote);
              $value= trim(substr($value, 0, ($p= strpos($value, '"')) !== FALSE
                ? $p : strlen($value)
              ), $quote);

            // Check for comment
            } else if (FALSE !== ($p= strpos($value, ';'))) {
              $value= trim(substr($value, 0, $p));
            }

            // Arrays and maps: key[], key[0], key[assoc]
            if (']' === substr($key, -1)) {
              $p= strpos($key, '[');
              $offset= substr($key, $p+ 1, -1);
              $key= substr($key, 0, $p);
              if (!isset($data[$section][$key])) {
                $data[$section][$key]= array();
              }
              if ('' === $offset) {
                $data[$section][$key][]= $value;
              } else {
                $data[$section][$key][$offset]= $value;
              }
            } else {
              $data[$section][$key]= $value;
            }
            break;
        }
      } while ($t= strtok("\r\n"));
      return $data;
    }
    
    /**
     * Retrieves the file name containing the properties
     *
     * @return  string
     */
    public function getFilename() {
      return $this->_file;
    }
    
    /**
     * Create the property file
     *
     * @throws  io.IOException if the property file could not be created
     */
    public function create() {
      $fd= new File($this->_file);
      $fd->open(FILE_MODE_WRITE);
      $fd->close();
    }
    
    /**
     * Returns whether the property file exists
     *
     * @return  bool
     */
    public function exists() {
      return file_exists($this->_file);
    }
    
    /**
     * Helper method that loads the data from the file if needed
     *
     * @param   bool force default FALSE
     * @throws  io.IOException
     */
    protected function _load($force= FALSE) {
      if (!$force && NULL !== $this->_data) return;
      
      if (self::$INI_PARSE_BUG) {
        $this->_data= $this->parse(file_get_contents($this->_file));
        $error= xp::errorAt(__FILE__, __LINE__ - 1);
      } else {
        $this->_data= parse_ini_file($this->_file, TRUE);
        $error= xp::errorAt(__FILE__, __LINE__ - 1);
      }
      if ($error) {
        $e= new IOException('The file "'.$this->_file.'" could not be read');
        xp::gc(__FILE__);
        $this->_data= NULL;
        throw $e;
      }
    }
    
    /**
     * Reload all data from the file
     *
     */
    public function reset() {
      return $this->_load(TRUE);
    }
    
    /**
     * Save properties to the file
     *
     * @throws  io.IOException if the property file could not be written
     */
    public function save() {
      $fd= new File($this->_file);
      $fd->open(FILE_MODE_WRITE);
      
      foreach (array_keys($this->_data) as $section) {
        $fd->write(sprintf("[%s]\n", $section));
        
        foreach ($this->_data[$section] as $key => $val) {
          if (';' == $key{0}) {
            $fd->write(sprintf("\n; %s\n", $val)); 
          } else {
            if ($val instanceof Hashmap) {
              $str= '';
              foreach ($val->keys() as $k) {
                $str.= '|'.$k.':'.$val->get($k);
              }
              $val= substr($str, 1);
            }
            if (is_array($val)) $val= implode('|', $val);
            if (is_string($val)) $val= '"'.$val.'"';
            $fd->write(sprintf(
              "%s=%s\n",
              $key,
              strval($val)
            ));
          }
        }
        $fd->write("\n");
      }
      $fd->close();
    }

    /**
     * Get the first configuration section
     *
     * @see     xp://util.Properties#getNextSection
     * @return  string the first section's name
     */
    public function getFirstSection() {
      $this->_load();
      reset($this->_data);
      return key($this->_data);
    }
    
    /**
     * Get the next configuration section
     *
     * Example:
     * <code>
     *   if ($section= $prop->getFirstSection()) do {
     *     var_dump($section, $prop->readSection($section));
     *   } while ($section= $prop->getNextSection());
     * </code>
     *
     * @see     xp://util.Properties#getFirstSection
     * @return  var string section or FALSE if this was the last section
     */
    public function getNextSection() {
      $this->_load();
      if (FALSE === next($this->_data)) return FALSE;

      return key($this->_data);
    }
    
    /**
     * Read an entire section into an array
     *
     * @param   string name
     * @param   var[] default default array() what to return in case the section does not exist
     * @return  array
     */
    public function readSection($name, $default= array()) {
      $this->_load();
      return isset($this->_data[$name]) 
        ? $this->_data[$name] 
        : $default
      ;
    }
    
    /**
     * Read a value as string
     *
     * @param   string section
     * @param   string key
     * @param   string default default '' what to return in case the section or key does not exist
     * @return  string
     */ 
    public function readString($section, $key, $default= '') {
      $this->_load();
      return isset($this->_data[$section][$key])
        ? $this->_data[$section][$key]
        : $default
      ;
    }
    
    /**
     * Read a value as array
     *
     * @param   string section
     * @param   string key
     * @param   var[] default default NULL what to return in case the section or key does not exist
     * @return  array
     */
    public function readArray($section, $key, $default= array()) {
      $this->_load();

      // New: key[]="a" or key[0]="a"
      // Old: key="" (an empty array) or key="a|b|c"
      if (!isset($this->_data[$section][$key])) {
        return $default;
      } else if (is_array($this->_data[$section][$key])) {
        return $this->_data[$section][$key];
      } else {
        return '' == $this->_data[$section][$key] ? array() : explode('|', $this->_data[$section][$key]);
      }
    }
    
    /**
     * Read a value as hash
     *
     * @param   string section
     * @param   string key
     * @param   util.Hashmap default default NULL what to return in case the section or key does not exist
     * @return  util.Hashmap
     */
    public function readHash($section, $key, $default= NULL) {
      $this->_load();

      // New: key[color]="green" and key[make]="model"
      // Old: key="color:green|make:model"
      if (!isset($this->_data[$section][$key])) {
        return $default;
      } else if (is_array($this->_data[$section][$key])) {
        return new Hashmap($this->_data[$section][$key]);
      } else {
        $return= array();
        foreach (explode('|', $this->_data[$section][$key]) as $val) {
          if (strstr($val, ':')) {
            list($k, $v)= explode(':', $val, 2);
            $return[$k]= $v;
          } else {
            $return[]= $val;
          } 
        }
        return new Hashmap($return);
      }
    }

    /**
     * Read a value as range
     *
     * @param   string section
     * @param   string key
     * @param   int[] default default NULL what to return in case the section or key does not exist
     * @return  array
     */
    public function readRange($section, $key, $default= array()) {
      $this->_load();
      if (!isset($this->_data[$section][$key])) return $default;
      
      list($min, $max)= explode('..', $this->_data[$section][$key]);
      return range((int)$min, (int)$max);
    }
    
    /**
     * Read a value as integer
     *
     * @param   string section
     * @param   string key
     * @param   int default default 0 what to return in case the section or key does not exist
     * @return  int
     */ 
    public function readInteger($section, $key, $default= 0) {
      $this->_load();
      return isset($this->_data[$section][$key])
        ? intval($this->_data[$section][$key])
        : $default
      ;
    }

    /**
     * Read a value as float
     *
     * @param   string section
     * @param   string key
     * @param   float default default 0.0 what to return in case the section or key does not exist
     * @return  float
     */ 
    public function readFloat($section, $key, $default= 0.0) {
      $this->_load();
      return isset($this->_data[$section][$key])
        ? doubleval($this->_data[$section][$key])
        : $default
      ;
    }

    /**
     * Read a value as boolean
     *
     * @param   string section
     * @param   string key
     * @param   bool default default FALSE what to return in case the section or key does not exist
     * @return  bool TRUE, when key is 1, 'on', 'yes' or 'true', FALSE otherwise
     */ 
    public function readBool($section, $key, $default= FALSE) {
      $this->_load();
      if (!isset($this->_data[$section][$key])) return $default;
      return (
        '1'     === $this->_data[$section][$key] ||
        'yes'   === $this->_data[$section][$key] ||
        'true'  === $this->_data[$section][$key] ||
        'on'    === $this->_data[$section][$key]
      );
    }
    
    /**
     * Returns whether a section exists
     *
     * @param   string name
     * @return  bool
     */
    public function hasSection($name) {
      $this->_load();
      return isset($this->_data[$name]);
    }

    /**
     * Add a section
     *
     * @param   string name
     * @param   bool overwrite default FALSE whether to overwrite existing sections
     * @return  string name
     */
    public function writeSection($name, $overwrite= FALSE) {
      $this->_load();
      if ($overwrite || !$this->hasSection($name)) $this->_data[$name]= array();
      return $name;
    }
    
    /**
     * Add a string (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   string value
     */
    public function writeString($section, $key, $value) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      $this->_data[$section][$key]= (string)$value;
    }
    
    /**
     * Add a string (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   int value
     */
    public function writeInteger($section, $key, $value) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      $this->_data[$section][$key]= (int)$value;
    }
    
    /**
     * Add a float (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   float value
     */
    public function writeFloat($section, $key, $value) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      $this->_data[$section][$key]= (float)$value;
    }

    /**
     * Add a boolean (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   bool value
     */
    public function writeBool($section, $key, $value) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      $this->_data[$section][$key]= $value ? 'yes' : 'no';
    }
    
    /**
     * Add an array string (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   array value
     */
    public function writeArray($section, $key, $value) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      $this->_data[$section][$key]= $value;
    }

    /**
     * Add a hashmap (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   var value either a util.Hashmap or an array
     */
    public function writeHash($section, $key, $value) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      if ($value instanceof Hashmap) {
        $this->_data[$section][$key]= $value;
      } else {
        $this->_data[$section][$key]= new Hashmap($value);
      }
    }
    
    /**
     * Add a comment (and the section, if necessary)
     *
     * @param   string section
     * @param   string key
     * @param   string comment
     */
    public function writeComment($section, $comment) {
      $this->_load();
      if (!$this->hasSection($section)) $this->_data[$section]= array();
      $this->_data[$section][';'.sizeof($this->_data[$section])]= $comment;
    }
    
    /**
     * Creates a string representation of this property file
     *
     * @return  string
     */
    public function toString() {
      return $this->getClassName().'('.$this->_file.')@{'.xp::stringOf($this->_data).'}';
    }
  }
?>
