<?php
/**
 * Example File
 * @author Mario Mueller <mario.mueller.mac@me.com>
 * @version 0.3.2
 */
require_once 'ProwlConnector.class.php';
require_once 'ProwlMessage.class.php';
require_once 'ProwlResponse.class.php';

$oProwl = new ProwlConnector();
$oMsg 	= new ProwlMessage();

// If you have one:
// $oProwl->setProviderKey('MY_PROVIDER_KEY');

try 
{
	$oProwl->setIsPostRequest(true);
	$oMsg->setPriority(0);
	
	// You can ADD up to 5 api keys
	// This is a Test Key, please use your own.
	$oMsg->addApiKey('e0bf09a4cc20ae0bcd63b30b19031ef59a458634');

	$oMsg->setEvent('My Event!');
	
	// These are optional:
	$oMsg->setDescription('My Event description.');
	$oMsg->setApplication('My Custom App Name.');
	
	$oResponse = $oProwl->push($oMsg);

	if ($oResponse->isError()) 
	{	
		print $oResponse->getErrorAsString();
	}
	else
	{
		print "Message sent." . PHP_EOL;
		print "You have " . $oResponse->getRemaining() . " Messages left." . PHP_EOL;
		print "Your counter will be resetted on " . date('Y-m-d H:i:s', $oResponse->getResetDate());
	}
}
catch (InvalidArgumentException $oIAE)
{
	print $oIAE->getMessage();
}
catch (OutOfRangeException $oOORE)
{
	print $oOORE->getMessage();
}
