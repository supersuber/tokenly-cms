<?php
namespace App\CMS;
/*
 * @module-type = dashboard
 * @menu-label = Themes
 * 
 * */
class Themes_Controller extends \App\ModControl
{
    public $data = array();
    public $args = array();
    
    function __construct()
    {
        parent::__construct();
        
        $this->model = new Themes_Model;
 
    }
    
    public function init()
    {
		$output = parent::init();
        
        if(isset($this->args[2])){
			switch($this->args[2]){
				case 'view':
					$output = $this->showThemes();
					break;
				case 'add':
					$output = $this->addTheme();
					break;
				case 'edit':
					$output = $this->editTheme();
					break;
				case 'delete':
					$output = $this->deleteTheme();
					break;
				default:
					$output = $this->showThemes();
					break;
			}
		}
		else{
			$output = $this->showThemes();
		}
		$output['template'] = 'admin';
        
        return $output;
    }
    
    private function showThemes()
    {
		$output = array('view' => 'list');
		
		$getThemes = $this->model->getAll('themes');
		foreach($getThemes as $k => $t){
			if($t['themeId'] == $this->data['site']['themeId']){
				$getThemes[$k]['active'] = 1;
			}
			else{
				$getThemes[$k]['active'] = 0;
			}
		}
		$output['themeList'] = $getThemes;

		
		return $output;
		
	}
	
	
	private function addTheme()
	{
		$output = array('view' => 'form');
		$output['form'] = $this->model->getThemeForm();
		$output['formType'] = 'Add';
		
		if(posted()){
			$data = $output['form']->grabData();
			$data['siteId'] = $this->data['site']['siteId'];
			try{
				$add = $this->model->addTheme($data);
			}
			catch(\Exception $e){
				$output['error'] = $e->getMessage();
				$add = false;
			}
			
			if($add){
				redirect($this->site.$this->moduleUrl);
			}
		}
		return $output;
	}
	
	private function editTheme()
	{
		if(!isset($this->args[3])){
			redirect($this->site);
		}
		
		$getTheme = $this->model->get('themes', $this->args[3]);
		if(!$getTheme){
			redirect($this->site.$this->moduleUrl);
		}
		
		$output = array('view' => 'form');
		$output['form'] = $this->model->getThemeForm($this->args[3]);
		$output['formType'] = 'Edit';
		
		if(posted()){
			$data = $output['form']->grabData();
			$data['siteId'] = $this->data['site']['siteId'];
			try{
				$add = $this->model->editTheme($this->args[3], $data);
			}
			catch(\Exception $e){
				$output['error'] = $e->getMessage();
				$add = false;
			}
			
			if($add){
				redirect($this->site.$this->moduleUrl);
			}
		}
		
		if($this->data['site']['themeId'] == $getTheme['themeId']){
			$getTheme['active'] = 1;
		}
		else{
			$getTheme['active'] = 0;
		}
		$output['form']->setValues($getTheme);
		
		return $output;
	}
	
	private function deleteTheme()
	{
		if(isset($this->args[3])){
			if($this->model->count('themes') > 1){
				$getTheme = $this->model->get('themes', $this->args[3]);
				if($getTheme AND $getTheme['themeId'] != $this->data['themeData']['themeId']){
					$delete = $this->model->delete('themes', $this->args[3]);
				}
			}
		}
		redirect($this->site.$this->moduleUrl);
	}
}
