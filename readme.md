# CakePHP Icing Plugin

	Portable Package of Utilities for CakePHP

## DatabaseCacheEngine

	Database Cache Engine useful for using the Cache::read/write but usable across multiple servers.
	
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
