# CakePHP Icing Plugin

Portable Package of Utilities for CakePHP

## VersionableBehavior

Attach to any model to creating versions of current state on save for later restoration
Uses the AuthComponent to log the user doing the save by default.

### Install
Run the schema into your database to create icing_versions table

  cake schema create -p Icing
  
### Usage Examples
Bind to model you want to auto-version on save
Default Settings:

	array(
		'contain' => array(), //only version the current model
		'versions' => false, //unlimited versions
		'bind' => false, //don't bind versions on find
	)

	public $actsAs = array('Icing.Versionable'); //default settings

	public $actsAs = array('Icing.Versionable' => array(
		'contain' => array('Hour'), //contains for relative model to be saved.
		'versions' => '5', //how many version to save at any given time (false by default unlimited)
		'bind' => true, //attach Versionable as HasMany relationship for you onFind and if contained
	));

Restoring from a version

	$this->Model->restoreVersion(2); //restores the version of id 2.


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
