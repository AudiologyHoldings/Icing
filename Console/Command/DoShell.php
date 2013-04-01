<?php
/**
 * This is a general utility shell to "do" any method on any Model
 *
 * Obviously, you can not easily pass in arrays or complex data as inputs,
 * but with this, you can easily access methods on your models via CLI
 * without having * to create custom shells for them
 *
 * NOTE: this assumes you have a secure environment
 * this obviously gives the keys to your application and database to anyone
 * who can execute this shell (eg: read, delete, query, etc)
 *
 * Only install if you "trust" your server setup and everyone who has shell
 * access
 *
 * Basic usage:
 *   ./cake Icing.do Model Method
 *   ./cake Icing.do Model Method Param1 Param2
 *
 * @compatible with CakePHP 2x (should be easy to downgrade to 1.2)
 * @author alan@zeroasterisk.com
 * @link https://github.com/AudiologyHoldings/Icing
 */
App::uses('ClassRegistry', 'Utility');
App::uses('AppShell', 'Console/Command');
class DoShell extends AppShell {

	/**
	 * var array
	 * (not set here, Model will be passed in as first arg)
	 */
	public $uses = array();

	/**
	 * cake standardized argument parser
	 * autosets basic help information
	 * (because of the required Model parameters)
	 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addArguments(array(
			'model' => array(
				'help' => 'Model Name (or Plugin.Model)',
				'required' => true,
			),
			'method' => array(
				'help' => 'The Model\'s method',
				'required' => false,
			),
			'params' => array(
				'help' => 'optional params to pass',
				'required' => false,
			)
		));
		$parser->addOptions(array(
			'plugin' => array(
				'short' => 'p',
				'help' => '(optional) The plugin within witch to look for this model',
			),
			'behavior' => array(
				'short' => 'b',
				'help' => '(optional) Attach this behavior on setup',
			),
			'json' => array(
				'short' => 'j',
				'help' => '(optional) JSON array of parameters specified rather than a literal string',
			),
		));
		return $parser;
	}

	/**
	 * pass in at least two args
	 * @param string $modelName
	 * @param string $methodName
	 * @params... string params to pass to methodName
	 */
	function main() {
		extract($this->params);
		$this->out("Icing.do Shell");
		$this->hr();
		$modelName = array_shift($this->args);
		if (empty($modelName)) {
			$this->help();
			return $this->error('Error: missing modelName (first parameter)');
		}
		App::uses('Core', 'ClassRegistry');
		if (!empty($plugin)) {
			App::uses('Model', $plugin.'.'.$modelName);
			$Model = ClassRegistry::init($plugin.'.'.$modelName);
		} else {
			App::uses('Model', $modelName);
			$Model = ClassRegistry::init($modelName);
		}
		if (empty($Model) || !is_object($Model)) {
			return $this->error('Error: unable to register modelName (internal error 1)');
		}
		// set the Model onto this shell, so we can extend help
		$this->Model = $Model;
		if (!empty($behavior)) {
			$Model->Behaviors->attach($behavior);
		}
		$methodName = array_shift($this->args);
		if (empty($methodName)) {
			$this->help();
			return $this->error("Error: missing methodName (second parameter)");
		}
		// check to see if we are actually triggering help
		if ($methodName=='help' || !empty($help)) {
			$this->help();
			return true;
		}
		if (empty($this->attach) && !method_exists($Model, $methodName)) {
			$this->help();
			return $this->error("Error: methodName [{$methodName}] doesn't exist on Model {$modelName} (internal error 2)");
		}
		if (!empty($json)) {
			$args = json_decode($this->args[0]);
		} else {
			$args = $this->args;
		}
		$this->out("<info>Running: {$Model->name}->{$methodName}(".json_encode($args).")</info>");
		$response = call_user_func_array(array($Model, $methodName), $args);
		if ($response===false) {
			$this->out("<error>Failure to run: {$Model->name}->{$methodName}()</error>");
		} else {
			$this->out("Success: ".json_encode($response));
		}
		if (isset($Model->errors) && !empty($Model->errors)) {
			$this->out("Errors: ".json_encode($Model->errors));
		}
		if (isset($Model->validationErrors) && !empty($Model->validationErrors)) {
			$this->out("Validation Errors: ".json_encode($Model->validationErrors));
		}
		$this->out();
	}

	/**
	 * Extra help information
	 */
	function help() {
		$this->out("HELP");
		$this->out();
		$this->out("  cake Icing.do \$modelName help");
		$this->out("   ^ Lists available methods on Model");
		$this->out("  cake Icing.do \$modelName \$methodName [\$parameter] [\$parameter]");
		$this->out("    ^ executes any method on any model, optionally specifying parameters (as strings)");
		$this->out("  cake Icing.do '\$pluginName.\$modelName' \$methodName [\$parameter] [\$parameter]");
		$this->out("    ^ a syntax for executing a Plugin.Model's method");
		$this->out("  cake Icing.do -p \$pluginName \$modelName \$methodName [\$parameter] [\$parameter]");
		$this->out("    ^ alt syntax for executing a Plugin.Model's method");
		$this->out("  cake Icing.do -b \$behaviorName \$modelName \$methodName [\$parameter] [\$parameter]");
		$this->out("    ^ attaches any behaviour before executing a method");
		$this->out();
		// extend help with details on the Model
		if (!empty($this->Model)) {
			$classMethods = get_class_methods($this->Model);
			$appModelMethods = get_class_methods('AppModel');
			$classMethods = array_diff($classMethods, $appModelMethods);
			$this->out("<info>available methods</info>");
			foreach ($classMethods as $methodName) {
				if (substr($methodName, 0, 1)!='_') {
					$this->out("  cake Icing.do {$this->Model->alias} {$methodName}");
				}
			}
			$this->out();
			$this->out("<info>all Model method are available too... eg:</info>");
			$this->out("  cake Icing.do {$this->Model->alias} delete '\$id'");
			$this->out("  cake Icing.do {$this->Model->alias} query 'UPDATE `a_table` SET `a_field` = \"abc\" WHERE `id` = \"\$id\";'");
		} else {
			$this->out("example:");
			$this->out("  cake Icing.do MyModel cleanup");
		}
		$this->out();
	}
}
