<?php
namespace App\CMS;
/*
 * @module-type = dashboard
 * @menu-label = Custom Profile Fields
 * 
 * */
class ProfileFields_Controller extends \App\ModControl
{
    public $data = array();
    public $args = array();
    
    function __construct()
    {
        parent::__construct();
        
        $this->model = new ProfileFields_Model;
         
    }
    
    public function init()
    {
		$output = parent::init();
        
        if(isset($this->args[2])){
			switch($this->args[2]){
				case 'view':
					$output = $this->showProfileFields();
					break;
				case 'add':
					$output = $this->addField();
					break;
				case 'edit':
					$output = $this->editField();
					break;
				case 'delete':
					$output = $this->deleteField();
					break;
				default:
					$output = $this->showProfileFields();
					break;
			}
		}
		else{
			$output = $this->showProfileFields();
		}
		$output['template'] = 'admin';
        
        return $output;
    }
    
    private function showProfileFields()
    {
		$output = array('view' => 'list');
		$getProfileFields = $this->model->getAll('profile_fields', array('siteId' => $this->data['site']['siteId']), array(), 'rank', 'asc');
		$output['fieldList'] = $getProfileFields;

		return $output;
	}
	
	
	private function addField()
	{
		$output = array('view' => 'form');
		$output['form'] = $this->model->getFieldForm();
		$output['formType'] = 'Add';
		
		if(posted()){
			$data = $output['form']->grabData();
			$data['siteId'] = $this->data['site']['siteId'];
			try{
				$add = $this->model->addField($data);
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
	
	private function editField()
	{
		if(!isset($this->args[3])){
			redirect($this->site);
		}
		
		$getField = $this->model->get('profile_fields', $this->args[3]);
		if(!$getField){
			redirect($this->site.$this->moduleUrl);
		}
		$getField['groups'] = array();
		$getGroups = $this->model->getAll('profile_fieldGroups', array('fieldId' => $this->args[3]));
		foreach($getGroups as $group){
			$getField['groups'][] = $group['groupId'];
		}
		
		$output = array('view' => 'form');
		$output['form'] = $this->model->getFieldForm($this->args[3]);
		$output['formType'] = 'Edit';
		
		if(posted()){
			$data = $output['form']->grabData();
			$data['siteId'] = $this->data['site']['siteId'];
			try{
				$add = $this->model->editField($this->args[3], $data);
			}
			catch(\Exception $e){
				$output['error'] = $e->getMessage();
				$add = false;
			}
			
			if($add){
				redirect($this->site.$this->moduleUrl);
			}
		}
		$output['form']->setValues($getField);
		return $output;
	}
	
	private function deleteField()
	{
		if(isset($this->args[3])){
			$getField = $this->model->get('profile_fields', $this->args[3]);
			if($getField){
				$delete = $this->model->delete('profile_fields', $this->args[3]);
			}			
		}
		redirect($this->site.$this->moduleUrl);
	}
}
