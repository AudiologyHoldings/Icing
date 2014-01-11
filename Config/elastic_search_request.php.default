<?php
/**
 * Icing.ElasticSearchRequest configuration
 *
 * the 'default' will always be used
 *
 * the 'test' will be merged on top if we are running as a unit test (useful)
 */

$config = array(
	'ElasticSearchRequest' => array(
		'default' => array(
			// the name of the index you want to use
			'index' => 'myindex',
			// the details of the HttpSocket request we will make
			//   http://book.cakephp.org/2.0/en/core-utility-libraries/httpsocket.html
			'uri' => array(
				'scheme' => 'http',
				'host' => 'localhost',
				'port' => 9200,
				),
			'auth' => array(
				'method' => 'Basic',
				'user' => null,
				'pass' => null
				),
			'version' => '1.1',
			'header' => array(
				'Connection' => 'close',
				'User-Agent' => 'CakePHP'
				),
			'redirect' => false,
			'cookies' => array()
		),
		// test values only apply when in unit tests
		'test' => array(
			'index' => 'myindex_test',
		),
	),
);
