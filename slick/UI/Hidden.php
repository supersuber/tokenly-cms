<?php
namespace UI;
class Hidden extends FormObject
{
	function __construct($name, $id = '')
	{
		parent::__construct();
		$this->name = $name;
		$this->id = $id;
		
	}
	
	public function display($elemWrap = '')
	{

		$classText = '';
		if(count($this->classes) > 0){
			$classText = 'class="'.$this->getClassesText().'"';
		}
		
		$idText = '';
		if($this->id != ''){
			$idText = 'id="'.$this->id.'"';
		}
		
		$attributeText = $this->getAttributeText();
		
		$output = $this->label.'<input type="hidden" name="'.$this->name.'" '.$idText.' '.$classText.' '.$attributeText.' value="'.$this->value.'" />';
		
		if($elemWrap != ''){
			$misc = new Misc;
			$output = $misc->wrap($elemWrap, $output);
		}
		
		return $output;
	}	
	
	
}
