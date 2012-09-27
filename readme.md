# CakePHP Icing Plugin

Portable Package of Utilities for CakePHP

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
