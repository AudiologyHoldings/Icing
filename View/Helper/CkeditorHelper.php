<?php
class CkeditorHelper extends AppHelper{
	var $helpers = array('Html');
	var $loaded = false;
	var $js_var = 'wtn_editor';
	
	function load(){
		$this->loaded = true;
		return $this->Html->script('/ckeditor/ckeditor');
	}
	
	/**
	* @example $this->Ckeditor->replace('Field', array('ckfinder' => true));
	* @example $this->Ckeditor->replace('Field', array('toolbar' => 'Basic'));
	*/ 
	function replace($id = null, $options = array()){
		$retval = "";
		if(!$this->loaded){
			$retval .= $this->load();
		}
		
		$varname = 'wtn_editor';
		if(isset($options['var_name'])){
			$varname = $options['var_name'];
			unset($options['var_name']);
		}
		
		if(isset($options['ckfinder']) && $options['ckfinder']){
			unset($options['ckfinder']);
			$options['filebrowserBrowseUrl'] = '/ckfinder/ckfinder.html';
			$options['filebrowserImageBrowseUrl'] = '/ckfinder/ckfinder.html?Type=Images';
      $options['filebrowserFlashBrowseUrl'] = '/ckfinder/ckfinder.html?Type=Flash';
      $options['filebrowserUploadUrl'] = '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files';
      $options['filebrowserImageUploadUrl'] = '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images';
      $options['filebrowserFlashUploadUrl'] = '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash';
		}
		
		$options = json_encode(array_merge(
			array(
				//'skin' => 'kama',
			),
			$options
		));
		
		$retval .= $this->Html->scriptBlock(
			"var $varname = CKEDITOR.replace('$id', $options)"
		);
		
		return $retval; 
	}
	
	/**
	* Destroy the editor
	*/
	function destroy($var_name = 'wtn_editor'){
		return $this->Html->scriptBlock("
			$var_name.destroy();
		");
	}
}