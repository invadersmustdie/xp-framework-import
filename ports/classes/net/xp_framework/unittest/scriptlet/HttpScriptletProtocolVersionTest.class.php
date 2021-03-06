<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestCase',
    'scriptlet.HttpScriptlet',
    'peer.URL'
  );

  /**
   * TestCase for protocol versioning in the scriptlet API. Scriptlets
   * should answer with the correct protocol version (the one the user
   * agent sent the request in!)
   *
   * @see      xp://scriptlet.HttpScriptlet
   */
  class HttpScriptletProtocolVersionTest extends TestCase {
  
    /**
     * Creates a new request object
     *
     * @param   string method
     * @param   peer.URL url
     * @return  scriptlet.HttpScriptletRequest
     */
    protected function newRequest($method, URL $url) {
      $q= $url->getQuery('');
      $req= new HttpScriptletRequest();
      $req->method= $method;
      $req->env['SERVER_PROTOCOL']= 'HTTP/1.1';
      $req->env['REQUEST_URI']= $url->getPath('/').($q ? '?'.$q : '');
      $req->env['QUERY_STRING']= $q;
      $req->env['HTTP_HOST']= $url->getHost();
      if ('https' === $url->getScheme()) { 
        $req->env['HTTPS']= 'on';
      }
      $req->setHeaders(array());
      $req->setParams($url->getParams());
      return $req;
    }
  
    /**
     * Test HTTP/1.0 Requests are answered w/ HTTP/1.0
     *
     */
    #[@test]
    public function http10RequestAnsweredWithHttp10() {
      $req= $this->newRequest('GET', new URL('http://localhost/'));
      $req->env['SERVER_PROTOCOL']= 'HTTP/1.0';
      $res= new HttpScriptletResponse();
      
      $s= new HttpScriptlet();
      $s->service($req, $res);
      
      $this->assertEquals('1.0', $res->version);
    }

    /**
     * Test HTTP/1.1 Requests are answered w/ HTTP/1.1
     *
     */
    #[@test]
    public function http11RequestAnsweredWithHttp11() {
      $req= $this->newRequest('GET', new URL('http://localhost/'));
      $req->env['SERVER_PROTOCOL']= 'HTTP/1.1';
      $res= new HttpScriptletResponse();
      
      $s= new HttpScriptlet();
      $s->service($req, $res);
      
      $this->assertEquals('1.1', $res->version);
    }

    /**
     * Test HTTP/0.9 Requests are unsupported
     *
     */
    #[@test, @expect('scriptlet.ScriptletException')]
    public function http09RequestsUnsupported() {
      $req= $this->newRequest('GET', new URL('http://localhost/'));
      $req->env['SERVER_PROTOCOL']= 'HTTP/0.9';
      $res= new HttpScriptletResponse();
      
      $s= new HttpScriptlet();
      $s->service($req, $res);
    }

    /**
     * Test HTTP/1.2 Requests are unsupported
     *
     */
    #[@test, @expect('scriptlet.ScriptletException')]
    public function http12RequestsUnsupported() {
      $req= $this->newRequest('GET', new URL('http://localhost/'));
      $req->env['SERVER_PROTOCOL']= 'HTTP/1.2';
      $res= new HttpScriptletResponse();
      
      $s= new HttpScriptlet();
      $s->service($req, $res);
    }

    /**
     * Test requests without a a valid protocol version are unsupported
     *
     */
    #[@test, @expect('scriptlet.ScriptletException')]
    public function emptyProtocolRequestsUnsupported() {
      $req= $this->newRequest('GET', new URL('http://localhost/'));
      $req->env['SERVER_PROTOCOL']= '';
      $res= new HttpScriptletResponse();
      
      $s= new HttpScriptlet();
      $s->service($req, $res);
    }

    /**
     * Test requests without a a valid protocol version are unsupported
     *
     */
    #[@test, @expect('scriptlet.ScriptletException')]
    public function invalidProtocolRequestsUnsupported() {
      $req= $this->newRequest('GET', new URL('http://localhost/'));
      $req->env['SERVER_PROTOCOL']= 'INVALID/1.0';
      $res= new HttpScriptletResponse();
      
      $s= new HttpScriptlet();
      $s->service($req, $res);
    }
  }
?>
