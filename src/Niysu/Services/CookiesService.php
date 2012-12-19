<?php
namespace Niysu\Services;

class CookiesService {
	public function __construct(&$request, &$response, &$logService) {
		$this->request =& $request;
		$this->response =& $response;
		$this->logService =& $logService;
		$this->refreshRequestCookies();
	}

	public function setDefaultLifetime($ttl) {
		$this->defaultLifetime = $ttl;
	}

	public function __get($varName) {
		return $this->get($varName);
	}

	public function __set($varName, $value) {
		return $this->add($varName, $value);
	}

	public function __isset($varName) {
		return $this->get($varName) !== null;
	}

	public function list() {
		return array_merge($this->requestCookies, $this->updatedCookies);
	}

	public function get($cookieName) {
		if (isset($this->updatedCookies[$cookieName]))
			return $this->updatedCookies[$cookieName];

		if (!isset($this->requestCookies[$cookieName]))
			return null;

		return $this->requestCookies[$cookieName];
	}

	public function add($name, $value, $expires = null, $path = null, $domain = null, $secure = false, $httponly = false) {
		if ($expires === null)
			$expires = $this->defaultLifetime;

		$this->updatedCookies[$name] = $value;

		if (is_string($expires) && substr($expires, 0, 1) == 'P')
			$expires = new \DateInterval($expires);
		if ($expires instanceof \DateInterval)
			$expires = (((($expires->y * 12 + $expires->m) * 30.4 + $expires->d) * 24 + $expires->h) * 60 + $expires->i) * 60 + $expires->s;
		if (is_numeric($expires))
			$expires = date('r', time() + intval($expires));

		$header = $name.'='.$value;
		if ($expires)	$header .= '; Expires='.$expires;
		if ($path)		$header .= '; Path='.$path;
		if ($domain)	$header .= '; Domain='.$domain;
		if ($secure)	$header .= '; Secure';
		if ($httponly)	$header .= '; HttpOnly';

		if ($this->logService)
			$this->logService->debug('Adding cookie '.$name.' set to value: '.$value);
		if (!$this->request->isHTTPS() && $secure && $this->logService)
			$this->logService->notice('Setting a secure cookie not through HTTPS is pointless');

		$this->response->addHeader('Set-Cookie', $header);
	}


	private function refreshRequestCookies() {
		$this->requestCookies = [];

		$header = $this->request->getHeader('Cookie');
		if (!$header) return;

		foreach (explode(';', $header) as $cookie) {
			list($name, $value) = explode('=', $cookie, 2);
			$this->requestCookies[trim($name)] = trim($value);
		}
	}


	private $request;
	private $response;
	private $logService;
	private $requestCookies = [];			// cookies read from the request ; array of format name => value
	private $updatedCookies = [];			// same format as $requestCookies but for cookies that have been set by this function
	private $defaultLifetime = null;		// default lifetime for cookies when expires is null
};

?>