TokenInput
----------------

This is an excellent pattern for HABTM assocations and hasMany as well

*Requirements*

The CakeDC/Search plugin must be setup on the model, allowing simple per-model configurable
searches.

https://github.com/CakeDC/search


---------------

I have included the JS and CSS files here as examples, and to simplify the
process of implementation, but you must include them into your application as
you see fit.

(the CSS file reuires the them of "facebook")

To get updates, go to the source:

https://github.com/loopj/jquery-tokeninput
http://loopj.com/jquery-tokeninput/


Implementing the Model / Behavior
------------------

First, just include the behavior:

```
public $actsAs = array('Icing.Tokeninputable');
```

Now, wherever you run your `save()` calls, change them to a `saveAll()`

That's it, the Behavior will re-process the data automatically in before/after
save callbacks.


Implementing the Controller
------------------

First, just include the helper:

```
public $helpers = array('Icing.Tokeninput');
```

Then make sure your edit pages are `contain`ing the assocatied data, so we can
pre-populate default values.

Finally implement the typeahead controller action.

(NOTE: this is a WIP, might want to move to a component)

```
	/**
	 * Admin typeahead for all controllers
	 * Also powers tokeninput for HABTM selections
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
	 * @link https://github.com/loopj/jquery-tokeninput
	 *   for the jquery-tokeninput, it wants an array of objects, id and name designated
	 *
	 *	echo $this->TwitterBootstrap->input('Assessment.Assessment', array(
	 *		'label' => 'Assessments',
	 *		'type' => 'text',
	 *		'data-provide' => 'tokeninput',
	 *		'data-source' => $this->Html->url(array('controller' => 'assessments', 'action' => 'tokeninput', 'as.json')),
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
	 * simplicity shortcut for tokeninput autocomplete,
	 * powered by admin_autocomplete('list')
	 */
	public function admin_tokeninput() {
		$this->setAction('admin_autocomplete', 'all');
	}
```




Implementing the Helper
------------------

You can do this however you like, but here's how I've chosen to implement it:

```
// HABTM: Assessment HABTM AssessmentContent with AssessmentsAssessmentContent
echo $this->Tokeninput->input('AssessmentContent.AssessmentContent', array(
	'label' => 'AssessmentContent',
	'source' => $this->Html->url(array('controller' => 'assessment_contents', 'action' => 'tokeninput', 'as.json')),
	'prePopulate' => Hash::combine($this->data, '{n}.AssessmentContent.id', '{n}.AssessmentContent.description'),
));
```

And for bonus points, you can add a block after the input, with a button to add
a new record via a modal window... you'll have to handle the modal->save JS on
your own at this point (because my version isn't nicely packaged) but it allows
for a very slick interface to add a new HABTM record and subsequently join it.

```
// HABTM: Assessment HABTM AssessmentContent with AssessmentsAssessmentContent
echo $this->Tokeninput->input('AssessmentContent.AssessmentContent', array(
	'label' => 'AssessmentContent',
	'source' => $this->Html->url(array('controller' => 'assessment_contents', 'action' => 'tokeninput', 'as.json')),
	'prePopulate' => Hash::combine($this->data, '{n}.AssessmentContent.id', '{n}.AssessmentContent.description'),
	'help_block' =>
	$this->Modal->link('<i class="icon-plus icon-white"></i> Add Assessment Content',
		array('controller' => 'assessment_contents', 'action' => 'add'),
		array('escape' => false, 'class' => 'btn btn-info btn-mini')
	) . ' <span class="muted">(after it\'s added, you will have to search for it above)</span>',
));
```

Implementing the JS & CSS
--------------

cd app/webroot
cp -r ../plugins/icing/webroot/tokeninput ./

JS is from loopj
---------------

http://loopj.com/jquery-tokeninput/

All credit ^


JS Config from the Source
-----------------------

Configuration

The tokeninput takes an optional second parameter on intitialization which allows you to customize the appearance and behaviour of the script, as well as add your own callbacks to intercept certain events. The following options are available:

Search Settings

method
The HTTP method (eg. GET, POST) to use for the server request. default: “GET”.
queryParam
The name of the query param which you expect to contain the search term on the server-side. default: “q”.
searchDelay
The delay, in milliseconds, between the user finishing typing and the search being performed. default: 300 (demo).
minChars
The minimum number of characters the user must enter before a search is performed. default: 1 (demo).
propertyToSearch
The javascript/json object attribute to search. default: “name” (demo).
jsonContainer
The name of the json object in the response which contains the search results. This is typically used when your endpoint returns other data in addition to your search results. Use null to use the top level response object. default: null.
crossDomain
Force JSONP cross-domain communication to the server instead of a normal ajax request. Note: JSONP is automatically enabled if we detect the search request is a cross-domain request. default: false.
Pre-population Settings

prePopulate
Prepopulate the tokeninput with existing data. Set to an array of JSON objects, eg: [{id: 3, name: "test"}, {id: 5, name: "awesome"}] to pre-fill the input. default: null (demo).
Display Settings

hintText
The text to show in the dropdown label which appears when you first click in the search field. default: “Type in a search term” (demo).
noResultsText
The text to show in the dropdown label when no results are found which match the current query. default: “No results” (demo).
searchingText
The text to show in the dropdown label when a search is currently in progress. default: “Searching…” (demo).
deleteText
The text to show on each token which deletes the token when clicked. If you wish to hide the delete link, provide an empty string here. Alternatively you can provide a html string here if you would like to show an image for deleting tokens. default: × (demo).
animateDropdown
Set this to false to disable animation of the dropdown default: true (demo).
theme
Set this to a string, eg “facebook” when including theme css files to set the css class suffix (demo).
resultsLimit
The maximum number of results shown in the drop down. Use null to show all the matching results. default: null
resultsFormatter
A function that returns an interpolated HTML string for each result. Use this function with a templating system of your choice, such as jresig microtemplates or mustache.js. Use this when you want to include images or multiline formatted results default: function(item){ return “<li>” + item.propertyToSearch + “</li>” } (demo).
tokenFormatter
A function that returns an interpolated HTML string for each token. Use this function with a templating system of your choice, such as jresig microtemplates or mustache.js. Use this when you want to include images or multiline formatted tokens. Quora’s people invite token field that returns avatar tokens is a good example of what can be done this option. default: function(item){ return “<li><p>” + item.propertyToSearch + “</p></li>” } (demo).
Tokenization Settings

tokenLimit
The maximum number of results allowed to be selected by the user. Use null to allow unlimited selections. default: null (demo).
tokenDelimiter
The separator to use when sending the results back to the server. default: “,”.
preventDuplicates
Prevent user from selecting duplicate values by setting this to true. default: false (demo).
tokenValue
The value of the token input when the input is submitted. Set it to id in order to get a concatenation of token IDs, or to name in order to get a concatenation of names. default: id
Callbacks

onResult
A function to call whenever we receive results back from the server. You can use this function to pre-process results from the server before they are displayed to the user. default: null (demo).
onAdd
A function to call whenever the user adds another token to their selections. defaut: null (demo).
onDelete
A function to call whenever the user removes a token from their selections. default: null (demo).
onReady
A function to call after initialization is done and the tokeninput is ready to use. default: null
Methods

selector.tokenInput("add", {id: x, name: y});
Add a new token to the tokeninput with id x and name y.
selector.tokenInput("remove", {id: x});
Remove the tokens with id x from the tokeninput.
selector.tokenInput("remove", {name: y});
Remove the tokens with name y from the tokeninput.
selector.tokenInput("clear");
Clear all tokens from the tokeninput.
selector.tokenInput("get");
Gets the array of selected tokens from the tokeninput (each item being an object of the kind {id: x, name: y}).
