<?php
class ProwlResponse
{
	/**
	 * The raw response.
	 * @var string
	 */
	protected $sRawResponse;
	
	/**
	 * The return code of the app.
	 * @var unknown_type
	 */
	protected $iReturnCode = null;
	
	/**
	 * Takes the raw api response.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @param string $sXml
	 * @return ProwlResponse
	 */
	public static function fromResponseXml($sXml)
	{
		$oResponse = new self();
		$oResponse->sRawResponse = $sXml;
		$oResponse->parseRawResponse();
		return $oResponse;
	} // function
	
	/**
	 * Parses the raw xml data.
	 * 
	 * @author Mario Mueller <mario.mueller.mac@me.com>
	 * @return void
	 */
	protected function parseRawResponse()
	{
	
	} // function
	
	/**
	 * Handles the API response.
	 * 
	 * @since  0.3.1
	 * @param  string $mReturn	The returned string from the API call.
	 * @return boolean
	 */
	protected function response($mReturn)
	{
		if ($mReturn === false)
		{
			$this->iReturnCode = 500;
			return false;
		}
		
		$oSxmlResponse = new SimpleXMLElement($mReturn);
		
		/* @var $oSxmlResponse SimpleXMLElement */
		if ($oSxmlResponse->success instanceof SimpleXMLElement)
		{
			$this->iReturnCode 	= (int) $oSxmlResponse->success['code'];
			$this->iRemaining 	= (int) $oSxmlResponse->success['remaining'];
			$this->iResetDate 	= (int) $oSxmlResponse->success['resetdate'];
		} // if successful response
		else
		{
			$this->iReturnCode 	= (int) $oSxmlResponse->error['code'];
		} // else not successfull response

		unset($oSxmlResponse);
		
		switch ($this->iReturnCode)
		{
			// Everything went alright
			case 200: 	
				return true;	
			// Anything that is not 200 is a failure?				
			default:	
				return false;	
		} // switch response code
	} // function
	
	/**
	 * Returns the error message to a given code.
	 * 
	 * @param integer $code
	 * @return string
	 */
	public function getError($iCode=null)
	{
		$iCode = (empty($iCode)) ? $this->iReturnCode : $iCode;
		
		//TODO: Find a better way to implement error messages. 
		switch($iCode)
		{
			case 200: 	return 'Request Successful.';	break;
			case 400:	return 'Bad request, the parameters you provided did not validate.';	break;
			case 401: 	return 'The API key given is not valid, and does not correspond to a user.';	break;
			case 405:	return 'Method not allowed, you attempted to use a non-SSL connection to Prowl.';	break;
			case 406:	return 'Your IP address has exceeded the API limit.';	break;
			case 500:	return 'Internal server error, something failed to execute properly on the Prowl side.';	break;
			case 10000:	return 'cURL library missing vital functions or does not support SSL. cURL w/SSL is required to execute ProwlConnector.class.';	break;
			case 10001:	return 'Parameter value exceeds the maximum byte size.';	break;
			default:	return false;	break;
		}
	} // function
} // class