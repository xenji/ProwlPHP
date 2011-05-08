<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mario
 * Date: 08.05.11
 * Time: 12:39
 * To change this template use File | Settings | File Templates.
 */
require_once dirname(__FILE__) . '/bootstrap.php';

class TestConnector extends \PHPUnit_Framework_TestCase {

	public function testDefaultMessageWithFilterInstance() {
		$aConfig = include dirname(__FILE__) . '/config.php';

		$oConnector = new \Prowl\Connector();
		$oConnector->setProviderKey($aConfig['providerkey']);

		$oMessage = new \Prowl\Message();
		$oMessage->addApiKey($aConfig['apikey']);
		$oMessage->setApplication("Unit Test");
		$oMessage->setPriority(0);
		$oMessage->setEvent("Unit Test");
		$oMessage->setDescription("Unit Test testDefaultMessageWithFilterInstance");

		$oMessage->setFilter(new Prowl\Security\PassthroughFilterImpl());

		$oResponse = $oConnector->push($oMessage);
		$this->assertFalse($oResponse->isError());
	}

	public function testDefaultMessageWithClosure() {
		$aConfig = include dirname(__FILE__) . '/config.php';

		$oConnector = new \Prowl\Connector();
		$oConnector->setProviderKey($aConfig['providerkey']);

		$oMessage = new \Prowl\Message();
		$oMessage->addApiKey($aConfig['apikey']);
		$oMessage->setApplication("Unit Test");
		$oMessage->setPriority(0);
		$oMessage->setEvent("Unit Test");
		$oMessage->setDescription("Unit Test testDefaultMessageWithClosure");

		$oMessage->setFilterCallback(function($sContent) {
			return $sContent;
		});

		$oResponse = $oConnector->push($oMessage);
		$this->assertFalse($oResponse->isError());
	}

	public function testRetrieveToken() {
		$aConfig = include dirname(__FILE__) . '/config.php';

		$oConnector = new \Prowl\Connector();
		$oConnector->setProviderKey($aConfig['providerkey']);

		$oTokenResponse = $oConnector->retrieveToken();

		$this->assertTrue(filter_var($oTokenResponse->getTokenUrl(), FILTER_VALIDATE_URL) !== false);
		$this->assertNotNull($oTokenResponse->getToken());
	}

	public function testApiToken() {
		$this->markTestSkipped("This cannot be tested automatically as it requires user interaction.");
	}
}