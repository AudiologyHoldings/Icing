<?php
/**
* Icing.Twitter helper generates links and loads javascript

Create settings file or use onload settings.

Config/twitter.php
$config = array(
	'Twitter' => array(
		'handle' => 'WebTechNick',
		'locale' => 'en',
		'buffer' => true,
	)
);

public $helpers = array('Icing.Twitter' => array(
	'handle' => 'WebTechNick',
	'locale' => 'en',
	'buffer' => true,
));


*
* @author Nick Baker <nick [at] webtechnick [dot] com>
* @version 1
* @license MIT
* @link http://www.webtechnick.com
*/
App::uses('AppHelper','View/Helper');
class TwitterHelper extends AppHelper {
	/**
	* Helpers to load with this helper.
	*/
	public $helpers = array('Html', 'Session');
	
	/**
	* Variable to track if we've loaded the javascript or not.
	*/
	public $loaded = false;
	
	/**
	* Base URL for twitter api
	*/
	public $baseUrl = 'https://twitter.com';
	
	/**
	* Default settings.
	*/
	public $defaults = array(
		'handle' => 'WebTechNick',
		'locale' => 'en',
		'buffer' => true,
	);
	
	/**
  * Add domain settings.
  * @example 
  public $helpers = array('Icing.Twitter' => array(
  	'handle' => 'WebTechNick',
  	'locale' => 'en',
  	'buffer' => true,
   ));
  */
  public function __construct(View $View, $settings = array()){
  	if(empty($settings)){
  		try{
				if(Configure::load('twitter')){
					$settings = Configure::read('Twitter');
				}
  		} catch(Exception $e){
  		}
  	}
  	if(empty($settings)){
  		$settings = $this->defaults;
  	}
  	return parent::__construct($View, $settings);
  }
	
	/**
	* Show a share tweet button
	* @param string text to put into button
	* @param mixed url to share (default is $this->here)
	* @param options 
	*  text - text default text of tweet. (default title)
	*  large - boolean show large button (default false)
	*/
	public function share($text = 'Tweet', $url = null, $options = array()){
		$button_url = '/share';
		$button_options = array('class' => 'twitter-share-button');
		if($url === null){
			$url = $this->here;
		}
		$button_options['data-url'] = Router::url($url, true);
		$button_options = array_merge($this->parseOptionsToButtonOptions($options), $button_options);
		return $this->button($text, $button_url, $button_options);
	}
	
	/**
	* Follow button
	* @param string text text to show
	* @param array of options
	*/
	public function follow($text = null, $options = array()){
		$button_url = '/' . $this->settings['handle'];
		$button_options = array('class' => 'twitter-follow-button');
		if($text == null){
			$text = 'Follow @' . $this->settings['handle'];
		}
		$button_options = array_merge($this->parseOptionsToButtonOptions($options), $button_options);
		return $this->button($text, $button_url, $button_options);
	}
	
	/**
	* Loads the javascript, will only load if hasn't been loaded before.
	* @param boolean overwrite, if true will return the script call again (default false)
	* @return string of script tag loading twitter library.
	*/
	public function init($overwrite = false){
		$retval = "";
		if($overwrite || !$this->loaded){
			$script = "!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');";
			$retval = $this->Html->scriptBlock($script);
			$this->loaded = true;
		}
		return $retval;
	}
	
	/**
	* This is the actual output button
	* @param text to put into button
	* @param url to link in button
	* @param array of options
	* @return script tag of all share features.
	*/
	protected function button($text = 'Tweet', $url = null, $options = array()){
		$options = array_merge(array(
				'data-lang' => $this->settings['locale'],
				'data-via' => $this->settings['handle'],
			),(array)$options
		);
		return $this->Html->link($text, $this->baseUrl . $url, $options) . $this->init();
	}
	
	/**
	* Parse options
	* @param array of options
	*  text - text default text of tweet
	*  large - boolean show large button
	*  count - boolean show count
	*  hashtags - mixed array or string of hashtags to add
	*  related - mixed array or string of related handles
	*/
	private function parseOptionsToButtonOptions($options = array()){
		$button_options = array();
		if(isset($options['text'])){
			$button_options['data-text'] = $options['text'];
		}
		if(isset($options['large']) && $options['large']){
			$button_options['data-size'] = 'large';
		}
		if(isset($options['count']) && $options['count']){
			$button_options['data-show-count'] = 'true';
		}
		if(isset($options['hashtags'])){
			if(is_array($options['hashtags'])){
				$button_options['data-hashtags'] = implode(",",$options['hashtags']);
			} else {
				$button_options['data-hashtags'] = $options['hashtags'];
			}
		}
		if(isset($options['related'])){
			if(is_array($options['related'])){
				$button_options['data-related'] = implode(",",$options['related']);
			} else {
				$button_options['data-related'] = $options['related'];
			}
		}
		return $button_options;
	}
}
