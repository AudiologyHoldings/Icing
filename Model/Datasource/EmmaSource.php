<?php
/**
* Emma DataSource
*
*/
App::uses('Emma','Vendor');
class EmmaSource extends DataSource{
	/**
	* Description of datasource
	*
	* @var string
	* @access public
	*/
	public $description = "Emma Data Source";

	public $errors = array();

	/**
	* Constructor
	*
	* Creates new HttpSocket
	*
	* @param array $config Configuration array
	* @access public
	*/
	public function __construct($config = array()) {
		parent::__construct($config);
		$this->initEmma();
	}

	public function initEmma() {
		$this->Emma = new Emma($this->config['account_id'],$this->config['public_key'], $this->config['private_key']);
	}

	protected function _request() {
		try {
			$req = $emma->membersRemoveSingle(111);
			return $this->_handleResult($req);
		} catch(Emma_Invalid_Response_Exception $e) {
			$this->errors[$e->getMessage()];
			return false;
		}
	}

	protected function _handleResult($result) {
		return json_decode($result);
	}
}