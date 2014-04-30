Typeahead
----------------

This is an excellent pattern for HABTM assocations and hasMany as well

*Requirements*

The CakeDC/Search plugin must be setup on the model, allowing simple per-model configurable
searches.

https://github.com/CakeDC/search


---------------

I have included the JS file here as an example, and to simplify the
process of implementation, but you must include them into your application as
you see fit.

To get updates, go to the source:

http://jasny.github.io/bootstrap/javascript.html#typeahead

https://github.com/jasny/bootstrap/

https://github.com/jasny/bootstrap/blob/master/js/bootstrap-typeahead.js


Implementing the Model / Behavior
------------------

First, just include the behavior:

```
public $actsAs = array('Icing.Typeaheadable');
```

Now, wherever you run your `save()` calls, change them to a `saveAll()`

That's it, the Behavior will re-process the data automatically in before/after
save callbacks.


Implementing the Controller
------------------

First, just include the helper:

```
public $helpers = array('Icing.Typeahead');
```

Then make sure your edit pages are `contain`ing the assocatied data, so we can
pre-populate default values.

Finally implement the typeahead controller action.

(NOTE: this is a WIP, might want to move to a component)

```
	/**
	 * Admin typeahead for all controllers
	 * Also powers typeahead for HABTM selections
	 *
	 * @link http://twitter.github.io/bootstrap/javascript.html#typeahead
	 * @link http://jasny.github.io/bootstrap/javascript.html#typeahead
	 *   for the typeahead, it wants a simple object result, id as the key and name as the value
	 *
	 *	echo $this->TwitterBootstrap->input('assessment_id', array(
	 *		'label' => 'Assessment',
	 *		'type' => 'text',
	 *		'data-provide' => 'typeahead',
	 *		'data-source' => $this->Html->url(array('controller' => 'assessments', 'action' => 'typeahead', 'as.json')),
	 *	));
	 *
	 * @link https://github.com/loopj/jquery-typeahead
	 *   for the jquery-typeahead, it wants an array of objects, id and name designated
	 *
	 *	echo $this->TwitterBootstrap->input('Assessment.Assessment', array(
	 *		'label' => 'Assessments',
	 *		'type' => 'text',
	 *		'data-provide' => 'typeahead',
	 *		'data-source' => $this->Html->url(array('controller' => 'assessments', 'action' => 'typeahead', 'as.json')),
	 *	));
	 *
	 * @param string $findType list or all
	 * will recieve ?q=searchterm&limit=#
	 * @access public
	 */
	public function admin_autocomplete($findType = 'list') {
		$model = $this->uses[0];
		$model = explode('.', $model);
		$model = array_pop($model);
		$allowedFilters = array_merge( Hash::extract($this->presetVars, 'fields'), array('q') );
		$query = array_intersect_key($this->params['url'], array_flip($allowedFilters));
		$query = array_merge($query, $this->passedArgs);
		if (empty($query['term']) && !empty($query['q'])) {
			$query['term'] = $query['q'];
		}
		$this->Prg->commonProcess();
		$findType = ($findType == 'all' ? 'all' : 'list');
		$data = $this->{$model}->find($findType, array(
			'fields' => array($this->{$model}->primaryKey, $this->{$model}->displayField),
			'conditions' => $this->{$model}->parseCriteria($query),
			'limit' => 10,
		));
		if ($findType == 'all') {
			// If the Translate behavior is not attached
			if (!$this->{$model}->Behaviors->attached('Typeaheadable')) {
				$this->{$model}->Behaviors->attach('Icing.Typeaheadable');
			}
			$data = $this->{$model}->displayFieldToName($data, true);
		}
		$this->set(compact('data', 'model', 'findType'));
		$this->view = 'JsonTypeahead';
		$this->render(null, null);
		exit;
	}

	/**
	 * simplicity shortcut for typeahead autocomplete,
	 * powered by admin_autocomplete('list')
	 */
	public function admin_typeahead() {
		$this->setAction('admin_autocomplete', 'list');
	}

	/**
	 * simplicity shortcut for typeahead autocomplete,
	 * powered by admin_autocomplete('list')
	 */
	public function admin_typeahead() {
		$this->setAction('admin_autocomplete', 'all');
	}
```




Implementing the Helper
------------------

You can do this however you like, but here's how I've chosen to implement it:

```
echo $this->Typeahead->input('assessment_section_id', array(
	'label' => 'Assessment Section',
	'source' => $this->Html->url(array('controller' => 'assessment_sections', 'action' => 'typeahead', 'as.json')),
	'help_inline' => ' <span class="muted">Enter any name/value, will create a new record if needed</span>',
));
```

Implementing the JS
--------------

cd app/webroot
cp -r ../plugins/icing/webroot/typeahead ./

JS is from jansy bootstrap
---------------

http://jasny.github.io/bootstrap/javascript.html#typeahead

All credit ^


