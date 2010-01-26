<?php
/**
 * Prowl Connector
 * 
 * This class provides a connection to the prowl service
 * at <link>http://prowl.weks.net/</link>.
 * 
 * @author Fenric <sandbox [at] fenric.co.uk>
 * @author Mario Mueller <mario.mueller.mac@me.com> - Refactored on 23rd of Jan. 2010
 * @version 0.3.1
 * @package Prowl
 * @subpackage Connector
 */
class ProwlConnector
{
	/**
	 * The version
	 * @var string
	 */
	protected $sVersion 		= '0.3.1';
	
	/**
	 * The cUrl connection
	 * @var resource
	 */
	protected $rCurl 			= null;
	
	/**
	 * API return code
	 * @var integer
	 */
	protected $iReturnCode 		= null;
	
	/**
	 * The count of remaining requests
	 * @var integer
	 */
	protected $iRemaining 		= null;
	
	/**
	 * The date for the remaining to be
	 * resetted
	 * @var integer
	 */
	protected $iResetDate		= null;
	
	/**
	 * Shall we use a proxy?
	 * @var boolean
	 */
	protected $bUseProxy 		= false;
	
	/**
	 * The proxy url
	 * @var string
	 */
	protected $sProxyUrl 		= null;
	
	/**
	 * The password for the proxy.
	 * This is optional.
	 * @var string
	 */
	protected $sProxyPasswd 	= null;

	/**
	 * The provider key. Use the
	 * setter to modify this.
	 * @var string
	 */
	protected $sProviderKey 	= null;
	
	/**
	 * Sets the identifier if this 
	 * should be a post request.
	 * @var boolean
	 */
	protected $bIsPostRequest	= false;
	
	
	/**
	 * The API base url.
	 * @var string
	 */
	protected $sApiUrl 			= 'https://prowl.weks.net/publicapi/';
	
	/**
	 * The API key verification url
	 * @var string
	 */
	protected $sVerifyContext 	= 'verify?apikey=%s&providerkey=%s';
	
	/**
	 * New messages will be send to
	 * this endpoint.
	 * @var string
	 */
	protected $sPushEndpoint 	= 'add';
	
	
	
	/**
	 * ProwlConnector.class provides access to the 
	 * webservice interface of Prowl by using
	 * cUrl + SSL. Use the setters of this class
	 * to provide the mandatory parameters.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com> - Refactored on 23rd of Jan. 2010 
	 * @throws RuntimeException
	 * @return void
	 */
	public function __construct()
	{
		if (extension_loaded('curl') == false)
			throw new RuntimeException('cUrl Extension is not available.');
			
		$curl_info = curl_version();	// Checks for cURL function and SSL version. Thanks Adrian Rollett!
		if(empty($curl_info['ssl_version']))
			throw new RuntimeException('Your cUrl Extension does not support SSL.');
			
	} // function
	
	/**
	 * Verifies the keys.
	 * 
	 * @param string $sApikey
	 * @param string $sProvkey
	 * @return 
	 */
	public function verify($sApikey, $sProvkey)
	{
		$sReturn = $this->execute(sprintf($this->sVerifyContext, $sApikey, $sProvkey));		
		return $this->response($sReturn);
	} // function
	
	
	/**
	 * Sets the provider key.
	 * This method uses a fluent interface.
	 * 
	 * @param string $sKey
	 * @return Prowl
	 */
	public function setProviderKey($sKey)
	{
		if (is_string($sKey))
			$this->sProviderKey = $sKey;
		else 
			throw new InvalidArgumentException('The param was not a string.');
		
		return $this;
	} // function
	
	/**
	 * Sets the post request identifier to true or false.
	 * This method uses a fluent interface.
	 *  
	 * @param boolean $bIsPost
	 * @return Prowl
	 */
	public function setIsPostRequest($bIsPost)
	{
		if (is_bool($bIsPost))
			$this->bIsPostRequest = $bIsPost;
		else 
			throw new InvalidArgumentException('The param was not a bool.');
		
		return $this;
	} // function
	
	/**
	 * Pushes a message to the given api key.
	 * 
	 * @author Mario Mueller <mario.mueller@mac@me.com>
	 * @param ProwlMessage $oMessage
	 * @return boolean
	 */
	public function push(ProwlMessage $oMessage)
	{	
		$oMessage->validate();
		
		$aParams['apikey'] 		= $oMessage->getApiKeysAsString();
		$aParams['providerkey'] = $this->sProviderKey;
		$aParams['application']	= $oMessage->getApplication();
		$aParams['event']		= $oMessage->getEvent();
		$aParams['description']	= $oMessage->getDescription();
		
		array_map(
			create_function('$sAryVal', 'return str_replace("\\n","\n", $sAryVal);'),
			$aParams);
		
		$sContextUrl = $this->sPushEndpoint;
		
		if (!$this->bIsPostRequest)
			$sContextUrl .= '?';
			
		$sParams 	= http_build_query($aParams);
		$sReturn 	= $this->execute($sContextUrl, $this->bIsPostRequest, $sParams);
		$oResponse 	= ProwlResponse::fromResponseXml($sReturn);
		
		$this->iRemaining = $oResponse->getRemaining();
		$this->iResetDate = $oResponse->getResetDate();
		
		return $oResponse;
	}
		
	
	/**
	 * The remaining requests
	 * 
	 * @return integer
	 */
	public function getRemaining()
	{
		return $this->iRemaining;
	}
	
	/**
	 * The reset date
	 * 
	 * @return integer
	 */
	public function getResetDate()
	{
		return $this->iResetDate;
	}
	
	/**
	 * Executes the request via cUrl and returns the response.
	 * 
	 * @param string 	$sUrl 			The resource context
	 * @param boolean 	$bIsPostRequest	Is it a post request?
	 * @param string 	$sParams		The urlencode'ed params.
	 */
	protected function execute($sUrl, $bIsPostRequest = false, $sParams=null)
	{
		$this->rCurl = curl_init($this->sApiUrl . $sUrl);
		curl_setopt($this->rCurl, CURLOPT_HEADER, 0);
		curl_setopt($this->rCurl, CURLOPT_USERAGENT, "ProwlConnector.class/" . $this->sVersion);
		curl_setopt($this->rCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->rCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->rCurl, CURLOPT_RETURNTRANSFER, 1);
		
		if ($bIsPostRequest)
		{
			curl_setopt($this->rCurl, CURLOPT_POST, 1);
			curl_setopt($this->rCurl, CURLOPT_POSTFIELDS, $sParams);
		}
		
		if ($this->bUseProxy)
		{
			curl_setopt($this->rCurl, CURLOPT_HTTPPROXYTUNNEL, 1);
			curl_setopt($this->rCurl, CURLOPT_PROXY, $this->sProxyUrl);
			curl_setopt($this->rCurl, CURLOPT_PROXYUSERPWD, $this->sProxyPasswd); 
		}
		
		$sReturn = curl_exec($this->rCurl);
		curl_close($this->rCurl);
		return $sReturn;
	}
	
	
	/**
	 * Sets the proxy server.
	 * 
	 * @since  0.3.1
	 * @param  string $sProxy 			The URL to a proxy server.
	 * @param  string $sUserPassword	The Password for the server (opt.)
	 * @return Prowl
	 */
	public function setProxy($sProxy, $sUserPasswd = null)
	{
		$mUrl = filter_var((string) $sProxy, FILTER_VALIDATE_URL);
		if ($mUrl !== false)
		{
			$this->bUseProxy = true;
			$this->sProxyUrl = $mUrl;
			
			if (is_string($sUserPasswd))
			{
				$this->sProxyPasswd = (string) $sUserPasswd;
			} // if password is present
		} // if proxy
		return $this;
	} // function
} // class