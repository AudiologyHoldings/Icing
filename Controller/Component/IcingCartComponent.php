<?php
/**
* IcingCart Component, session handling cart.
*
* Setup
*
* Create a config/recaptcha.php with the following
	$config = array(
	 'cart' => array(
		 'model' => 'Item',
		 'contain' => array('Status','Upload','Category'),
	 )
	);
*
* or define settings on load
*
* public $components = array('IcingCart' => array(
		'model' => 'Item', //Primary model to cart.
		'contain' => array('Status','Upload','Category') //Contain on your model to include in cart
  ));
*
* @version 1.1
* @author Nick Baker
* @license MIT
*/
App::uses('Component', 'Controller');
class IcingCartComponent extends Component {
	/**
	* Load the Session component
	*/
	public $components = array('Session');
  
  /**
	* Model defined in setup
	*/
	public $model = null;
	
	/**
	* Contain to put on model
	*/
	public $contain = null;
	
	/**
	* Actual model for use.
	*/
	private $Model = null;
	
	/**
	* Actual model for use.
	*/
	private $Controller = null;
	
	/**
	* Errors array
	*/
	private $errors = array();
	
	/**
	* The current state of the cart.
	*/
	private $cart = array();
	
	/**
	* Load Settings
	*/
	public function __construct(ComponentCollection $collection, $settings = array()){
		parent::__construct($collection, $settings);
		if(empty($settings)){
			Configure::load('cart');
			$this->_set(Configure::read('cart'));
		}	else {
			$this->_set($settings);
		}
		if(!$this->model){
			throw new NotFoundException('Model not defined.');
		}
	}
	
	/**
	* Load the model and contain variables.
	*/
	public function initialize(&$Controller){
		$this->Controller = $Controller;
		$this->cart = $this->get();
	}
	/**
	* Get the shopping cart
	* @return array current cart
	*/
	public function get(){
		return $this->Session->read("IcingCart") ? $this->Session->read("IcingCart") : array();
	}
	/**
	* Clears the cart
	* @return void
	*/
	public function clear(){
		$this->Session->write("IcingCart",null);
	}
	/**
  * Save the current cart
  */
  private function save(){
    $this->Session->write("IcingCart", $this->cart);
  }
  
	/**
	* Add the item to the cart
	* @param mixed item_id
	* @param boolean bypass (if true, bypass inCart check) allows for item to be in cart more than once
	* @return boolean success
	*/
	public function add($item_id = null, $bypass_check = false){
    if(!$item_id){
    	$this->error('No Item ID given.');
    	return false;
    }
    $this->loadModel();
    //Add the item to the cart.
    if($cart_item = $this->Model->findById($item_id)){
    	if($this->runCallback('allowCart', $cart_item)){
				if($bypass_check || !$this->inCart($cart_item)){
					$this->__runCallback('itterateCart', $cart_item);
					$this->cart[] = $cart_item;
					$this->save();
					return true;
				} else {
					
				}
			}
    } else {
      $this->error('Item not found');
    }
    return false;
  }
  
  /**
  * Remove the item from the cart
  * @param mixed item_id
  * @return boolean success
  */
  function remove($item_id = null){
    if(!$item_id){
      $this->error('No Item ID given.');
    	return false;
    }
    $this->loadModel();
    //remove the item from the cart
    if($cart_item = $this->Model->findById($item_id)){
    	$key = $this->inCart($cart_item);
      if($key !== false){
        $slice = array_splice($this->cart,$key,1); //remove the found cart item.
        $this->save();
        return true;
      }
    }
    return false;
  }
  
  /**
  * Search if the item is in the cart.
  * @param search item, full array item to search for
  * @return mixed false if not found, the key of the item in cart if found.
  */
  public function inCart($searchItem){
  	$this->loadModel();
    foreach($this->cart as $key => $item){
      if($item[$this->model][$this->Model->primaryKey] == $searchItem[$this->model][$this->Model->primaryKey]){
        return $key;
      }
    }
    return false;
  }
  
  /**
  * Get the errors of the component
  * @return array of errors
  */
  public function getErrors(){
  	return $this->errors;
  }
  
  /**
  * Loads the model
  */
  private function loadModel(){
  	if(!$this->Model && $this->model){
  		$this->Model = ClassRegistry::init($this->model);
  		$this->Model->contain($this->contain);
  	}
  }
  /**
  * Adds an error to the errors
  * @param string error message to add
  */
  private function error($message = null){
  	$this->errors[] = $message;
  }
  
  /**
	* Run the callback if it exists
	* @param string callback
	* @param mixed passed in variable (optional)
	* @return mixed result of the callback function
	*/ 
	private function runCallback($callback, $passedIn = null){
		$this->loadModel();
		if(is_callable(array($this->Model, $callback))){
			return call_user_func_array(array($this->Model, $callback), array($passedIn));
		}
		return true;
	}
}