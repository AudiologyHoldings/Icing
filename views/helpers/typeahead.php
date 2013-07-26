<?php
/**
 * this is a generic helper for Simucase
 */
Class TypeaheadHelper extends AppHelper {

	public $helpers = array(
		'Output',
		'Form',
		'TwitterBootstrap',
	);

	/**
	 * Placeholder so we never double-load assets
	 *
	 */
	public $assetsLoaded = false;

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
		return $this->Output->script('/files/simucase/js/libs/external/bootstrap-typeahead.js');
	}

	/**
	 * This is a simple input helper, which passed through the TwitterBootstrap
	 * helper and ensures all parts and peices needed for typeahead are
	 * available and setup correctly
	 *
	 * NOTE: on form submission, you end up with a "name" not an ID...
	 *   you'll have to translate it out on save
	 *
	 * @param string $fieldName
	 * @param array $options or string $source
	 * @return string $html
	 * @link http://jasny.github.io/bootstrap/javascript.html#typeahead
	 */
	public function input($fieldName, $options = null) {
		$defaults = array(
			'type' => 'text',
			'data-provide' => 'typeahead',
			'id' => 'typeahead' . String::uuid(),
			// this is the url to the source for autocomplete
			'source' => $this->Output->url(array('action' => 'typeahead', 'as.json')),
		);
		if (is_string($options)) {
			$options = array('source' => $options);
		}
		if (empty($options) || !is_array($options)) {
			$options = array();
		}
		$options = array_merge($defaults, $options);
		if (empty($options['source'])) {
			throw new OutOfBoundsException("TokeninputHelper::input($fieldName) you must provide a source");
		}
		extract($options);

		// options for the typeahead
		//   http://jasny.github.io/bootstrap/javascript.html#typeahead
		$jsOptions = compact( 'source', 'items', 'minLength', 'ajaxdelay' );
		// options for the form input, exclude the jsOptions & 'source'
		$options = array_diff_key($options, $jsOptions + array('source' => 0));
		foreach ($jsOptions as $k => $v) {
			$options["data-{$k}"] = $v;
		}

		// simple and easy - data-attributes trigger functionality
		return $this->assets() .
			$this->TwitterBootstrap->input($fieldName, $options);

		/*
		// each input would get JS function to init
		$js = '$("#'. $options['id'] . '").typeahead("' . $source  . '", ' . json_encode($jsOptions) . ');';
		return $this->assets() .
			$this->TwitterBootstrap->input($fieldName, $options) .
			$this->Output->scriptBlock($js);
		*/
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
