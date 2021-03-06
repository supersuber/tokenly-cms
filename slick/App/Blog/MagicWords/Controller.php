<?php
namespace App\Blog;
/*
 * @module-type = dashboard
 * @menu-label = Magic Words
 * 
 * */
 use App\Tokenly;
class MagicWords_Controller extends \App\ModControl
{
	function __construct()
	{
		parent::__construct();
		$this->model = new MagicWords_Model;
		$this->popModel = new Tokenly\POP_Model;
	}
	
	public function init()
	{
		$output = parent::init();
		$output['template'] = 'admin';
		$output['view'] = 'index';
		$output['form'] = $this->model->getWordForm();
		$output['message'] = '';
		
		if(posted()){
			$data = $output['form']->grabData();
			$data['userId'] = $this->data['user']['userId'];
			
			try{
				$submit = $this->model->submitMagicWord($data);
			}
			catch(\Exception $e){
				$submit = false;
				$output['message'] = $e->getMessage();
			}
			
			if($submit){
				$output['message'] = 'Correct! Word submitted successfully';
			}	
		}
		$output['words'] = $this->model->getUserWordSubmissions($this->data['user']['userId']);
		return $output;
	}
}
