<?php
/**
 * Created by JetBrains PhpStorm.
 * User: mario
 * Date: 08.05.11
 * Time: 12:39
 * To change this template use File | Settings | File Templates.
 */
require_once dirname(__FILE__) . '/bootstrap.php';

/**
 * Tests GitHub Issue #7
 */
class TestBugId7 extends \PHPUnit_Framework_TestCase {

	public function testMessageSetter() {
		$oMessage = new \Prowl\Message();
		$sUrl = "http://xenji.com";
		$oMessage->setUrl($sUrl);
		$this->assertEquals($sUrl, $oMessage->getUrl(), "Assertion of URL setter failed, maybe due bug #7?");
	}
}