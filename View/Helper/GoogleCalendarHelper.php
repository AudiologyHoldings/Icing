<?php
/**
* Google Calendar Reminder link generator
* @author Nick Baker
* @version 1.0
* @license MIT
*/
App::uses('AppHelper','View/Helper');
class GoogleCalendarHelper extends AppHelper{
	/**
	* Helpers to load
	*/
  public $helpers = array('Html');
  
  /**
  * Url to google calendar
  */
  public $url = "http://www.google.com/calendar/event?";
  
  /**
  * Map to google images
  */
  public $images = array(
  	'small' => 'http://www.google.com/calendar/images/ext/gc_button1.gif',
  	'large' => 'http://www.google.com/calendar/images/ext/gc_button6.gif',
  );
  
  /**
  * Return an image url to click for a google reminder.
  * @param string image
  * @param array of options
  * - start: start date (will encode to UTC, default to same day if empty)
  * - end: end date (will be encoded to UTC, default is same day if empty)
  * - title: string title of the event
  * - details: string details of the event.
  * - add: mixed string or array of email address to invite to event
  * - location: string location of event
  * - busy: boolean default true
  * - image_options: array of options to be passed into HtmlHelper::image
  * - link_options: array of options to be passed into HtmlHelper::link
  * - url_only: boolean, if true will return the reminder google link.
  * @example
  	$this->GoogleCalendar->reminder('small', array(
			'start' => 'tomorrow',
			'end' => 'tomorrow',
			'title' => 'Test Event',
			'details' => 'Details of Event',
			'location' => 'Albuquerque, NM',
			'add' => 'nurvzy@gmail.com'
		));
		@example
		$this->GoogleCalendar->reminder('small', array(
			'start' => 'Aug 15th, 2013 8:00pm',
			'end' => 'Aug 15th, 2013 9:00pm',
			'title' => 'Test Event',
			'details' => 'Details of Event',
			'location' => 'Albuquerque, NM',
			'add' => array('nurvzy@gmail.com', 'nick.baker@audiologyholdings.com')
		));
  */
  public function reminder($image = null, $options = array()){
  	if(!is_array($image) && isset($this->images[$image])){
  		$image = $this->images[$image];
  	}
  	if(empty($image)){
  		$image = $this->images['small'];
  	}
  	$today = date('Y-m-d');
  	$options = array_merge(array(
  		'start' => $today,
  		'end' => $today,
  		'title' => 'Untitled',
  		'details' => '',
  		'add' => '',
  		'location' => '',
  		'busy' => true,
  		'image_options' => array(),
  		'link_options' => array('escape' => false),
  		'url_only' => false,
  	), $options);
  	
  	//Build the query from options
  	$query = array();
  	$format = 'Ymd\THis\Z';
  	$query['action'] = 'TEMPLATE';
  	$query['dates'] = gmdate($format, strtotime($options['start'])) . '/' . gmdate($format, strtotime($options['end']));
  	$query['text'] = $options['title'];
		if(!empty($options['details'])){
			$query['details'] = $options['details'];
		}
  	if(!empty($options['location'])){
  		$query['location'] = $options['location'];
  	}
  	if($options['busy']){
  		$query['trp'] = 'true';
  	}
  	$addstring = "";
  	if(!empty($options['add'])){ 
  		if(is_array($options['add'])){
  			$addstring = '&add=' . implode('&add=', $options['add']);
  		} else {
  			$addstring = '&add=' . $options['add'];
  		}
  	}
  	$url = $this->url . http_build_query($query) . $addstring; 
  	if($options['url_only']){
  		return $url;
  	}
  	return $this->Html->link($this->Html->image($image, $options['image_options']), $url, $options['link_options']);
  }
}