<?php
/**
 * Copyright [2011] [Mario Mueller]
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *	  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace Prowl;

/**
 * Prowl Connector
 *
 * This class provides a connection to the prowl service
 * at <link>http://www.prowlapp.com/</link>.
 *
 * @author Mario Mueller <mario.mueller.work@gmail.com>
 * @version 1.0.0
 * @package Prowl
 * @subpackage Connector
 */
class Connector {

	/**
	 * System version to send it with the client string
	 * @var string
	 */
	protected $sVersion = "1.0.0";

	/**
	 * The cUrl connection
	 * @var resource
	 */
	protected $rCurl = null;

	/**
	 * Shall we use a proxy?
	 * @var boolean
	 */
	protected $bUseProxy = false;

	/**
	 * The proxy url
	 * @var string
	 */
	protected $sProxyUrl = null;

	/**
	 * The password for the proxy.
	 * This is optional.
	 * @var string
	 */
	protected $sProxyPasswd = null;

	/**
	 * The provider key. Use the
	 * setter to modify this.
	 * @var string
	 */
	protected $sProviderKey = null;

	/**
	 * Sets the identifier if this
	 * should be a post request.
	 * @var boolean
	 */
	protected $bIsPostRequest = false;


	/**
	 * The API base url.
	 * @var string
	 */
	protected $sApiUrl = 'https://api.prowlapp.com/publicapi/';

	/**
	 * The API key verification url
	 * @var string
	 */
	protected $sVerifyContext = 'verify?apikey=%s&providerkey=%s';

	/**
	 * New messages will be send to
	 * this endpoint.
	 * @var string
	 */
	protected $sPushEndpoint = 'add';

	/**
	 * The last response that was
	 * received from the API.
	 * @var \Prowl\Response
	 */
	protected $oLastResponse = null;

	/**
	 * ProwlConnector.class provides access to the
	 * webservice interface of Prowl by using
	 * cUrl + SSL. Use the setters of this class
	 * to provide the mandatory parameters.
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com> - Refactored on 23rd of Jan. 2010
	 * @throws RuntimeException
	 * @return void
	 */
	public function __construct() {
		if (extension_loaded('curl') == false) {
			throw new \RuntimeException('cUrl Extension is not available.');
		}

		$curl_info = curl_version(); // Checks for cURL function and SSL version. Thanks Adrian Rollett!
		if (empty($curl_info['ssl_version'])) {
			throw new \RuntimeException('Your cUrl Extension does not support SSL.');
		}
	}

	/**
	 * Verifies the keys. This is optional but
	 * will we part of the future workflow
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @param string $sApikey
	 * @param string $sProvkey
	 * @return ProwlResponse
	 */
	public function verify($sApikey, $sProvkey) {
		$sReturn = $this->execute(sprintf($this->sVerifyContext, $sApikey, $sProvkey));
		return \Prowl\Response::fromResponseXml($sReturn);
	}


	/**
	 * Sets the provider key.
	 * This method uses a fluent interface.
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @param string $sKey
	 * @return Prowl
	 */
	public function setProviderKey($sKey) {
		if (is_string($sKey)) {
			$this->sProviderKey = $sKey;
		} else {
			throw new \InvalidArgumentException('The param was not a string.');
		}
		return $this;
	}

	/**
	 * Sets the post request identifier to true or false.
	 * This method uses a fluent interface.
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @param boolean $bIsPost
	 * @return \Prowl\Connector
	 */
	public function setIsPostRequest($bIsPost) {
		if (is_bool($bIsPost)) {
			$this->bIsPostRequest = $bIsPost;
		} else {
			throw new \InvalidArgumentException('The param was not a bool.');
		}
		return $this;
	}

	/**
	 * Pushes a message to the given api key.
	 *
	 * @author Mario Mueller <mario.mueller@mac@me.com>
	 * @param ProwlMessage $oMessage
	 * @return \Prowl\Response
	 */
	public function push(\Prowl\Message $oMessage) {
		$oMessage->validate();

		$aParams['apikey'] = $oMessage->getApiKeysAsString();
		$aParams['providerkey'] = $this->sProviderKey;
		$aParams['application'] = $oMessage->getApplication();
		$aParams['event'] = $oMessage->getEvent();
		$aParams['description'] = $oMessage->getDescription();
		$aParams['priority'] = $oMessage->getPriority();

		array_map(create_function('$sAryVal', 'return str_replace("\\n","\n", $sAryVal);'), $aParams);

		$sContextUrl = $this->sPushEndpoint;

		if (!$this->bIsPostRequest) {
			$sContextUrl .= '?';
		}

		$sParams = http_build_query($aParams);
		$sReturn = $this->execute($sContextUrl, $this->bIsPostRequest, $sParams);

		$this->oLastResponse = \Prowl\Response::fromResponseXml($sReturn);

		return $this->oLastResponse;
	}


	/**
	 * The remaining requests
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @throws RuntimeException
	 * @return integer
	 */
	public function getRemaining() {
		if (is_null($this->oLastResponse)) {
			throw new \RuntimeException('Cannot access last response. Did you made a request?');
		}
		return $this->oLastResponse->getRemaining();
	}

	/**
	 * The reset date by last response.
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @throws RuntimeException
	 * @return integer
	 */
	public function getResetDate() {
		if (is_null($this->oLastResponse)) {
			throw new \RuntimeException('Cannot access last response. Did you made a request?');
		}
		return $this->oLastResponse->getResetDate();
	}

	/**
	 * Executes the request via cUrl and returns the response.
	 *
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @param string	 $sUrl			 The resource context
	 * @param boolean	 $bIsPostRequest	Is it a post request?
	 * @param string	 $sParams		The urlencode'ed params.
	 * @return string
	 */
	protected function execute($sUrl, $bIsPostRequest = false, $sParams = null) {
		$this->rCurl = curl_init($this->sApiUrl . $sUrl);
		curl_setopt($this->rCurl, CURLOPT_HEADER, 0);
		curl_setopt($this->rCurl, CURLOPT_USERAGENT, "ProwlConnector.class/" . $this->sVersion);
		curl_setopt($this->rCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($this->rCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->rCurl, CURLOPT_RETURNTRANSFER, 1);

		if ($bIsPostRequest) {
			curl_setopt($this->rCurl, CURLOPT_POST, 1);
			curl_setopt($this->rCurl, CURLOPT_POSTFIELDS, $sParams);
		}

		if ($this->bUseProxy) {
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
	 * @author Mario Mueller <mario.mueller.work@gmail.com>
	 * @since  0.3.1
	 * @param  string $sProxy			 The URL to a proxy server.
	 * @param  string $sUserPassword	The Password for the server (opt.)
	 * @return \Prowl\Connector
	 */
	public function setProxy($sProxy, $sUserPasswd = null) {
		$mUrl = filter_var((string)$sProxy, FILTER_VALIDATE_URL);
		if ($mUrl !== false) {
			$this->bUseProxy = true;
			$this->sProxyUrl = $mUrl;

			if (is_string($sUserPasswd)) {
				$this->sProxyPasswd = (string)$sUserPasswd;
			}
		}
		return $this;
	}
}