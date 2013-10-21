<?php
namespace Errors;

class Errors {
	private $topic;
	private $messages = [];
	private $config;
	private $separation;

	public function __construct ($config, $topic, $separation) {
		$this->topic = $topic;
		$this->config = $config;
		$this->separation = $separation;
	}

	public function check () {
		if (count($this->messages) > 0) {
			return false;
		}
		return true;
	}

	public function push ($message) {
		$this->messages[] = $message;
	}

	public function exception ($e) {
		$project = $this->config->project;
		$root = (($sapi == 'cli') ? getcwd() : $_SERVER['DOCUMENT_ROOT']);
		$context = [
			'code'			=> uniqid(),
			'root'			=> $root,
			'created_date'	=> date('Y-m-d H:i:s'),
			'message'		=> $e->getMessage(),
			'file'			=> $e->getFile(),
			'line'			=> $e->getLine(),
			'stack'			=> (array)$e->getTrace(),
			'get'			=> (isset($_GET) ? $_GET : []),
			'post'			=> (isset($_POST) ? $_POST : []),
			'session'		=> (isset($_SESSION) ? $_SESSION : []),
			'server'		=> (isset($_SERVER) ? $_SERVER : [])
		];
		$topic->publish('exception', $context);
		$mode = 'production';
		if (isset($project['mode'])) {
			$mode = $project['mode'];
		}
		if ($mode == 'development' || php_sapi_name() == 'cli') {
			print_r($context);
			exit;
		}
		$this->separation->app('errors')->layout('errors-' . $mode)->data('errors', $context)->write();
		exit;
	}

	public function get () {
		return $this->messages;
	}
}