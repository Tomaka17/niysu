<?php
namespace Niysu\Contexts;

class CookiesContextTest extends \PHPUnit_Framework_TestCase {
	private $logger;

	protected function setUp() {
		$this->logger = new \Monolog\Logger('');
		$this->logger->pushHandler(new \Monolog\Handler\NullHandler());
	}

	public function testNoCookie() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'response' => new \Niysu\HTTPResponseStorage(),
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [			// no cookies header
			])
		]);

		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');

		$this->assertNull($cookiesService->test);
		$this->assertEquals(0, count($cookiesService->getCookiesList()));
	}

	public function testGetCookie() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'response' => new \Niysu\HTTPResponseStorage(),
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Cookie' => 'test=hello'
			])
		]);

		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');

		$this->assertEquals($cookiesService->test, 'hello');
		$this->assertEquals(1, count($cookiesService->getCookiesList()));
	}

	/**
	 * @depends testGetCookie
	 */
	public function testGetCookieDefaultValue() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'response' => new \Niysu\HTTPResponseStorage(),
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [			// no cookies header
			])
		]);
		
		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');
		
		$this->assertEquals('hello', $cookiesService->get('test', 'hello'));
		$this->assertEquals(0, count($cookiesService->getCookiesList()));
	}

	/**
	 * @depends testGetCookie
	 */
	public function testGetMultipleCookies() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'response' => new \Niysu\HTTPResponseStorage(),
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Cookie' => 'test1=hello; test2=world; test3=tomato'
			])
		]);

		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');

		$this->assertEquals(count($cookiesService->getCookiesList()), 3);
		$this->assertEquals($cookiesService->test1, 'hello');
		$this->assertEquals($cookiesService->test2, 'world');
		$this->assertEquals($cookiesService->test3, 'tomato');
	}

	/**
	 * @depends testGetCookie
	 */
	public function testUpdateCookie() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'response' => new \Niysu\HTTPResponseStorage(),
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Cookie' => 'test=4'
			])
		]);

		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');

		$cookiesService->test++;
		$this->assertEquals($cookiesService->test, 5);

		$cookiesService->test2 = 5;
		$this->assertEquals(count($cookiesService->getCookiesList()), 2);
	}

	public function testSetCookie() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET'),
			'response' => new \Niysu\HTTPResponseStorage()
		]);

		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');

		$cookiesService->test = 10;
		$this->assertTrue($scope->response->hasHeader('Set-Cookie'));
	}

	public function testUnsetCookie() {
		$scope = new \Niysu\Scope([
			'log' => $this->logger,
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET'),
			'response' => new \Niysu\HTTPResponseStorage()
		]);

		$cookiesService = $scope->call(__NAMESPACE__.'\\CookiesContext');

		$cookiesService->test = 10;
		unset($cookiesService->test);

		$this->assertEquals(0, count($cookiesService->getCookiesList()));
	}
};

?>