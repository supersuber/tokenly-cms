<?php
namespace App\Account;
use App\API\V1;
class Home_Controller extends \App\ModControl
{	
    function __construct()
    {
        parent::__construct();
        $this->model = new Home_Model;
    }
    
    public function init()
    {
		$output = parent::init();
		
		if(!isset($_SESSION['accountAuth'])){
			if(isset($_COOKIE['rememberAuth'])){
				$log = $this->logRemembered();
				if($log){
					die();
				}
			}
			
			if(isset($this->args[1])){
				switch($this->args[1]){
					case 'verify';
						$output = $this->verifyAccount($output);
						break;
					case 'success':
						$output = $this->showSuccess($output);
						break;
					default:
						$output['view'] = '404';
						break;
				}
				return $output;
			}
			
			$output['view'] = 'auth';
			$output['loginForm'] = $this->model->getLoginForm();
			if(intval($this->data['app']['meta']['disableRegister']) === 1){
				$output['registerForm'] = false;
			}
			else{
				$output['registerForm'] = $this->model->getRegisterForm();
			}
			$output['loginMessage'] = '';
			$output['registerMessage'] = '';
			
			if(posted()){
				if(!isset($_POST['submit-type'])){
					die();
				}
				
				if($_POST['submit-type'] == 'login'){
					$output = $this->login($output);
				}
				elseif($output['registerForm']){
					$output = $this->register($output);
				}
			}
			
		}
		else{
			try{
				$userInfo = V1\Auth_Model::getUser(array('authKey' => $_SESSION['accountAuth'], 'site' => $this->data['site']));
			}
			catch(\Exception $e){
				redirect($this->site.$this->data['app']['url'].'/logout');
			}
			if(isset($_REQUEST['r'])){
				redirect($this->site.$_GET['r']);
			}
			else{
				redirect(route('account.dash-home'));
			}			
		}
		$output['title'] = 'Log In';
		return $output;
    }
    
    private function login($output)
    {
		$data = $output['loginForm']->grabData();
		$data['site'] = $this->data['site'];
		
		try{
			$login = $this->model->checkAuth($data);
		}
		catch(\Exception $e){
			$output['loginMessage'] = $e->getMessage();
			$login = false;
		}
		if($login){
			if(isset($_REQUEST['r'])){
				redirect($this->site.$_GET['r']);
			}
			else{
				redirect(route('account.dash-home'));
			}
		}
		return $output;
	}
	
	private function register($output)
	{
		if(intval($this->data['app']['meta']['disableRegister']) == 1){
			return $output;
		}
		
		$data = $output['registerForm']->grabData();
		$data['site'] = $this->data['site']['domain'];
		
		try{
			$register = $this->model->registerAccount($data);
		}
		catch(\Exception $e){
			$output['registerMessage'] = $e->getMessage();
			$register = false;
		}
		
		if($register){
			redirect($this->site.$this->data['app']['url'].'/success');
		}
		
		return $output;
	}
    
    public static function logRemembered()
    {	
		$model = new Home_Model;
		if(!isset($_COOKIE['rememberAuth'])){
			return false;
		}
		
		$auth = $_COOKIE['rememberAuth'];
		$expAuth = explode(':', $auth);
		if(count($expAuth) != 3){
			setcookie('rememberAuth', '', time()-3600, '/');
			return false;
		}
		$checksum = md5($expAuth[0].':'.$expAuth[1]);
		if($checksum != $expAuth[2]){
			setcookie('rememberAuth', '', time()-3600, '/');
			return false;
		}
		
		$decodeId = base64_decode($expAuth[1]);
		$get = $model->get('users', $decodeId);
		if(!$get){
			setcookie('rememberAuth', '', time()-3600, '/');
			return false;
		}
		
		$passHash = hash('sha256', $get['password'].$get['username']);
		if($passHash != $expAuth[0]){
			setcookie('rememberAuth', '', time()-3600, '/');
			return false;
		}

		$model->generateAuthToken($get['userId']);
		$url = $_SERVER['REQUEST_URI'];
		header('Location: '.$url);

		return true;
		
	}
    
    public function verifyAccount($output)
    {
		if(!isset($this->args[2]) OR trim($this->args[2]) == ''){
			$output['view'] = '404';
			return $output;
		}
		
		$getUser = $this->model->get('users', $this->args[2], array(), 'activate_code');
		if(!$getUser OR $getUser['activated'] == 1){
			$output['view'] = '404';
			return $output;
		}
		
		$this->model->edit('users', $getUser['userId'], array('activate_code' => '', 'activated' => 1));
		
		$output['view'] = 'verify-success';
		
		return $output;
	}
	
	public function showSuccess($output)
	{
		$output['view'] = 'register-success';
		$output['title'] = 'Register Account';
		
		return $output;
	}
}
