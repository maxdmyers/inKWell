<?php
require_once('./support/init.php');
include('./support/Imap.php');
 
class fSMTPTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{	
		$_SERVER['SERVER_NAME'] = 'flourishlib.com';
		if (defined('EMAIL_DEBUG')) {
			fCore::enableDebugging(TRUE);
		}
	}
	
	public function tearDown()
	{
		
	}
	
	private function findMessage($token, $user)
	{
		$mailbox = new Imap(EMAIL_SERVER, $user, EMAIL_PASSWORD);
		
		$i = 0;
		do {
			sleep(1);
			$messages = $mailbox->listMessages();
			foreach ($messages as $number => $headers) {
				if (strpos($headers['subject'], $token) !== FALSE) {
					$message = $mailbox->getMessage($number, TRUE);
					$mailbox->deleteMessage($number);
					return $message;
				}
			}
			$i++;
		} while ($i < 60);
		
		throw new Exception('Email message ' . $token . ' never arrived');
	}
	
	private function generateSubjectToken()
	{
		return uniqid('', TRUE);
	}
	
	static public function serverProvider()
	{
		$output = array();
		
		if (defined('THIRD_PARTY_EMAIL_PASSWORD')) {
			$output[] = array('mail.gmx.com', 25, FALSE, 'flourishlib@gmx.com', THIRD_PARTY_EMAIL_PASSWORD);
			$output[] = array('smtp.aim.com', 587, FALSE, 'flourishlib@aim.com', THIRD_PARTY_EMAIL_PASSWORD);
			$output[] = array('smtp.zoho.com', 465, TRUE, 'flourishlib@zoho.com', THIRD_PARTY_EMAIL_PASSWORD);
			$output[] = array('smtp.gmail.com', 465, TRUE, 'flourishlib@gmail.com', THIRD_PARTY_EMAIL_PASSWORD);
			$output[] = array('smtp.live.com', 587, FALSE, 'flourishlib@live.com', THIRD_PARTY_EMAIL_PASSWORD);
		}
		
		if (ini_get('SMTP')) {
			$output[] = array(ini_get('SMTP'), ini_get('smtp_port'), FALSE, NULL, NULL);
		} else {
			$output[] = array('localhost', 25, FALSE, 5, NULL, NULL);
		}
		
		return $output;
	}
	
	/**
	 * @dataProvider serverProvider
	 */	
	public function testBadCredentials($server, $port, $secure, $username, $password)
	{
		if (!$username) {
			$this->markTestSkipped();
		}
		
		$this->setExpectedException('fValidationException');
		$token = $this->generateSubjectToken();
		
		$smtp = new fSMTP($server, $port, $secure, 5);
		$smtp->authenticate($username, $password . 'dhjskdhsaku');
		
		$email = new fEmail();
		$email->setFromEmail($username ? $username : 'will@flourishlib.com');
		$email->addRecipient(EMAIL_ADDRESS, 'Test User');
		$email->setSubject($token . ': Testing Simple Email');
		$email->setBody('This is a simple test');
		$email->send($smtp);
	}
	
	/**
	 * @dataProvider serverProvider
	 */	
	public function testSendSimple($server, $port, $secure, $username, $password)
	{
		$token = $this->generateSubjectToken();
		
		$smtp = new fSMTP($server, $port, $secure, 5);
		if ($username) {
			$smtp->authenticate($username, $password);	
		}
		
		$email = new fEmail();
		$email->setFromEmail($username ? $username : 'will@flourishlib.com');
		$email->addRecipient(EMAIL_ADDRESS, 'Test User');
		$email->setSubject($token . ': Testing Simple Email');
		$email->setBody('This is a simple test');
		$message_id = $email->send($smtp);
		
		$message = $this->findMessage($token, EMAIL_USER);
		$this->assertEquals($message_id, $message['headers']['Message-ID']);
		$this->assertEquals($username ? $username : 'will@flourishlib.com', $message['headers']['From']);
		$this->assertEquals($token . ': Testing Simple Email', $message['headers']['Subject']);
		$this->assertEquals('This is a simple test', trim($message['plain']));
		
		$smtp->close();
	}
	
	/**
	 * @dataProvider serverProvider
	 */	
	public function testSendSinglePeriodOnLine($server, $port, $secure, $username, $password)
	{
		$token = $this->generateSubjectToken();
		
		$smtp = new fSMTP($server, $port, $secure, 5);
		if ($username) {
			$smtp->authenticate($username, $password);	
		}
		
		$email = new fEmail();
		$email->setFromEmail($username ? $username : 'will@flourishlib.com');
		$email->addRecipient(EMAIL_ADDRESS, 'Test User');
		$email->setSubject($token . ': Testing Single Periods on a Line');
		$email->setBody('This is a test of single periods on a line
.
.');
		$message_id = $email->send($smtp);
		
		$message = $this->findMessage($token, EMAIL_USER);
		$this->assertEquals($message_id, $message['headers']['Message-ID']);
		$this->assertEquals($username ? $username : 'will@flourishlib.com', $message['headers']['From']);
		$this->assertEquals($token . ': Testing Single Periods on a Line', $message['headers']['Subject']);
		$this->assertEquals('This is a test of single periods on a line
.
.', trim($message['plain']));
		
		$smtp->close();
	}
	
	/**
	 * @dataProvider serverProvider
	 */	
	public function testSendMultipleToCcBcc($server, $port, $secure, $username, $password)
	{
		$token = $this->generateSubjectToken();
		
		$smtp = new fSMTP($server, $port, $secure, 5);
		if ($username) {
			$smtp->authenticate($username, $password);	
		}
		
		$email = new fEmail();
		$email->setFromEmail($username ? $username : 'will@flourishlib.com');
		$email->addRecipient(EMAIL_ADDRESS, 'Test User');
		$email->addRecipient(str_replace('@', '_2@', EMAIL_ADDRESS), 'Test User 2');
		$email->addCCRecipient(str_replace('@', '_3@', EMAIL_ADDRESS), 'Test User 3');
		$email->addBCCRecipient(str_replace('@', '_4@', EMAIL_ADDRESS), 'Test User 4');
		$email->setSubject($token . ': Testing Multiple Recipients');
		$email->setBody('This is a test of sending multiple recipients');
		$message_id = $email->send($smtp);
		
		$message = $this->findMessage($token, EMAIL_USER);
		$this->assertEquals($message_id, $message['headers']['Message-ID']);
		$this->assertEquals($username ? $username : 'will@flourishlib.com', $message['headers']['From']);
		$this->assertEquals($token . ': Testing Multiple Recipients', $message['headers']['Subject']);
		$this->assertEquals('This is a test of sending multiple recipients', trim($message['plain']));
		
		$message = $this->findMessage($token, str_replace('tests', 'tests_2', EMAIL_USER));
		// It seems the windows imap extension doesn't support the personal part of an email address
		$is_windows = stripos(php_uname('a'), 'windows') !== FALSE;
		$this->assertEquals($is_windows ? 'tests@flourishlib.com' : '"Test User" <tests@flourishlib.com>', $message['headers']['To']);
		
		$message = $this->findMessage($token, str_replace('tests', 'tests_3', EMAIL_USER));
		$this->assertEquals($is_windows ? 'tests_3@flourishlib.com' : '"Test User 3" <tests_3@flourishlib.com>', $message['headers']['Cc']);
		
		$message = $this->findMessage($token, str_replace('tests', 'tests_4', EMAIL_USER));
		$this->assertEquals(FALSE, isset($message['headers']['Bcc']));
		
		$smtp->close();
	}
}