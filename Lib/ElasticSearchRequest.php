<?php
/**
 * Tool to simplify making requests
 *
 */
class ElasticSearchRequestException extends CakeBaseException {}
App::uses('HttpSocket', 'Network/Http');
class ElasticSearchRequest extends HttpSocket {

	/**
	 * Placeholder for config... you should configure this with
	 *   app/Config/elastic_search_request.php
	 *     default copy here
	 *   cp app/Plugin/Icing/Config/elastic_search_request.php.default app/Config/elastic_search_request.php
	 *     or
	 *   Configure::write(array('ElasticSearchRequest' => array( ... )));
	 *
	 */
	public $_config = array();

	/**
	 * construct the object
	 */
	public function __construct($config = array()) {
		$this->config($config);
		$this->_config['log'] = true;
	}

	/**
	 * Quick and simple _search functionality
	 *   including basic query parsing/handling
	 *
	 * @param mixed $query
	 * @param array $request
	 * @param boolean $returnRaw
	 */
	public function search($query = '', $request = array(), $returnRaw = false) {
		$request = $this->buildRequest($request);
		$request['method'] = 'GET';
		$request['uri']['path'] .= '/_search';
		// setup the query
		$data = $this->parseQuery($query);
		// handle extra details
		if (!empty($request['limit'])) {
			$data['size'] = intval($request['limit']);
		}
		if (!empty($request['size'])) {
			$data['size'] = intval($request['size']);
		}
		if (!empty($request['page']) && !empty($request['size'])) {
			$data['from'] = (intval($request['page']) - 1) * intval($request['size']);
		}
		if (!empty($request['from'])) {
			$data['from'] = intval($request['from']);
		}
		if (!empty($request['fields'])) {
			$data['fields'] = (is_array($request['fields']) ? array_values($request['fields']) : explode(',', $request['fields']));
		}
		// set the $data
		$request['body'] = $this->asJson($data);
		$data = $this->request($request);
		if ($returnRaw) {
			return $data;
		}
		if (empty($data['hits']['hits'])) {
			return array();
		}
		$output = array();
		foreach ($data['hits']['hits'] as $i => $hit) {
			$output[$i] = array(
				'_id' => $hit['_id'],
			);
			if (!empty($hit['_source'])) {
				$output[$i] += $hit['_source'];
			}
			if (!empty($hit['fields'])) {
				$output[$i] += $hit['fields'];
			}
		}
		return $output;
	}

	public function createIndex($index, $request = array()) {
		$request['uri']['path'] = "/{$index}";
		$request = $this->buildRequest($request);
		$request['method'] = 'PUT';
		$request['body'] = '';
		$data = $this->request($request);
		return (!empty($data['ok']));
	}

	public function deleteIndex($index, $request = array()) {
		$request['uri']['path'] = "/{$index}";
		$request = $this->buildRequest($request);
		$request['method'] = 'DELETE';
		$request['body'] = '';
		$data = $this->request($request);
		return (!empty($data['ok']));
	}

	public function createMapping($mapping, $request = array()) {
		$request = $this->buildRequest($request);
		$table = $this->verifyTableOnPath($request);
		if (!array_key_exists($table, $mapping)) {
			$mapping = array($table => $mapping);
		}
		if (!array_key_exists('properties', $mapping[$table])) {
			$mapping[$table] = array('properties' => $mapping[$table]);
		}
		$request['method'] = 'PUT';
		$request['uri']['path'] .= '/_mapping';
		$request['body'] = $this->asJson($mapping);
		$data = $this->request($request);
		return (!empty($data['ok']));
	}

	public function getMapping($request = array()) {
		$request = $this->buildRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'GET';
		$request['uri']['path'] .= '/_mapping';
		$request['body'] = '';
		$data = $this->request($request);
		return $data;
	}

	public function createRecord($data, $request = array()) {
		$request = $this->buildRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'POST';
		$request['uri']['path'] .= '/'; // automatic ID creation
		$request['body'] = $this->asJson($data);
		$data = $this->request($request);
		if (!empty($data['_id'])) {
			return $data['_id'];
		}
		return false;
	}

	public function updateRecord($id, $data, $request = array()) {
		$request = $this->buildRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'POST';
		$request['uri']['path'] .= "/{$id}"; // explicit id = overwrite
		$request['body'] = $this->asJson($data);
		$data = $this->request($request);
		if (!empty($data['_id'])) {
			return $data['_id'];
		}
		return false;
	}

	public function deleteRecord($id, $request = array()) {
		$request = $this->buildRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'DELETE';
		$request['uri']['path'] .= "/{$id}"; // explicit id = overwrite
		$request['body'] = '';
		$data = $this->request($request);
		return (!empty($data['ok']));
	}

	public function getRecord($id, $request = array()) {
		$request = $this->buildRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'GET';
		$request['uri']['path'] .= "/{$id}";
		$request['body'] = '';
		$raw = $this->request($request);
		$data = $raw['_source'];
		$data['_id'] = $raw['_id'];
		return $data;
	}

	public function exists($id, $request = array()) {
		$request = $this->buildRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'HEAD';
		$request['uri']['path'] .= "/{$id}";
		$request['body'] = '';
		try {
			$raw = $this->request($request);
			return true;
		} catch (ElasticSearchRequestException $e) {
			if (strpos($e->getMessage(), '404') !== false) {
				return false;
			}
			throw new ElasticSearchRequestException($e->getMessage());
		}
		return false;
	}

	/**
	 *
	 * TODO: this needs to be extened a bunch
	 *
	 * @param mixed $query or $query_string
	 * @return array $query as nested query array
	 */
	public function parseQuery($query = '') {
		if (empty($query)) {
			return array();
		}
		if (is_string($query)) {
			// is it a JSON array?
			$_query = @json_decode($query, true);
			if (!empty($_query) && is_array($_query)) {
				$query = $_query;
			}
		}
		if (is_string($query)) {
			$query = $this->autoQuery($query);
		}
		// it's an array, validate that it's wrapped in "query"
		if (!array_key_exists('query', $query)) {
			$query = array('query' => $query);
		}
		if (is_string($query['query'])) {
			$query['query'] = array('query_string' => array('query' => $query['query']));
		}
		return $query;
	}

	/**
	 * Automate a query: string --> nested query
	 *   supports:
	 *     '~fuzzy' - supports fuzzy_like_this creation http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
	 *     'query' - defaults to query, query_string, query
	 *       'term'
	 *       'partial*'
	 *       '"phrase"'
	 *       'field:value AND term'
	 *
	 * @param string $query_string
	 * @return array $query as array
	 */
	public function autoQuery($query_string) {
		if (substr($query_string, 0, 1) == '~') {
			$query_string = substr($query_string, 1);
			// treat as a fuzzy term match
			return array('query' => array('fuzzy_like_this' => array('like_text' => $query_string)));
		}
		// treat is as a simple query string
		return array(
			'query' => array(
				'query_string' => array(
					'query' => $query_string,
					'lenient' => true,
				)
			)
		);
	}

	/**
	 * Swiss army knife
	 *
	 * @param array $options
	 *   - method [GET, POST, PUT, DELETE]
	 *   - index (optional override)
	 *   - table (optional - appends to index to create path)
	 *   - request (optional override to the request details)
	 *     - uri
	 *       - path (we auto-set this to: /$index/$table/$action)
	 *       - query (eg: ?pretty-true)
	 *     - heeader
	 */
	public function request($request = array()) {
		// do the request
		$this->log(compact('request'));
		try {
			$response = parent::request($request);
		} catch (SocketException $e) {
			$this->log(array('SocketException' => $e->getMessage()));
			return false;
		}
		$this->log(compact('response'));
		try {
			return $this->handleResponse($response, $request);
		} catch(ElasticSearchRequestException $e) {
			$error = $e->getMessage();
			if (strpos($error, '[retry]') !== false) {
				return $this->request($request);
			}
			$this->log(compact('error'));
			throw new ElasticSearchRequestException($error);
		}
		// how did we get here?
		throw new ElasticSearchRequestException("Request failed, unknown");
	}

	/**
	 *
	 *
	 * @param object $response
	 * @param array $request
	 * @return array $response friendly
	 * @throws ElasticSearchRequestException
	 */
	public function handleResponse($response, $request = null) {
		// validate the request
		$data = @json_decode($response->body, true);
		if (!empty($data['error']) || !in_array($response->code, array(200, 201))) {
			$error = (empty($data['error']) ? 'unknown error' : $data['error']);
			if (preg_match('#IndexMissingException\[\[(.+)\] missing\]#', $error, $match)) {
				$index = $match[1];
				if ($this->createIndex($index, $request) && empty($this->retrying)) {
					sleep(1);
					$this->retrying = true; // retry -- watch out for a loop
					$this->log('retrying');
					return $this->request($request);
				}
			}
			if (strpos($error, 'IndexAlreadyExistsException') !== false) {
				return array('ok' => true, 'message' => 'IndexAlreadyExistsException');
			}
			$error = str_replace(array('[', ']'), ' ', $error);
			throw new ElasticSearchRequestException("Request failed, got a response code of {$response->code} {$error}");
		}
		if (empty($data) && $request['method'] != 'HEAD') {
			throw new ElasticSearchRequestException("Request failed, response empty: {$response->body}");
		}
		return $data;
	}

	/**
	 * Get and set config
	 */
	public function config($config = array()) {
		if (empty($this->_config)) {
			try {
				Configure::load('elastic_search_request');
			} catch (ConfigureException $e) {
				// no config file?...  validation later will confirm functionality
			}
			$defaultConfig = Configure::read('ElasticSearchRequest');
			if (!empty($defaultConfig['default'])) {
				$config = Hash::merge($config, $defaultConfig['default']);
			}
			$isUnitTest = Configure::read('inUnitTest');
			if (!empty($isUnitTest) && !empty($defaultConfig['test'])) {
				$config = Hash::merge($config, $defaultConfig['test']);
			}
		}
		if (!empty($config)) {
			$config = Hash::merge($this->_config, $config);
			$this->verifyConfig($config);
			$config['verified'] = true;
		}
		$this->_config = $config;
		if (empty($this->_config)) {
			$this->verifyConfig($this->_config);
		}
		return $this->_config;
	}

	/**
	 * verify config
	 *
	 * @param array $config
	 * @return boolean
	 * @throws ElasticSearchRequestException
	 */
	public function verifyConfig($config) {
		$extra = 'Check you config.';
		if (Configure::read('debug') > 0) {
			$extra = 'Maybe you should: `cp app/Plugin/Icing/Config/elastic_search_request.php.default app/Config/elastic_search_request.php`';
		}
		if (empty($config)) {
			throw new ElasticSearchRequestException("Invalid config: it is empty.  {$extra}");
		}
		if (empty($config['index'])) {
			throw new ElasticSearchRequestException("Invalid config: the 'index' is empty.  {$extra}");
		}
		if (!preg_match('#^[a-zA-Z0-9_-]+$#', $config['index'])) {
			throw new ElasticSearchRequestException("Invalid config: the 'index' is invalid, it should be basic alphanumeric.  {$extra}");
		}
		if (empty($config['uri']['scheme'])) {
			throw new ElasticSearchRequestException("Invalid config: the 'uri.scheme' is empty, it should be http or https. {$extra}");
		}
		if (empty($config['uri']['host'])) {
			throw new ElasticSearchRequestException("Invalid config: the 'uri.host' is empty, it should be the hostname to reach your ElasticSearch server.  {$extra}");
		}
		if (empty($config['uri']['port'])) {
			throw new ElasticSearchRequestException("Invalid config: the 'uri.port' is empty, it should be 80 or 9200 or whatever the port is on your ElasticSearch server.  {$extra}");
		}
		return true;
	}

	/**
	 * Log data via the standard logging engine
	 *
	 * @param mixed $data
	 * @return boolean
	 */
	public function log($data) {
		if (empty($this->_config['log'])) {
			return true;
		}
		if (is_array($data) && !empty($data['request'])) {
			// make a friendly curl command to emmlate
			CakeLog::write('ElasticSearchRequest', $this->asCurlRequest($data['request']));
		}
		CakeLog::write('ElasticSearchRequest', $this->asJson($data));
		return true;
	}

	/**
	 * Build a request from the _config data and all details passed in
	 * also calculates the path if empty
	 *
	 * @param array $request
	 * @return array $request
	 */
	public function buildRequest($request) {
		$allowedKeysFromConfig = array('method', 'uri', 'auth', 'version', 'body', 'line', 'header', 'raw', 'redirect', 'cookies');
		$config = array_intersect_key($this->_config, array_flip($allowedKeysFromConfig));
		$request = Hash::merge($config, $request);
		if (empty($request['uri']['path'])) {
			$path = '/' . $this->_config['index'];
			if (!empty($this->_config['table'])) {
				$path .= '/' . $this->_config['table'];
			} elseif (!empty($request['table'])) {
				$path .= '/' . $request['table'];
			}
			$request['uri']['path'] = $path;
		}
		return $request;
	}

	/**
	 * Accept either an array or a json encoded string - return a json encoded string
	 *
	 * @param mixed $json
	 * @return string $json
	 */
	public function asJson($json) {
		if (empty($json)) {
			return '';
		}
		if (is_string($json)) {
			$json = @json_decode($json, true);
		}
		if (is_array($json)) {
			return json_encode($json);
		}
		return '';
	}

	/**
	 * translate a request array into a curl request
	 *
	 * @param array $request
	 * @return string $curl
	 */
	public function asCurlRequest($request) {
		extract($request['uri']);
		$url = "{$scheme}://{$host}:{$port}{$path}";
		if (!empty($query)) {
			$url .= "?{$query}";
		}
		$data = '';
		if (!empty($request['body'])) {
			$data = $this->asJson($request['body']);
		}
		return "curl -X{$request['method']} '{$url}' -d '{$data}'";
	}

	/**
	 * sometimes we have to have the index AND the table on the path
	 *
	 * @param array $request
	 * @return string $table
	 * @throws ElasticSearchRequestException
	 */
	public function verifyTableOnPath($request) {
		// verify, we do have a table in the path
		$pathParts = explode('/',  trim($request['uri']['path'], '/'));
		if (count($pathParts) > 1) {
			$index = array_shift($pathParts);
			$table = array_shift($pathParts);
			return $table;
		}
		throw new ElasticSearchRequestException("Unable to complete request - you need to pass in the table, by either 'path', in the 'config' or in the 'request' array");
	}

}
