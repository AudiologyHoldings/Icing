<?php
/**
  * Attach to any model to add an sendEmail function.
  *
  * Example Usage:
  * @example
  * public $actsAs = array('Icing.Emailable');
  *
  *	Send an Email
  	@example
  	$this->Model->sendEmail(
  		'test@example.com', //to
  		'from@example.com', //from
  		'template', //in View/Emails/html/template.ctp
  		'Subject', //subject
  		array(
  			'data' => 'some_data'
  		), //viewVars ($data = 'some_data') (default array())
  		array(
  			'/path/to/attachments'
  		), //attachments (default array())
  		'default', //default email configuration in /Config/email.php (default: default)
  		array(
  			'Html','Time','Number'
  		) //Helpers Available to the View, (Default Html, Time, Number)
  	);

  * @version: since 1.0
  * @author: Nick Baker
  * @link: http://www.webtechnick.com
  */
App::uses('IcingUtil', 'Icing.Lib');
class EmailableBehavior extends ModelBehavior {
	/**
	* Setup the behavior
	*/
	public function setUp(Model $Model, $settings = array()){
		$settings = array_merge(array(
		), (array)$settings);
		$this->settings[$Model->alias] = $settings;
	}
	
	/**
	* Send the email using CakeEmail
	* @param mixed to
	* @param string from
	* @param string template
	* @param string subject
	* @param array viewVars to give to the template
	* @param array attachments of attachments
	* @param array helpers available to the template
	*/
	public function sendEmail(Model $Model, $to, $from, $template, $subject, $viewVars = array(), $attachments = array(), $config = 'default', $helpers = array()){
		$this->setupEmail($config, $helpers);
		$this->Email->to($to);
		$this->Email->subject($subject);
		$this->Email->from($from);
		$this->Email->template($template, 'default'); //in /View/Email/html/comment.ctp
		$this->Email->emailFormat('html');
		$this->Email->viewVars($viewVars);
		if(!empty($attachments)){
			$this->Email->attachments($attachments);
		}
		return $this->Email->send();
	}
	
	private function setupEmail($config = 'default', $helpers = array()){
		if (empty($helpers)) {
			$helpers = array('Html','Number','Time');
		}
		App::uses('CakeEmail', 'Network/Email');
		$this->Email = new CakeEmail($config);
		$this->Email->helpers($helpers);
	}
	
	/**
	* array options to send a plain email
	*/
	public function sendPlainEmail(Model $Model, $options = array()) {
		$options = array_merge(
			array(
				'to' => null,
				'from' => null,
				'replyTo' => null,
				'subject' => null,
				'message' => null,
				'attachments' => array(),
				'helpers' => array(),
				'config' => 'default'
			),
			(array) $options
		);
		if (!empty($options['to']) && !empty($options['from']) && !empty($options['message']) && !empty($options['subject'])) {
			$this->setupEmail($options['config'], $options['helpers']);
			$this->Email->to($options['to']);
			$this->Email->from($options['from']);
			$this->Email->subject($options['subject']);
			if (!empty($options['replyTo'])) {
				$this->Email->replyTo($options['replyTo']);
			}
			if (!empty($options['attachments'])) {
				$this->Email->attachments($options['attachments']);
			}
			return $this->Email->send($options['message']);
		}
		return false;
	}
}
