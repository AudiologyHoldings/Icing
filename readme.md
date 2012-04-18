# CakePHP Icing Plugin

Portable Package of Utilities for CakePHP

## DatabaseCacheEngine

Database Cache Engine useful for using the Cache::read/write but usable across multiple servers.

### DatabaseCacheEngine Install

Create database_caches table either using the MyISAM Engine (useful for ability to store large amounts of data)

	CREATE TABLE IF NOT EXISTS `database_caches` (
		`key` varchar(50) NOT NULL,
		`value` text NOT NULL,
		`duration` int(11) NOT NULL,
		UNIQUE KEY `key` (`key`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	
If you don't plan on storing anything more than 255 characters of json_encoded data, and you don't fear loosing all your caches if your database has to restart, you would benefit from using the MEMORY Engine instead.

	CREATE TABLE IF NOT EXISTS `database_caches` (
		`key` varchar(50) NOT NULL,
		`value` varchar(255) NOT NULL,
		`duration` int(11) NOT NULL,
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
