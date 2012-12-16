<?php
namespace Niysu\Services;

class DebugPanelService {
	public function __construct(&$response, $scope) {
		if (!$response)
			throw new \LogicException('DebugPanelService can\'t be used outside of a route');

		$response = new \Niysu\HTTPResponseCustomFilter($response, function($data) use ($scope) {
			if (!$this->active)
				return;
			if (substr($data->getHeader('Content-Type'), 0, 9) != 'text/html')
				return;

			if (preg_match('/\\<\\/body\\>/i', $data->getData(), $matches, PREG_OFFSET_CAPTURE)) {
				$timeElapsed = call_user_func($scope->elapsedTime);
				$dbConnectionTime = 'unknown';
				eval('$evaluatedPanel = "'.addslashes(self::$panelTemplate).'";');

				$splitOffset = $matches[0][1];
				$newContent = substr($data->getData(), 0, $splitOffset).$evaluatedPanel.substr($data->getData(), $splitOffset);
				$data->setData($newContent);
			}
		});
	}

	public function activate() {
		$this->active = true;
	}

	public function deactivate() {
		$this->active = false;
	}


	private $active = false;

	private static $panelTemplate =
		'<div style="position:fixed; left:0; bottom:0; width:100%; padding:0.5em 1em; background-color:gray; border-top:2px double black;">
			$timeElapsed ms - $dbConnectionTime ms
		</div>';
};

?>