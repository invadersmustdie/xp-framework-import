<?php
/* This class is part of the XP framework
 *
 * $Id: XmlRpcClient.class.php 10594 2007-06-11 10:04:54Z friebe $ 
 */

  namespace webservices::xmlrpc;

  uses('webservices.xmlrpc.XmlRpcResponseMessage', 'webservices.xmlrpc.XmlRpcRequestMessage');

  /**
   * This is a XML-RPC client; XML-RPC is a remote procedure call
   * protocol that uses XML as the message format.
   *
   * It has the same origins like SOAP, but has been developed to cure
   * some of the problems, SOAP has: it not nearly as complex as SOAP is
   * and does not have all those (mostly unneccessary) features SOAP does.
   * The spec is short and precise, unlike SOAP's - thus, the various
   * implementations really understand themselves.
   *
   * <code>
   *   uses('webservices.xmlrpc.XmlRpcClient', 'webservices.xmlrpc.transport.XmlRpcHttpTransport');
   *   $c= &new XmlRpcClient(new XMLRPCHTTPTransport('http://xmlrpc.xp-framework.net'));
   *   
   *   try(); {
   *     $res= $c->invoke('sumAndDifference', 5, 3);
   *   } if (catch('XmlRpcFaultException', $e)) {
   *     $e->printStackTrace();
   *     exit(-1);
   *   }
   *
   *   echo $res;
   * </code>
   *
   * @ext      xml
   * @see      http://xmlrpc.com
   * @purpose  Generic XML-RPC Client base class
   */
  class XmlRpcClient extends lang::Object {
    public
      $transport  = NULL,
      $message    = NULL,
      $answer     = NULL;

    /**
     * Constructor.
     *
     * @param   webservices.xmlrpc.transport.XmlRpcTransport transport
     */
    public function __construct($transport) {
      $this->transport= $transport;
    }
    
    /**
     * Set trace for debugging
     *
     * @param   util.log.LogCategory cat
     */
    public function setTrace($cat) {
      $this->transport->setTrace($cat);
    }
    
    /**
     * Invoke a method on a XML-RPC server
     *
     * @param   string method
     * @param   mixed vars
     * @return  mixed answer
     * @throws  lang.IllegalArgumentException
     * @throws  webservices.xmlrpc.XmlRpcFaultException
     */
    public function invoke() {
      if (!is('webservices.xmlrpc.transport.XmlRpcTransport', $this->transport))
        throw(new lang::IllegalArgumentException('Transport must be a webservices.xmlrpc.transport.XmlRpcTransport'));
    
      $args= func_get_args();
      
      $this->message= new XmlRpcRequestMessage();
      $this->message->create(array_shift($args));
      $this->message->setData($args);
      
      // Send
      if (FALSE == ($response= $this->transport->send($this->message))) return FALSE;
      
      // Retrieve response
      if (FALSE == ($this->answer= $this->transport->retrieve($response))) return FALSE;
      
      $data= $this->answer->getData();
      return $data;
    }
  }
?>