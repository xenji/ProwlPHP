<?php

require_once 'ProwlConnector.class.php';
require_once 'ProwlMessage.class.php';

$oProwl = new ProwlConnector();
$oMsg 	= new ProwlMessage();

// If you have one:
// $oProwl->setProviderKey('MY_PROVIDER_KEY');

try 
{
	$oProwl->setIsPostRequest(true);
	$oMsg->setPriority(0);
	
	// You can ADD up to 5 api keys
	$oMsg->addApiKey('e0bf09a4cc20ae0bcd63b30b19031ef59a458634');

	$oMsg->setEvent('My Event!');
	
	// These are optional:
	$oMsg->setDescription('My Event description.');
	$oMsg->setApplication('My Custom App Name.');
	
	$bSubmitted = $oProwl->push($oMsg);
	
	if ($bSubmitted == false)
	{
		print $oProwl->getError();
	} // if
	
	else print "Mesage sent";
}
catch (InvalidArgumentException $oIAE)
{
	print $oIAE->getMessage();
}
catch (OutOfRangeException $oOORE)
{
	print $oIAE->getMessage();
}