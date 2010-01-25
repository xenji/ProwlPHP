<?php

require_once 'ProwlConnector.class.php';
require_once 'ProwlMessage.class.php';

$oProwl = new ProwlConnector();
$oMsg 	= new ProwlMessage();

// If you have one:
// $oProwl->setProviderKey('MY_PROVIDER_KEY');

try 
{
	$oMsg->setPriority(0);
	
	// You can ADD up to 5 api keys
	$oMsg->addApiKey('FIRST_API_KEY');
	$oMsg->addApiKey('SECND_API_KEY');

	$oMsg->removeApiKey('FIRST_API_KEY');
	
	
	$oMsg->setEvent('My Event!');
	
	// These are optional:
	$oMsg->setDescription('My Event description.');
	$oMsg->setApplication('My Custom App Name.');
	
	$bSubmitted = $oProwl->push($oMsg);
	
	if ($bSubmitted == false)
	{
		print $oProwl->getError();
	} // if
}
catch (InvalidArgumentException $oIAE)
{
	print $oIAE->getMessage();
}
catch (OutOfRangeException $oOORE)
{
	print $oIAE->getMessage();
}