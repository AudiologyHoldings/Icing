<?php
/**
 * this is a generic helper for Tokeninput / Tokeninputable
 */
App::uses('AppHelper','View/Helper');
Class TokeninputHelper extends AppHelper {

	public $helpers = array(
		'Html',
		'Form',
		// optionally use the TwitterBootstrap helper for form elements
		'TwitterBootstrap',
	);

	/**
	 * The display field may not always be "name"
	 * it could be "title" or some virutal field
	 *
	 * We need this to translated it into "name"
	 *
	 * NOTE: if you have to concat multiple fields,
	 * do it before you pass into this helper, perhaps via a virtual field
	 */
	public $displayField = 'name';

	/**
	 * The primaryKey is ususally 'id'
	 * but just in case it isn't, you can set it here
	 */
	public $primaryKey = 'id';

	/**
	 * Which form helper to use
	 */
	public $formHelper = 'TwitterBootstrap';

	/**
	 * Placeholder so we never double-load assets
	 */
	public $assetsLoaded = false;

	/**
	 * path to assests files
	 */
	public $assetsPath = '/tokeninput/';

	/**
	 * assets loading method options
	 */
	public $assetsOptions = array('inline' => false);

	/**
	 * Load the important, required assets
	 *
	 * @return string $html
	 */
	public function assets() {
		if ($this->assetsLoaded) {
			return '';
		}
		$this->assetsLoaded = true;
		return $this->Html->css($this->assetsPath . 'jquery.tokeninput.css') .
			$this->Html->script($this->assetsPath . 'jquery.tokeninput.js', $this->assetsOptions);
	}

	/**
	 * This is a simple input helper, which passed through the TwitterBootstrap
	 * helper and ensures all parts and peices needed for tokeninput are
	 * available and setup correctly
	 *
	 * Be aware that you have to pass in a $prePopulate array
	 *   either in the $options as a key, or as a 3rd param
	 *   and it should be a simple list of keys=>names
	 *
	 * It also populates JS onto the HtmlHelper for each tokeninput
	 *
	 * NOTE: if an ID isn't passed in, we generate a random one
	 *
	 * NOTE: on form submission, you end up with a CSV string of ids from this
	 *   your save handling will have to be smart enough to split into records
	 *
	 * @param string $fieldName
	 * @param array $options or string $source
	 * @param mixed $prePopulate data (optional, can be part of the $options array too)
	 * @return string $html
	 * @link http://loopj.com/jquery-tokeninput/ #Configuration
	 */
	public function input($fieldName, $options = null, $prePopulate = null) {
		$defaults = array(
			'type' => 'text',
			'data-provide' => 'tokeninput',
			'id' => 'tokeninput' . CakeText::uuid(),
			// this is the url to the source for autocomplete
			'source' => $this->Html->url(array('action' => 'tokeninput', 'as.json')),
			// pass in a simple array(array(id=>__, name=>__))
			// or pass in a simple array(id=>__, name=>__) and we will transform
			'prePopulate' => $prePopulate,
			// theme, has to match with CSS linked in assets
			'theme' => 'facebook',
		);
		if (is_string($options)) {
			$options = array('source' => $options, 'prePopulate' => $prePopulate);
		}
		if (empty($options) || !is_array($options)) {
			$options = array();
		}
		$options = array_merge($defaults, $options);
		if (empty($options['source'])) {
			throw new OutOfBoundsException("TokeninputHelper::input($fieldName) you must provide a source");
		}
		// cleanup and reformat prePopulate data
		$options['prePopulate'] = $this->prePopulate($options['prePopulate']);
		extract($options);

		// options for the tokenInput
		//   http://loopj.com/jquery-tokeninput/ #Configuration
		$jsOptions = compact( 'method', 'queryParam', 'searchDelay', 'minChars',
			'propertyToSearch', 'jsonContainer', 'crossDomain', 'prePopulate',
			'hintText', 'noResultsText', 'searchingText', 'deleteText',
			'animateDropdown', 'theme', 'resultsLimit' );
		$js = '$("#'. $options['id'] . '").tokenInput("' . $source  . '", ' . json_encode($jsOptions) . ');';

		// options for the form input, exclude the jsOptions & 'source'
		$options = array_diff_key($options, $jsOptions + array('source' => 0));

		return $this->assets() .
			$this->{$this->formHelper}->input($fieldName, $options) .
			$this->Html->scriptBlock($js, array('inline' => false));
	}

	/**
	 * Determines what format you put in for existing data
	 * and corrects it to the expected format for tokeninput
	 *
	 * Input can be a simple list find where ids are keys and names are values
	 * (simplest)
	 *
	 * Input can also be formatted as:
	 *   array(array(id=>__, name=>__))
	 *
	 * If the latter, the id and name fields are determined by the properties:
	 *   primaryKey & displayField on this helper
	 *
	 * @param array $prePopulate
	 * @return array $prePopulate
	 *   correct array(array(id=>__, name=>__))
	 *
	 */
	public function prePopulate($prePopulate) {
		if (!empty($prePopulate) && is_string($prePopulate)) {
			$prePopulate = json_decode($prePopulate, true);
		}
		if (empty($prePopulate) || !is_array($prePopulate)) {
			return array();
		}
		// are we formatted as a correct array(array(id=>__, name=>__))
		$node = current($prePopulate);
		if (is_array($node) && !empty($node[$this->primaryKey])) {
			// formatted correcty, but lets still extract
			// because we might be translating displayField
			$ids = Set::extract($prePopulate, '/' . $this->primaryKey);
			$names = Set::extract($prePopulate, '/' . $this->displayField);
		}
		if (!is_array($node)) {
			// we assume data is formatted as a simple array(id=>__, name=>__) and we will transform
			$ids = array_keys($prePopulate);
			$names = array_values($prePopulate);
		}
		if (empty($ids)) {
			return array();
		}
		$prePopulate = array();
		foreach (array_keys($ids) as $i) {
			$prePopulate[$i] = array(
				'id' => $ids[$i],
				'name' => (isset($names[$i]) ? $names[$i] : ''),
			);
		}
		return $prePopulate;
	}

}
