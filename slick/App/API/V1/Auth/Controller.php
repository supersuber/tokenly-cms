<?php
namespace App\API\V1;
class Auth_Controller extends \Core\Controller
{
	public $methods = array('GET','POST');
	
	function __construct()
	{
		$this->model = new Auth_Model;
	}
	
	public function init($args = array())
	{
		$output = array();
		$this->args = $args;
		switch($this->useMethod){
			case 'POST':
				$output =  $this->authenticate();
				break;
			case 'GET':
				if(isset($this->args[1])){
					switch($this->args[1]){
						case 'logout':
							$output = $this->logout();
							break;
					}
				}
				else{
					$output = $this->getUser();
				}
				break;
			
		}
		return $output;
	}
	
	private function authenticate()
	{
		$output = array();
		try{
			$this->args['data']['isAPI'] = true;
			$auth = $this->model->checkAuth($this->args['data']);
		}
		catch(\Exception $e){
			$output['error'] = $e->getMessage();
			return $output;
		}
		
		$output['result'] = $auth;
		return $output;
	}
	
	private function getUser()
	{
		$output = array();
		$profModel = new \App\Profile\User_Model;
		
		try{
			$get = $this->model->getUser($this->args['data']);
		}
		catch(\Exception $e){
			$output['error'] = $e->getMessage();
			return $output;
		}
		$output['result'] = $get;
		return $output;
	}
	
	private function logout()
	{
		$output = array();
		try{
			$logout = $this->model->logout($this->args['data']);
		}
		catch(\Exception $e){
			$output['error'] = $e->getMessage();
			return $output;
		}
		$output['result'] = 'Success';
		return $output;
	}
}
