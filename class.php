<?php
/* cURL helper class
 * Author:  Johan / Asbra.net
 * Created: 2011-02-19
 * Updated: 2011-07-27
 * Updated: 2011-09-10
 * Updated: 2011-10-04
 * Updated: 2012-07-21
 * Updated: 2013-01-15
 * Updated: 2013-02-04
 */

class cURL
{
  private $useragent; // user agent string
  private $handle;    // handle to the cURL object
  private $cookies;   // boolean value whether to use/store cookies or not
  private $redirs;    // boolean value whether to follow redirects or not
  public $cookiejar;  // filename of the cookie jar
  public $data;       // last data returned from a cURL transfer
  public $code;       // the last HTTP code returned
  public $url;        // URL of the page we are currently at
  public $info;       // information about the last cURL transfer
  private $proxy;     // proxy adress
  private $proxypwd;  // proxy password
  public $xhr;        // boolean value whether to use XHR (XMLHttpRequest) or not

  function __construct( $redirs = true, $cookies = true, $useragent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:13.0) Gecko/20100101 Firefox/13.0' )
  {
    $this->useragent = $useragent;
    $this->handle    = null;
    $this->cookies   = $cookies;
    $this->redirs    = $redirs;
    $this->cookiejar = 'cookies.txt';
    $this->proxy     = '';
    $this->proxypwd  = '';
    $this->xhr       = false;
    $this->referer = null;
  }

  public function set_proxy( $proxy, $auth = '' )
  {
    $this->proxy    = $proxy;
    $this->proxypwd = $auth;
  }

  private function setopt($url)
  {
    curl_setopt( $this->handle, CURLOPT_URL, $url );
    curl_setopt( $this->handle, CURLOPT_HEADER, 0 );

    if( $this->redirs )
    {
      curl_setopt( $this->handle, CURLOPT_FOLLOWLOCATION, 1 );
      curl_setopt( $this->handle, CURLOPT_MAXREDIRS, 10 );
    }
    else
    {
      curl_setopt( $this->handle, CURLOPT_FOLLOWLOCATION, 0 );
      curl_setopt( $this->handle, CURLOPT_MAXREDIRS, 0 );
    }

    curl_setopt( $this->handle, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $this->handle, CURLOPT_USERAGENT, $this->useragent );

    if( substr( $url, 4, 1 ) == 's' )
    {
      curl_setopt( $this->handle, CURLOPT_SSL_VERIFYPEER, false );
      curl_setopt( $this->handle, CURLOPT_SSL_VERIFYHOST, false );
    }

    if( $this->cookies )
    {
      curl_setopt( $this->handle, CURLOPT_COOKIEJAR, $this->cookiejar );
      curl_setopt( $this->handle, CURLOPT_COOKIEFILE, $this->cookiejar );
    }

    if( $this->proxy != '' )
    {
      curl_setopt( $this->handle, CURLOPT_PROXY, $this->proxy );

      if( $this->proxypwd != '' )
      {
        curl_setopt( $this->handle, CURLOPT_PROXYUSERPWD, $this->proxypwd );
      }
    }

    if( $this->referer != '' ) #Custom patch here!
    {
      curl_setopt( $this->handle, CURLOPT_REFERER, $this->referer );
    }

    if( $this->xhr == true )
    {
      curl_setopt( $this->handle, CURLOPT_HTTPHEADER, array( "X-Requested-With: XMLHttpRequest" ) );
    }
  }

  function post( $url, $data, $referer = '', $xhr = false )
  {
    $this->handle = curl_init();

    $fields_string = '';
    if( is_array( $data ) )
    {
      foreach( $data as $key => $value )
      {
        $fields_string .= urlencode($key) . '=' . urlencode($value) . '&';
      }
      rtrim( $fields_string, '&' );
    }
    else
    {
      $fields_string = $data;
    }

    $this->xhr = $xhr;
    if($referer != "") $this->referer = $referer; #Custom patch here!
    $this->setopt($url);
    curl_setopt( $this->handle, CURLOPT_POST, 1 );
    curl_setopt( $this->handle, CURLOPT_POSTFIELDS, $fields_string );

    $this->data = curl_exec( $this->handle );
    if($this->data === false){
        echo 'Curl error: ' . curl_error($this->handle);
    };
    $this->code = curl_getinfo( $this->handle, CURLINFO_HTTP_CODE );
    $this->info = curl_getinfo( $this->handle );
    $this->url  = ( isset( $this->info['redirect_url'] ) && !empty( $this->info['redirect_url'] ) != '' ? $this->info['redirect_url'] : $this->info['url'] );
    //curl_close( $this->handle );

    return $this->data;
  }

  function get( $url, $referer = '', $xhr = false )
  {
    $this->handle = curl_init();

    $this->xhr = $xhr;
    if($referer != "") $this->referer = $referer; #Custom patch here!
    $this->setopt($url);
    $this->data = curl_exec( $this->handle );
    if($this->data === false){
        echo 'Curl error: ' . curl_error($this->handle);
    };
    $this->code = curl_getinfo( $this->handle, CURLINFO_HTTP_CODE );
    $this->info = curl_getinfo( $this->handle );
    $this->url  = ( isset( $this->info['redirect_url'] ) && !empty( $this->info['redirect_url'] ) != '' ? $this->info['redirect_url'] : $this->info['url'] );
    //curl_close( $this->handle );

    return $this->data;
  }

  public function cleanup()
  {
    if( file_exists( $this->cookiejar ) )
    {
      unlink( $this->cookiejar );
    }
  }
}

?>