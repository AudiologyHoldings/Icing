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

# Models

* Throttle

# Datasources

* ArraySource

# Libraries

* DatabaseCacheEngine
* AppTestCase
* AppTestFixture
* Re

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
		'count' => false,
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
	Re::pluckValid($data, array('/ModelA/field', '/ModelB/field', '/lastChance'), 'defaultValue'); // gets first valid result for various paths or default value
	Re::pluck($data, array('/ModelA/field', '/ModelB/field', '/lastChance'), 'defaultValue'); // same as pluckValid() but without the valid check
	(bool) Re::pluckIsValid($data, array('/ModelA/field', '/ModelB/field', '/lastChance')); // same as pluckValid() and simply returns true/false
	Re::before($string, ',') == 'all of the string before the first comma';
	Re::after($string, '.') == 'all of the string after the last period';

## Base62

Transform any (large) int into Base62, useful for short URLs - see [Unit
Tests](https://github.com/AudiologyHoldings/Icing/blob/master/Test/Case/Lib/Base62Test.php)
for examples

	App::uses('Base62', 'Icing.Lib');
	Base62::encode(1234567890) == '1ly7vk';

