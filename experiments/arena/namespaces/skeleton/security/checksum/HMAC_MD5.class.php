<?php
/* This class is part of the XP framework
 *
 * $Id: HMAC_MD5.class.php 10594 2007-06-11 10:04:54Z friebe $ 
 */

  namespace security::checksum;
 
  uses('security.checksum.Checksum');
  
  /**
   * HMAC_MD5 checksum
   *
   * @see      xp://security.checksum.Checksum
   * @purpose  Provide an API to check HMAC_MD5 checksums
   */
  class HMAC_MD5 extends Checksum {
  
    /**
     * Calculate HMAC_MD5 for given string (and key, if specified)
     *
     * @param   string str
     * @param   string key default NULL
     * @return  string
     */
    public static function hash($str, $key= ) {
      if (NULL === $key) return pack('H*', md5($str));
      
      $key= str_pad($key, 0x40, "\x00");
      if (strlen($key) > 0x40) {
        $key= pack('H*', md5($key));
      }

      $ip= $key ^ str_repeat("\x36", 0x40);
      $op= $key ^ str_repeat("\x5c", 0x40);
      
      return HMAC_MD5::hash($op.pack('H*', md5($ip.$str)));
    }
      
    /**
     * Create a new checksum from a string
     *
     * @param   string str
     * @param   string key default NULL
     * @return  security.checksum.HMAC_MD5
     */
    public static function fromString($str, $key= ) {
      return new HMAC_MD5(HMAC_MD5::hash($str, $key));
    }

    /**
     * Create a new checksum from a file object
     *
     * @param   io.File file
     * @param   string key default NULL
     * @return  security.checksum.HMAC_MD5
     */
    public static function fromFile($file, $key= ) {
      try {
        $file->open(FILE_MODE_READ);
        $str= $file->read($file->size());
        $file->close();
      } catch (::Exception $e) {
        throw($e);
      }
      return new HMAC_MD5(HMAC_MD5::hash($str, $key));
    }
  }
?>