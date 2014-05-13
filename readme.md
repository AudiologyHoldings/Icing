# CakePHP Icing Plugin

Portable Package of Utilities for CakePHP

# Helpers

* CsvHelper
* CkeditorHelper
* GoogleCalendarHelper
* TwitterHelper

# Behaviors

* FileUploadBehavior
* VersionableBehavior
* SummableBehavior
* ThrottleableBehavior
* EmailableBehavior

# Models

* Throttle

# Datasources

* ArraySource

# Libraries

* DatabaseCacheEngine
* AppTestCase
* AppTestFixture
* Re
* Pluck
* Base62
* PhpTidy
* ElasticSearchRequest

# Shells

* FixtureUpdateShell

## CsvHelper

Easily create and server CSV files.

	//some view.ctp with $data of a model
	foreach($data as $record){
		$row = array_values($record['Model']);
		$this->Csv->addRow($row);
	}
	echo $this->Csv->render('filename.csv');

## CkeditorHelper

Easily add Ckeditors to your forms.  Integrates with Ckfinder easily

	echo $this->Ckeditor->replace('ContentBody', array('ckfinder' => true, 'forcePasteAsPlainText' => 'true'));

## GoogleCalendarHelper

Build reminder links and quick add forms to intergrate with a logged in google calendar user.

	public $helpers = array('Icing.GoogleCalendar' => array('domain' => 'audiologyholdings.com'));

	$this->GoogleCalendar->reminder('small', array(
		'start' => 'Aug 15th, 2013 8:00pm',
		'end' => 'Aug 15th, 2013 9:00pm',
		'title' => 'Test Event',
		'details' => 'Details of Event',
		'location' => 'Albuquerque, NM',
		'add' => array('nurvzy@gmail.com', 'nick.baker@audiologyholdings.com')
	));


	$this->GoogleCalendar->quickForm('Add', array(
		'input' => array('label' => 'Quick Add'),
		'create' => array('id' => 'customID),
		'submit' => array('class' => 'someClass')
	));

## TwitterHelper

Build share, mention and hashtag buttons

	//Config/twitter.php
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

	<?php echo $this->Twitter->share(); ?>
	<?php echo $this->Twitter->share('Tweet This!', '/pledge', array('text' => 'Take the hearing health pledge!')); ?>
	<?php echo $this->Twitter->share('Tweet', array('action' => 'view'), array(
		'text' => 'Take the hearing health pledge!',
		'large' => true,
		'count' => 'none', //'horizontal' (default), 'vertical'
		'hashtags' => array('HashTag1','HashTagh2','HashTag3'),
		'related' => array('HearingAids','WebTechNick')
	)); ?>


# Behaviors

## FileUploadBehavior

Create *Config/file_upload.php* based on *app/Plugin/Icing/Config/file_upload.php.default*

Attach to any model to handle uploads.  Model attached needs name, type, and size fields (customizable)

	var $actsAs = array('Icing.FileUpload');

	var $actsAs = array(
		'Icing.FileUpload' => array(
			'uploadDir'    			=> WEB_ROOT . DS . 'files',
			'fields'       			=> array('name' => 'file_name', 'type' => 'file_type', 'size' => 'file_size'),
			'allowedTypes' 			=> array('pdf' => array('application/pdf')),
			'required'    			=> false,
			'unique' 						=> false //filenames will overwrite existing files of the same name. (default true)
			'fileNameFunction' 	=> 'sha1' //execute the Sha1 function on a filename before saving it (default false)
		)
	)

Use the built in helper to resize and cache on the fly

	echo $this->FileUpload->image($image['Upload']['name'], 300); //will resize to 300 px wide and cache to webroot/files/resized by default


## VersionableBehavior

Attach to any model to creating versions of current state on save for later restoration
Uses the AuthComponent to log the user doing the save by default.

### Install
Run the schema into your database to create icing_versions table

	cake schema create -p Icing

You should see icing_versions in your database

### Usage Examples
Bind to model you want to auto-version on save
Default Settings:

	array(
		'contain' => array(), //only version the current model
		'versions' => false, //unlimited versions
		'minor_timeframe' => false, //do not mark for minor versions
		'bind' => false, //don't bind versions on find
	)

	public $actsAs = array('Icing.Versionable'); //default settings

	public $actsAs = array('Icing.Versionable' => array(
		'contain'         => array('Hour'), //contains for relative model to be included in the version.
		'versions'        => '5',           //how many version to save at any given time (false by default unlimited)
		'minor_timeframe' => '10',          //Mark all previous versions if saved within 10 seconds of current version.  Easily cleanup minor_versions
		'bind'            => true,          //attach IcingVersion as HasMany relationship for you on find and if contained
	));

Restoring from a version

	$this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a'); //restores version id 50537471-ba08-44ae-a606-24e5e017215a
 	$this->Model->restoreVersion('50537471-ba08-44ae-a606-24e5e017215a', false); //restores version id 50537471-ba08-44ae-a606-24e5e017215a and won't create a new version before restoring.
 	$this->Model->restoreVersion(2, 3); //restores the second version back from most recent on Model id 3
 	$this->Model->restoreVersion(2, 3, false); //restores the second version back from most recent on Model id 3 and doesn't create a new version before saving

Diffs from a version

	$result = $this->Model->diffVersion('50537471-ba08-44ae-a606-24e5e017215a'); //Gets the diff between version id and the curent state of the record.
	$result = $this->Model->diffVersion('50537471-ba08-44ae-a606-24e5e017215a', '501234121-ba08-44ae-a606-2asdf767a'); //Gets the diff between two different versions.

Save without creating a version

	$this->Model->save($data, array('create_version' => false));

# DataSources

## ArraySource

Allows for an array dataset instead of sql database but can be assosiated with other model data with normal cakephp assosiations and finds.

### Example

	//Config/database.php
	var $array = array(
		'datasource' => 'Icing.ArraySource'
	);

	//Model/ConsumerGuide.php
	App::uses('AppModel','Model');
	class ConsumerGuide extends AppModel {
		public $name = 'ConsumerGuide';
		public $useDbConfig = 'array';
		public $displayField = 'name';
		public $primaryKey = 'type';

		public $records = array(
			array(
				'type' => 'loved_one',
				'text' => "Do you have a loved one with hearing loss and don't know where to turn? Download our free guide, which will give you the information you need to help your family member or friend with hearing loss.",
				'path' => 'Free_Guide_-_Hearing_and_Your_Loved_Ones.pdf',
				'name' => 'Free Guide - Hearing and Your Loved Ones',
				'thumb' => 'hearing_and_your_loved_ones.png',
			),
		);
	}

	//Example Uses
	$this->ConsumerGuide->find('first');
	$this->ConsumerGuide->find('all', array(
		'conditions' => array(
			'ConsumerGuide.type' => 'loved_one',
		),
		'fields' => array('ConsumerGuide.text','ConsumerGuide.path'),
		'order' => array('ConsumerGuide.name ASC'),
		'limit' => 2,
	));
	$this->ConsumerGuide->field('path', array('ConsumerGuide.type' => 'loved_one'));
	$this->ConsumerGuide->findByType('loved_one');

## ThrottleableBehavior

This is a convenience shortcut to functionality on the Throttle model.
Basically it's a very clean and simple way to Throttle anything.

	// setup in the model Behaviors all the time
	public $actsAs = array('Icing.Throttleable');

	// or load/attach the Behavior
	$this->MyModel->Behaviors->load('Icing.Throttleable');

	// the default `throttle()` method prefixes the $key with the Model->alias
	if (!$this->MyModel->throttle('someKey', 2, 3600)) {
		throw new OutOfBoundsException('This method  on MyModel has been attempted more than 2 times in 1 hour... wait.');
	}
	// the `_throttle()` method does not modify $key at all, so it's the same regardless of how you access it
	if (!$this->MyModel->_throttle('key-could-be-anywhere', 2, 3600)) {
		throw new OutOfBoundsException('This key has been attempted (from somewhere) more than 2 times in 1 hour... wait.');
	}

# Models

## Throttle

Simple throttling table/toolset

Common Usage:

	App::uses('Throttle', 'Icing.Model');
	if (!ClassRegistry::init('Icing.Throttle')->checkThenRecord('myUniqueKey', 2, 3600)) {
		throw new OutOfBoundsException('This method has been attempted more than 2 times in 1 hour... wait.');
	}
	if (!ClassRegistry::init('Icing.Throttle')->checkThenRecord('myUniqueKey'.AuthComponent::user('id'), 1, 60)) {
		throw new OutOfBoundsException('A Logged In User Account has attempted more than 1 time in 60 seconds... wait.');
	}
	if (!ClassRegistry::init('Icing.Throttle')->checkThenRecord('myUniqueKey'.env('REMOTE_ADDR'), 5, 86400)) {
		throw new OutOfBoundsException('Your IP address has attempted more than 5 times in 1 day... wait.');
	}
	// you can use `limit()` or `checkThenRecord()` -- they are identical methods
	if (!ClassRegistry::init('Icing.Throttle')->limit('myUniqueKeyAsLimitAlias', 2, 3600)) {
		throw new OutOfBoundsException('This method has been attempted more than 2 times in 1 hour... wait.');
	}

Also see ThrottleableBehavior:

	if (!$this->MyModel->throttle('someKey', 2, 3600)) {
		throw new OutOfBoundsException('This method  on MyModel has been attempted more than 2 times in 1 hour... wait.');
	}

Main Methods:

* checkThenRecord() - shortcut to check, and then, record for a $key
* limit() - alias to checkThenRecord()
* check() - checks to see that there are no more than $allowed records for a $key
* record() - saves a record for a $key (which will $expireInSec)
* purge() - empties all expired records from table (automatically called on check())

[Unit Tests](https://github.com/AudiologyHoldings/Icing/blob/master/Test/Case/Model/ThrottleTest.php):

	./cake test Icing Model/Throttle

# Libraries

## DatabaseCacheEngine

Database Cache Engine useful for using the Cache::read/write but usable across multiple servers.

### DatabaseCacheEngine Install

Create database_caches table either using the MyISAM Engine (useful for ability to store large amounts of data)

	CREATE TABLE IF NOT EXISTS `database_caches` (
		`key` varchar(50) NOT NULL,
		`value` text NOT NULL,
		`duration` int(11) unsigned NOT NULL,
		UNIQUE KEY `key` (`key`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;

If you don't plan on storing anything more than 255 characters of json_encoded data, and you don't fear loosing all your caches if your database has to restart, you would benefit from using the MEMORY Engine instead.

	CREATE TABLE IF NOT EXISTS `database_caches` (
		`key` varchar(50) NOT NULL,
		`value` varchar(255) NOT NULL,
		`duration` int(11) unsigned NOT NULL,
		UNIQUE KEY `key` (`key`)
	) ENGINE=MEMORY DEFAULT CHARSET=utf8;

###  DatabaseCacheEngine Setup

`app/Config/bootstrap.php`

	CakePlugin::load('Icing');
	Cache::config('database', array(
		'engine' => 'Icing.DatabaseCache',
		'duration' => '+1 day',
	));

### DatabaseCacheEngine Usage

	Cache::write('somekey', 'somevalue', 'database');
	Cache::read('somekey', 'database');

## AppTestCase

Easy way to load fixture automatically and in groups.  Look at the file for more usage examples

	App::uses('AppTestCase', 'Icing.Lib');
	Configure::load('app_test_fixtures');
	class WhateverTest extends AppTestCase {
		...
	}


* `assertArrayCompare` - comapre all keys/values in array 1 with all matching keys and their values in array 2.
* `assertInArray` - shortcut to assertTrue(in_array())
* `assertIsEmpty` - shortcut to assertTrue(empty())
* `assertIsNotEmpty` - shortcut to assertTrue(!empty())
* `assertKeyExists` - shortcut to assertTrue(array_key_exists())
* `assertTimestamp` - special test for timestamp within tolerance of expected timestamp [now]
* `assertValidationErrors` - special test for validation errors
* `loadFixtureGroup` - loads a Config'ed set of standard grouped fixtures

## AppTestFixture

Fixes missing fields of records with bogus data so you don't have to worry about it. Look at the file for more usage examples

	App::uses('AppTestFixture', 'Icing.Lib');
	class UserFixture extends AppTestFixture {
		...
	}

## Re

This is a very simple utility library that comes in quite handy.  View
[source](https://github.com/AudiologyHoldings/Icing/blob/master/Lib/Re.php)
and [Unit
Tests](https://github.com/AudiologyHoldings/Icing/blob/master/Test/Case/Lib/ReTest.php) for more details.

	App::uses('Re', 'Icing.Lib');
	Re::arrayCSV('a,b,c') ~ Re::stringCSV(array('a', 'b', 'c'));
	Re::isValid($data); // basically !empty() but allows 0 (by default)
	Re::before($string, ',') == 'all of the string before the first comma';
	Re::after($string, '.') == 'all of the string after the last period';

**Re::pluck() DEPRECATED**

The `Re::pluck()` Methods were based on Set::extract() and that has been
deprecated in the CakePHP core. While I initially really liked the XPath
syntax, there were several case where it caused more problems than it solved

Switch to `Pluck::one()` or one of the `Pluck` methods

	[DEPRECATED] Re::pluckValid($data, array('/ModelA/field', '/ModelB/field', '/lastChance'), 'defaultValue'); // gets first valid result for various paths or default value
	[DEPRECATED] Re::pluck($data, array('/ModelA/field', '/ModelB/field', '/lastChance'), 'defaultValue'); // same as pluckValid() but without the valid check
	[DEPRECATED](bool) Re::pluckIsValid($data, array('/ModelA/field', '/ModelB/field', '/lastChance')); // same as pluckValid() and simply returns true/false

## Pluck

A simple wrapper for the `Hash::extract()` method, which encorporates the
`Hash::filter()` method a bit too.

All of these methods require an array as the first argument

All of these methods accept multiple paths or as single path as the second argument (order matters)

All of these methods accept a `filterCallback` as the last argument
(`$default` is third for `one()` and `firstPathOrDefault()`)

* `false`  will not run Hash::filter() (**important!**, use this if you need empty/boolean results)
* `null`  will run Hash::filter($data) to remove all empties
* otherwise  will run Hash::filter($data, $filterCallback)

<pre>
	Pluck::all() --> array()
		the only real benifit to this is, you can aggregate results from multiple paths
	Pluck::all($user, 'User.id') == array(123)
	Pluck::all($user, array('Bad.path', 'User.id', 'User.name')) == array(123, 'john doe')

	Pluck::firstPath() --> array()
		the first result which matches any path (in order) returns
		note: we do filter data first, so unless you disable filtering, it's the first non-empty result.
	Pluck::firstPath($user, 'User.id') == array(123)
	Pluck::firstPath($user, array('Bad.path', 'User.id', 'User.name')) == array(123)

	Pluck::firstPathOrDefault() --> array() or $default
		the output of Pluck::firstPath()
		if empty, we instead return a $default argument
	Pluck::firstPathOrDefault($user, 'Bad.path', 'default text') == 'default text'
	Pluck::firstPathOrDefault($user, 'Bad.path', array('default', 'array')) == array('default', 'array')

	Pluck::one() --> value {string or whatever} or $default
		the output of Pluck::firstPath()
		but we only return the "current" or first value...
		also, if empty, we instead return a $default argument
	Pluck::one($user, 'User.id') == 123
	Pluck::one($user, array('Bad.path', 'User.id', 'User.name')) == 123
	Pluck::one($user, 'Bad.path', 'non-user') == 'non-user'
		data = string/int/etc = passthrough
	Pluck::one('as_string@example.com', 'User.email', 'no email') == 'as_string@example.com'
	Pluck::one(0, 'User.email', 'no email') == '0'
		data = null or false or empty array or empty string = default
	Pluck::one(array(), 'User.email', 'no email') == 'no email'
	Pluck::one(null, 'User.email', 'no email') == 'no email'
	Pluck::one(false, 'User.email', 'no email') == 'no email'
	Pluck::one('', 'User.email', 'no email') == 'no email'

	Pluck::oneEmpties()
		the same as Pluck::one() but $filterCallback=false, allowing empties

	Pluck::allEmpty()
		the same as (!empty(Pluck::all()))
</pre>

If you find yourself doing: `current(Hash::extract($data, 'User.id'))` then
checkout `Pluck::one($data, 'User.id')`

Likewise, use that with multiple paths and return the first valid value we find
at any of those paths.

Here's a pretty decent use case for this Lib:

	$user_id = Pluck::one($userOrId, array('User.id', 'Account.user_id', 'user_id', 'id'), 'guest');

This will return `$userOrId` if it is a valid ID (a non-array, not empty)

Or if `$serOrId` is an array, it will return the first valid result from any of
the paths offerd (left = first/priority).

If no valid paths found, returns the default which is set to 'guest' (if not
specific it is `null`).

## Base62

Transform any (large) int into Base62, useful for short URLs - see [Unit
Tests](https://github.com/AudiologyHoldings/Icing/blob/master/Test/Case/Lib/Base62Test.php)
for examples

	App::uses('Base62', 'Icing.Lib');
	Base62::encode(1234567890) == '1ly7vk';

## PhpTidy


This Lib will allow you to easily "tidy" or "beautify" files or inline code, to
CakePHP standards/conventions.  The "engine" for it is currently PhpTidy
(though we may switch to code sniffer or something).

*Convenience wrapper for phptidy.php script in app/Plugin/Icing/Vendor/phptidy.php*

Usage:

    App::uses('PhpTidy', 'Icing.Lib');
    $formatted = PhpTidy::string($unformattedPhpCode);
    // or //
    PhpTidy::string(APP . 'path/to/php-file.php');

## ElasticSearchRequest

This is an extension of the HttpSocket utility, customized and organized to
help interact with ElasticSearch.


Setup:

Copy the default `ElasticSearchIndex` configuration into your app and edit it
to suit your setup.

```
cp app/Plugin/Icing/Config/elastic_search_request.php.default app/Config/elastic_search_request.php
```

Note that there's a `default` config and a `test` config which will override
the `default` config...  But only if your tests set the following Configure variable:

```
Configure::write('inUnitTest', true);
```

Usage:

    App::uses('ElasticSearchRequest', 'Icing.Lib');
    $this->ESR = new ElasticSearchRequest(array('index' => 'myindex', 'table' => 'mytable'));
    $records = $this->ESR->search('query string');
    $rawResponse = $this->ESR->search('query string', array(), true);
    // -------------
    $bool = $this->ESR->createIndex('mynewindex');
    $mapping = array(
      "test_table" => array(
        "properties" => array(
          "model" => array(
            "type" => "string",
            "store" => "yes",
            ),
          "association_key" => array(
            "type" => "string",
            "store" => "yes",
          ),
          "data" => array(
            "type" => "string",
            "store" => "yes",
          )
        )
      )
    );
    $bool = $this->ESR->createMapping($mapping);
    $data = array(
      "model" => "MyExample",
      "association_key" => "12345",
      "data" => "here is some raw text data, great to search against",
    );
    $elastic_search_id = $this->ESR->createRecord($data);
    $data = $this->ESR->getRecord($elastic_search_id);
    $elastic_search_id = $this->ESR->updateRecord($elastic_search_id, $data);
    $bool = $this->ESR->deleteRecord($elastic_search_id);
    $bool = $this->ESR->deleteIndex('mynewindex');
    $mapping = $this->ESR->getMapping();

## FixtureUpdateShell

Attempts to intelligently update your fixtures to

 * force it to use Icing.AppTestFixture
 * update the $fields to always match the current database schema (doesn't touch records, or any other config)
 * run Icing.PhpTidy against the fixutre, to correct formatting

Bonus: it will also verify all fixtures can be found in the database table

Usage:

    ./cake Icing.FixtureUpdate
    ./cake Icing.FixtureUpdate help
    ./cake Icing.FixtureUpdate --plugin MyPlugin --connection my_connection
