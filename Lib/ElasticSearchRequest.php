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
	 * Placeholder for the last request, response, error
	 *   useful to see what just happened
	 */
	public $last = array();

	/**
	 * construct the object
	 */
	public function __construct($config = array()) {
		$this->config($config);
	}

	/**
	 * Sends request to _search.
	 *
	 * @param mixed $query
	 *   Can be a simple string - if so, it's wrapped as a "query_string" query against the _all field.
	 *   http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
	 *
	 *   Can also be an array of a custom built query.  If it does have a 'query' key, it will be wrapped in ['query' => $orig].
	 *   (Is auto wrapping needed/useful?)
	 *
	 * @param array $request
	 *    Additional array of parameters to be sent along with the request.
	 *
	 * @param boolean $returnRaw (default = false)
	 *    If false, we return $ESresponse['hits']['hits'], removing the following information
	 *       - How long the query took
	 *		 - If the search timed out
	 *       - How many shards were searched, and success/fail count from those
	 *       - Total number of matched documents
	 *    Additionally, in $ESresponse['hits']['hits'], for each hit, the '_index' and '_type' are removed.
	 */
	public function search($query = '', $request = array(), $returnRaw = false) {

		// We will end up sending $request to elasticsearch.
		// the ['body'] key will be built from sending $query to buildQueryFromStringOrArray(),
		// and some certain keys from $request will be added to $request['body'].

		$request = $this->addConfigToRequest($request);
		$request['method'] = 'GET';
		$request['uri']['path'] .= '/_search';

		$requestBody = $this->buildQueryFromStringOrArray($query);

		// copy from $request to $requestBody - simple field copies.
		// 'from' and 'size' limited to integer
		foreach (array('from', 'size') as $intField) {
			if (!empty($request[$intField])) {
				$requestBody[$intField] = intval($request[$intField]);
			}
		}
		// 'min_score' simple copy
		foreach (array('min_score') as $field) {
			if (!empty($request[$field])) {
				$requestBody[$field] = $request[$field];
			}
		}

		// copy from $request to $requestBody - special cases
		if (!empty($request['limit']) && empty($requestBody['size'])) {
			// 'limit' as a synonym for size
			$requestBody['size'] = intval($request['limit']);
		}
		if (!empty($request['page']) && !empty($request['size'])) {
			// auto set 'from' if 'page' and 'size' are set
			$requestBody['from'] = (intval($request['page']) - 1) * intval($request['size']);
		}
		if (!empty($request['fields'])) {
			// 'fields' may be an array
			$requestBody['fields'] = is_array($request['fields']) ? array_values($request['fields']) : explode(',', $request['fields']);
		}

		$request['body'] = $this->asJson($requestBody);
		$ESresponse = $this->request($request);

		if ($returnRaw) {
			return $ESresponse;
		}
		if (empty($ESresponse['hits']['hits'])) {
			return array();
		}
		$output = array();

		foreach (array_keys($ESresponse['hits']['hits']) as $i) {
			$hit = $ESresponse['hits']['hits'][$i];
			// Always include ID
			$output[$i] = array(
				'_id' => $hit['_id'],
			);

			// Single fields we add if existing
			foreach (array('_score') as $singleField) {
				if (!empty($hit[$singleField])) {
					$output[$i][$singleField] = $hit[$singleField];
				}
			}

			// These are arrays that are merged in if they exist
			foreach (array('_source', 'fields') as $array_field) {
				if (!empty($hit[$array_field])) {
					$output[$i] += $hit[$array_field];
				}
			}
			unset($ESresponse['hits']['hits'][$i]);
		}

		return $output;
	}

	public function createIndex($index, $request = array()) {
		$request['uri']['path'] = "/{$index}";
		$request = $this->addConfigToRequest($request);
		$request['method'] = 'PUT';
		$request['body'] = '';
		$data = $this->request($request);

		if ($data['_code'] != 200) {
			return false;
		}

		// Wait for index to become ready. Wait a max of 60 seconds, retry every 50ms
		$startTime = time();
		while ($startTime + 60 > time()) {
			$stages = $this->getRecoveryStagesForIndex($index);
			if (!in_array('START', $stages)) {
				return true;
			}
			usleep(50000); //50ms
		}

		$this->log("CreateIndex:  Index [$index] took longer than 60 seconds to become ready.");
		return true;
	}

	public function deleteIndex($index, $request = array()) {
		$request['uri']['path'] = "/{$index}";
		$request = $this->addConfigToRequest($request);
		$request['method'] = 'DELETE';
		$request['body'] = '';
		$data = $this->request($request);
		return $data['_code'] == 200;
	}

	public function createMapping($mapping, $request = array()) {
		$request = $this->addConfigToRequest($request);
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
		return $data['_code'] == 200;
	}

	public function getMapping($request = array()) {
		$request = $this->addConfigToRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'GET';
		$request['uri']['path'] .= '/_mapping';
		$request['body'] = '';
		$data = $this->request($request);
		return $data;
	}

	public function createRecord($data, $request = array()) {
		$request = $this->addConfigToRequest($request);
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
		$request = $this->addConfigToRequest($request);
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
		$request = $this->addConfigToRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'DELETE';
		$request['uri']['path'] .= "/{$id}"; // explicit id = overwrite
		$request['body'] = '';
		$data = $this->request($request);
		return $data['_code'] == 200;
	}

	public function getRecord($id, $request = array()) {
		$request = $this->addConfigToRequest($request);
		$this->verifyTableOnPath($request);
		$request['method'] = 'GET';
		$request['uri']['path'] .= "/{$id}";
		$request['body'] = '';
		$raw = $this->request($request);
		$data = $raw['_source'];
		$data['_id'] = $raw['_id'];
		return $data;
	}

	/**
	 *  Calls the "indicies recovery" API and returns raw data.
	 *  http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-recovery.html
	 *
	 *  Note:  Tells this->request to skip handleRequest().  That way, we may use this function
	 *  inside handleRequest() without causing infinite loops.
	 *
	 *  @param string Index name
	 *  @param array Request override [not used]
	 *  @return array Raw json recovery data
	 **/
	public function getRecoveryRawForIndex($index, $request = array()) {
		$request['uri']['path'] = "/{$index}/_recovery";
		$request = $this->addConfigToRequest($request);
		$request['method'] = 'GET';
		$request['body'] = '';
		$data = $this->request($request, array('skipHandleResponse' => true));
		#pr($data);
		return $data;
	}

	/**
	 *  Calls the "indicies recovery" API and returns an array of stages, 1 for each shard.
	 *  http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/indices-recovery.html
	 *
	 *  Note:  Tells this->request to skip handleRequest().  That way, we may use this function
	 *  inside handleRequest() without causing infinite loops.
	 *
	 *  Example of return value when everything is good
	 *  ["DONE", "DONE", "DONE", "DONE"]
	 *
	 *  Example of return value when shards are rebuilding
	 *  ["DONE", "DONE", "TRANSLOG", "DONE", "TRANSLOG"]
     *
	 *  Example of return value when a newly created index is not ready to be used
	 *  ["START", "START", "START", "START", "START"]
	 *
	 *  @param string Index name
	 *  @param array Request override [not used]
	 *  @return array Stages
	 **/
	public function getRecoveryStagesForIndex($index, $request = array()) {
		$recovery = $this->getRecoveryRawForIndex($index, $request);
		$stages = Hash::extract($recovery, "$index.shards.{n}.stage");
		return $stages;
	}


	public function exists($id, $request = array()) {
		$request = $this->addConfigToRequest($request);
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
	 * buildQueryFromStringOrArray: Pass it a query, it returns a possibly modified query
	 *
	 *    If it's a string query, it's passed through textQueryToFuzzyOrQueryStringQuery(), which turns it into
	 *                  (a) a query_string query that's lenient.
	 *               or (b) a fuzzy_like_this query if the first character is a ~.
	 *
	 *    If it's an array query, it's wrapped in ['query' => ???] if there's no 'query' key. (Why?)
	 *
	 *    If it's an array query and the  array key 'query' is a string,
	 *    that value is wrapped in a 'query_string' query.  (Why?)
	 *
	 * TODO: this needs to be extended a bunch
	 *
	 * @param mixed $query or $query_string
	 * @return array $query as nested query array
	 */
	public function buildQueryFromStringOrArray($query = '') {
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
			$query = $this->textQueryToFuzzyOrQueryStringQuery($query);
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
	 * Automate a query: string --> nested array query
	 *   supports:
	 *     '~fuzzy' - supports fuzzy_like_this creation
	 *				  http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-flt-query.html
	 *
	 *     'query'  - defaults to query, query_string, query
	 *               http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
	 *       'term'
	 *       'partial*'
	 *       '"phrase"'
	 *       'field:value AND term'
	 *
	 * @param string $query_string
	 * @return array $query as array
	 */
	public function textQueryToFuzzyOrQueryStringQuery($query_string) {
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
	 * @param array $request
	 *   - method [GET, POST, PUT, DELETE]
	 *   - index (optional override)
	 *   - table (optional - appends to index to create path)
	 *   - request (optional override to the request details)
	 *     - uri
	 *       - path (we auto-set this to: /$index/$table/$action)
	 *       - query (eg: ?pretty-true)
	 *     - header
	 * @param array $options
	 *     - Set key 'skipHandleResponse' to get raw data returned
	 *       instead of calling handleResponse().
	 *
	 * @return array $response friendly (usually via handleResponse())
	 */
	public function request($request = array(), $options = array()) {
		$this->log(compact('request'));
		$this->last['request'] = $request;
		$this->last['response'] = null;
		$this->last['error'] = null;

		// do the request
		try {
			$response = parent::request($request);
		} catch (SocketException $e) {
			$this->last['error'] = $e->getMessage();
			$this->log(array('SocketException' => $e->getMessage()));
			return false;
		}

		// log the response
		$this->log(compact('response'));
		$this->last['response'] = $response;

		// if asked to skipHandleResponse, return it
		if (!empty($options['skipHandleResponse'])) {
			return array_merge( (array) @json_decode($response->body, true), array('_code' => $response->code));
		}

		// handle the response
		try {
			return $this->handleResponse($response, $request);
		} catch(ElasticSearchRequestException $e) {
			$this->last['error'] = $e->getMessage();
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
	 * Parse the response,
	 *  - look for errors
	 *  - handle "known" errors
	 *    - in case of "missing index", create index and re-request
	 *  - handle unkown errors (exception)
	 *  - handle empty response (exception)
	 *  - return response
	 *
	 * @param object $response
	 * @param array $request
	 * @return array $response friendly
	 * @throws ElasticSearchRequestException
	 */
	public function handleResponse($response, $request = null) {
		// validate the request
		$data = array_merge( (array) @json_decode($response->body, true), array('_code' => $response->code));
		if (!empty($data['error']) || !in_array($response->code, array(200, 201))) {
			$error = (empty($data['error']) ? 'unknown error' : $data['error']);
			if (preg_match('#IndexMissingException\[\[(.+)\] missing\]#', $error, $match)) {
				$index = $match[1];
				if ($this->createIndex($index, $request) && empty($this->retrying)) {
					$this->retrying = true; // retry -- watch out for a loop
					$this->log('retrying');
					return $this->request($request);
				}
			}
			if (strpos($error, 'IndexAlreadyExistsException') !== false) {
				return array('message' => 'IndexAlreadyExistsException', '_code' => 200);
			}
			if (!empty($response->code)) {
				$error = str_replace(array('[', ']'), ' ', $error);
				throw new ElasticSearchRequestException("Request failed, got a response code of {$response->code} {$error}");
			}
		}
		if (empty($data) && $request['method'] != 'HEAD') {
			throw new ElasticSearchRequestException("Request failed, response empty: {$response->body}");
		}
		return $data;
	}

	/**
	 * Get and set config
	 *  - also sets up the default config if not yet done
	 *
	 * Look at the Config/elastic_search_request.php file for an example of all
	 * the config options.
	 *
	 * @param array $config {optional} pass in any config you want to set, ongoing
	 * @return array $config
	 */
	public function config($config = array()) {
		if (empty($this->_config['defaultLoaded'])) {
			$this->_config['defaultLoaded'] = true;
			try {
				Configure::load('elastic_search_request');
			} catch (ConfigureException $e) {
				// no config file?...  validation later will confirm functionality
			}
			$defaultConfig = Configure::read('ElasticSearchRequest');
			if (!empty($defaultConfig['default'])) {
				$this->_config = Hash::merge($this->_config, $defaultConfig['default']);
			}
			$isUnitTest = Configure::read('inUnitTest');
			if (!empty($isUnitTest) && !empty($defaultConfig['test'])) {
				$this->_config = Hash::merge($this->_config, $defaultConfig['test']);
			}
			// defaulting logging to be false in prod, true in dev
			if (!array_key_exists('log', $this->_config)) {
				$this->_config['log'] = (Configure::read('debug') > 0);
			}
		}
		if (!empty($config)) {
			$this->_config = Hash::merge($this->_config, $config);
			$this->_config['verified'] = false;
		}
		if (empty($this->_config['verified'])) {
			$this->verifyConfig($this->_config);
			$this->_config['verified'] = true;
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
	public function addConfigToRequest($request) {
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
