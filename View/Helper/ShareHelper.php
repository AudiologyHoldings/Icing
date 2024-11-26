<?php
/**
* Icing.Share helper generates javascript less links to share a page

	public $helpers = array('Icing.Share' => array(
		'popup' => false
	));

*
* @author Nick Baker <nick [at] webtechnick [dot] com>
* @version 1
* @license MIT
* @link http://www.webtechnick.com
*/
App::uses('AppHelper','View/Helper');
class ShareHelper extends AppHelper {
	/**
	* Helpers to load with this helper.
	*/
	public $helpers = array('Html');

	/**
	* Map of social services to links
	*/
	public $baseUrls = array(
		'twitter' => 'https://twitter.com/share?',
		'googleplus' => 'https://plus.google.com/share?',
		'facebook' => 'https://www.facebook.com/sharer/sharer.php?',
		'pinterest' => 'https://www.pinterest.com/pin/create/button/?',
	);
	
	/**
	* Default setting for javascript popup share window
	*/
	public $popup = true;

	/**
	* Default settings
	*/
	public $defaults = array(
		'popup' => true
	);

	/**
	* Add settings.
	* @example
		public $helpers = array('Icing.Share' => array(
			'popup' => false,
		));
	*/
	public function __construct(View $View, $settings = array()){
		if (empty($settings)) {
			$settings = $this->defaults;
		}
		return parent::__construct($View, $settings);
	}

	/**
	* Twitter share link generator
	*
	* @param array of options
	* - label: string label of the link (default to twitter image 32x32)
	* - linkOptions: array of options to pass into the link (default 'target' => '_blank', 'escape' => false)
	* - url: string of url to share (default to $this->here)
	* - text: string of text to autofill the share field
	* @return string link
	*/
	public function twitter($options = array()) {
		$options = array_merge(array(
			'label' => $this->Html->image('Icing.twitter.png'),
			'linkOptions' => array(
				'target' => '_blank',
				'escape' => false,
				'rel' => 'nofollow',
				),
			'url' => $this->defaultUrl(),
			'text' => '',
		), (array) $options);

		$data = array(
			'url' => $options['url'],
			'text' => $options['text']
		);
		$url = $this->buildShareUrl('twitter', $data);
		return $this->shareLink($options['label'], $url, $options['linkOptions']);
	}

	/**
	* Google Plus share link generator
	*
	* @param array of options
	* - label: string label of the link (default to googleplus image 32x32)
	* - linkOptions: array of options to pass into the link (default 'target' => '_blank', 'escape' => false)
	* - url: string of url to share (default to $this->here)
	* @return string link
	*/
	public function googleplus($options = array()) {
		$options = array_merge(array(
			'label' => $this->Html->image('Icing.googleplus.png'),
			'linkOptions' => array(
				'target' => '_blank',
				'escape' => false,
				'rel' => 'nofollow',
				),
			'url' => $this->defaultUrl(),
		), (array) $options);

		$data = array(
			'url' => $options['url'],
		);
		$url = $this->buildShareUrl('googleplus', $data);
		return $this->shareLink($options['label'], $url, $options['linkOptions']);
	}

	/**
	* Facebook share link generator
	*
	* @param array of options
	* - label: string label of the link (default to facebook image 32x32)
	* - linkOptions: array of options to pass into the link (default 'target' => '_blank', 'escape' => false)
	* - url: string of url to share (default to $this->here)
	* @return string link
	*/
	public function facebook($options = array()) {
		$options = array_merge(array(
			'label' => $this->Html->image('Icing.facebook.png'),
			'linkOptions' => array(
				'target' => '_blank',
				'escape' => false,
				'rel' => 'nofollow',
			),
			'url' => $this->defaultUrl(),
		), (array) $options);

		$data = array(
			'u' => $options['url'],
		);
		$url = $this->buildShareUrl('facebook', $data);
		return $this->shareLink($options['label'], $url, $options['linkOptions']);
	}

	/**
	* Pinterest share link generator
	*
	* @param array of options
	* - label: string label of the link (default to pinterest image 32x32)
	* - linkOptions: array of options to pass into the link (default 'target' => '_blank', 'escape' => false)
	* - image: string of image to pin (required)
	* - url: string of url to share (default to $this->here)
	* - text: string of text to autofill the share field
	* @return string link
	*/
	public function pinterest($options = array()) {

		$options = array_merge(array(
			'label' => $this->Html->image('Icing.pinterest.png'),
			'linkOptions' => array(
				'target' => '_blank',
				'escape' => false,
				'rel' => 'nofollow',
			),
			'url' => $this->defaultUrl(),
			'image' => '',
			'text' => '',
		), (array) $options);


		$data = array(
			'media' => $options['image'],
			'url' => $options['url'],
			'description' => $options['text']
		);
		$url = $this->buildShareUrl('pinterest', $data);
		return $this->shareLink($options['label'], $url, $options['linkOptions']);
	}

	/**
	* Build the Share Url
	* @param string service
	* @param array data to http encode
	* @return string of share url
	*/
	protected function buildShareUrl($service, $data) {
		if (!isset($this->baseUrls[$service])) {
			return null;
		}
		return $this->baseUrls[$service] . http_build_query($data);
	}
	
	/**
	* Get the current url
	* @return string full path url of current page.
	*/
	protected function defaultUrl() {
		return $this->Html->url($this->request->here, true);
	}

	/**
	* Share link
	* @access protected
	* @param string label
	* @param string url
	* @param array of options
	* @return string link
	*/
	protected function shareLink($label, $url, $options = array()) {
		if ($this->settings['popup'] && !isset($options['onclick'])) {
			$options['onclick'] = "javascript:window.open(this.href, '', 'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600'); return false;";
		}
		return $this->Html->link($label, $url, $options);
	}
}
