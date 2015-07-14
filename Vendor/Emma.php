<?php
	/**
	 * Emma API Wrapper Exception for if there is a missing Account ID
	 *
	 * @category  Services
	 * @author    Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @copyright 2013 Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @license   http://www.opensource.org/licenses/mit-license.php MIT
	 * @link      https://github.com/myemma/emma-wrapper-php
	 */
	class Emma_Missing_Account_Id extends Exception {
		protected $message = "All requests must include your Account ID";
	}

	/**
	 * Emma API Wrapper Exception for if there is a missing public key or private key
	 *
	 * @category  Services
	 * @author    Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @copyright 2013 Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @license   http://www.opensource.org/licenses/mit-license.php MIT
	 * @link      https://github.com/myemma/emma-wrapper-php
	 */
	class Emma_Missing_Auth_For_Request extends Exception {
		protected $message = "All requests must include both your Public API Key and your Private API Key";
	}

	/**
	 * Emma API Wrapper Custom Exception
	 *
	 * @category  Services
	 * @author    Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @copyright 2013 Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @license   http://www.opensource.org/licenses/mit-license.php MIT
	 * @link      https://github.com/myemma/emma-wrapper-php
	 */

	class Emma_Invalid_Response_Exception extends Exception {
		/**
		* Default Message
		* @access protected
		* @var string
		*/
		protected $message = 'The requested URL responded with HTTP code %d';
		/**
		* HTTP Response Code
		* @access protected
		* @var integer
		*/
		protected $httpCode;
		/**
		* HTTP Response Body
		* @access protected
		* @var string
		*/
		protected $httpBody;
		/**
		* Constructor
		*
		* @param string $message
		* @param string $code
		* @param string $httpBody
		* @param integer $httpCode
		* @access protected
		* @var integer
		*/
		public function __construct($message = null, $code = 0, $httpBody, $httpCode = 0) {
			$this->httpBody = $httpBody;
			$this->httpCode = $httpCode;
			$message = sprintf($this->message, $httpCode);
			parent::__construct($message, $code);
		}
		/**
		* Get HTTP response body
		*/
		public function getHttpBody() {
			return $this->httpBody;
		}
		/**
		* Get HTTP response code
		*/
		public function getHttpCode() {
			return $this->httpCode;
		}
	}

	/**
	 * Emma API Wrapper
	 *
	 * @category  Services
	 * @package   Services_Emma
	 * @author    Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @copyright 2013 Dennis Monsewicz <dennismonsewicz@gmail.com>
	 * @license   http://www.opensource.org/licenses/mit-license.php MIT
	 * @link      https://github.com/myemma/emma-wrapper-php
	 */
	class Emma {
		/**
		* Cache the API base url
		*/
		public $base_url = "https://api.e2ma.net/";
		/**
		* Cache the user account id for API usage
		*/
		protected $_account_id;
		/**
		* Cache the user public key for API usage
		*/
		protected $_pub_key;
		/**
		* Cache the user private key for API usage
		*/
		protected $_priv_key;
		/**
		* Cache optional postdata for HTTP request
		*/
		public $_postData = array();
		/**
		* Cache optional query params for HTTP request
		*/
		public $_params = array();

		protected $_debug = false;

		/**
		* Connect to the Emma API
		* @param string $account_id		Your Emma Account Id
		* @param string $pub_api_key	Your Emma Public API Key
		* @param string $priv_api_key	Your Emma Public API Key
		* @access public
		*/
		function __construct($account_id, $pub_api_key, $pri_api_key, $debug = false) {
			if(empty($account_id))
				throw new Emma_Missing_Account_Id();

			if(empty($pub_api_key) || empty($pri_api_key))
				throw new Emma_Missing_Auth_For_Request();

			$this->_account_id = $account_id;
			$this->_pub_key = $pub_api_key;
			$this->_priv_key = $pri_api_key;
			$this->_debug = $debug;
		}

		/**
		* API Calls to the Members related endpoint(s)
		* @see http://api.myemma.com/api/external/members.html
		*/

		/**
		* Get a basic listing of all members in an account.
		* @param array $params		Array Additional HTTP GET params
		* @access public
		* @return A list of members in the given account
		*/
		function myMembers($params = array()) {
			return $this->get('/members', $params);
		}

		/**
		* Get detailed information on a particular member, including all custom fields.
		* @param int $id		Member Id
		* @param array $params		Array Additional HTTP GET params
		* @access public
		* @return A single member if one exists.
		*/
		function membersListById($id, $params = array()) {
			return $this->get("/members/{$id}", $params);
		}

		/**
		* Get detailed information on a particular member, including all custom fields, by email address instead of ID.
		* @param string $email		Member Email
		* @param array $params		Array Additional HTTP GET params
		* @access public
		* @return A single member if one exists.
		*/
		function membersListByEmail($email, $params = array()) {
			return $this->get("/members/email/{$email}", $params);
		}

		/**
		* If a member has been opted out, returns the details of their optout, specifically date and mailing_id.
		* @param int $id		Member ID
		* @access public
		* @return Member opt out date and mailing if member is opted out
		*/
		function membersListOptout($id) {
			return $this->get("/members/{$id}/optout");
		}

		/**
		* Update a member’s status to optout keyed on email address instead of an ID.
		* @param string $email		Member Email
		* @access public
		* @return True if member status change was successful or member was already opted out.
		*/
		function membersOptout($email) {
			return $this->put("/members/email/optout/{$email}");
		}

		/**
		* Add new members or update existing members in bulk. If you are doing actions for a single member please see the membersAdd() function
		* @param array $params		Array of options
		* @access public
		* @return An import id
		*/
		function membersBatchAdd($params = array()) {
			return $this->post('/members', $params);
		}

		/**
		* Adds or updates a single audience member. If you are performing actions on bulk members please use the membersBatchAdd() function
		* @param array $member_data		Array of options
		* @access public
		* @return The member_id of the new or updated member, whether the member was added or an existing member was updated, and the status of the member. The status will be reported as ‘a’ (active), ‘e’ (error), or ‘o’ (optout).
		*/
		function membersAddSingle($member_data = array()) {
			return $this->post("/members/add", $member_data);
		}

		/**
		* Takes the necessary actions to signup a member and enlist them in the provided group ids. You can send the same member multiple times and pass in new group ids to signup.
		* @param array $member_data	Array of options
		* @access public
		* @return the member_id of the member, and their status. The status will be reported as 'a' (active), 'e' (error), or 'o' (optout).
		*/
		function membersSignup($member_data = array()) {
			return $this->post("/members/signup", $member_data);
		}

		/**
		* Delete an array of members. The members will be marked as deleted and cannot be retrieved.
		* @param array $params		Array of options
		* @access public
		* @return True if all members are successfully deleted, otherwise False.
		*/
		function membersRemove($params = array()) {
			return $this->put("/members/delete", $params);
		}

		/**
		* Change the status for an array of members. The members will have their member_status_id updated.
		* @param array $params		Array of options
		* @access public
		* @return True if the members are successfully updated, otherwise False.
		*/
		function membersChangeStatus($params = array()) {
			return $this->put("/members/status", $params);
		}

		/**
		* Update a single member’s information.
		* Update the information for an existing member. Note that this method allows the email address to be updated (which cannot be done with a POST, since in that case the email address is used to identify the member).
		* @param int $id		Member ID
		* @param array $params	Array of options
		* @access public
		* @return True if the member was updated successfully
		*/
		function membersUpdateSingle($id, $params = array()) {
			return $this->put("/members/{$id}", $params);
		}

		/**
		* Delete the specified member. The member, along with any associated response and history information, will be completely removed from the database.
		* @param int $id	Member ID
		* @access public
		* @return True if the member was updated successfully
		*/
		function membersRemoveSingle($id) {
			return $this->delete("/members/{$id}");
		}

		/**
		* Get the groups to which a member belongs.
		* @param int $id	Member ID
		* @access public
		* @return An array of groups.
		*/
		function membersListSingleGroups($id) {
			return $this->get("/members/{$id}/groups");
		}

		/**
		* Add a single member to one or more groups.
		* @param int $id		Member ID
		* @param array $params	Array of options
		* @access public
		* @return An array of ids of the affected groups.
		*/
		function membersGroupsAdd($id, $params = array()) {
			return $this->put("/members/{$id}/groups", $params);
		}

		/**
		* Remove a single member from one or more groups.
		* @param int $id		Member ID
		* @param array $params	Array of options
		* @access public
		* @return An array of references to the affected groups.
		*/
		function membersRemoveSingleFromGroups($id, $params = array()) {
			return $this->put("/members/{$id}/groups/remove", $params);
		}

		/**
		* Delete all members.
		* @param string $member_status_id	Status to set all affected members
		* @access public
		* @return true
		*/
		function membersRemoveAll($members_status_id = "a") {
			$params = array('member_status_id' => $members_status_id);
			return $this->delete("/members", $params);
		}

		/**
		* Remove the specified member from all groups.
		* @param int $member_id		Member ID
		* @access public
		* @return True if the member is removed from all groups
		*/
		function membersRemoveFromAllGroups($member_id) {
			return $this->delete("/members/{$member_id}/groups");
		}

		/**
		* Remove multiple members from groups.
		* @param array $params		Array of options
		* @access public
		* @return True if the members are deleted, otherwise False.
		*/
		function membersRemoveMultipleFromGroups($params = array()){
			return $this->put("/members/groups/remove", $params);
		}

		/**
		* Get the entire mailing history for a member.
		* @param int $id	Member ID
		* @access public
		* @return Message history details for the specified member.
		*/
		function membersMailingHistory($id, $params = array()) {
			return $this->get("/members/{$id}/mailings", $params);
		}

		/**
		* Get a list of members affected by this import.
		* @param int $import_id		ID of import
		* @access public
		* @return 	A list of members in the given account and import.
		*/
		function membersImported($import_id) {
			return $this->get("/members/imports/{$import_id}/members");
		}

		/**
		* Get information and statistics about this import.
		* @param int $import_id		ID of import
		* @access public
		* @return 	Import details for the given import_id.
		*/
		function membersImportStats($import_id) {
			return $this->get("/members/imports/{$import_id}");
		}

		/**
		* Get information about all imports for this account.
		* @param array $params		Array of options
		* @access public
		* @return 	An array of import details.
		*/
		function myImports($params = array()) {
			return $this->get("/members/imports", $params);
		}

		/**
		* Update an import record to be marked as ‘deleted’.
		* @access public
		* @return 	True if the import is marked as deleted.
		*/
		function membersRemoveImport() {
			return $this->delete("/members/imports/delete");
		}

		/**
		* Copy all account members of one or more statuses into a group.
		* @param int $group_id		ID of group
		* @param array $params		Array of options
		* @access public
		* @return 	True
		*/
		function membersCopyToGroup($group_id, $params = array()) {
			return $this->put("/members/{$group_id}/copy", $params);
		}

		/**
		* Update the status for a group of members, based on their current status. Valid statuses id are (‘a’,’e’, ‘f’, ‘o’) active, error, forwarded, optout.
		* @param string $status_from	Existing status
		* @param string $status_to		New Status
		* @param int $group_id			ID of Group
		* @access public
		* @return 	True
		*/
		function membersUpdateGroupMembersStatus($status_from, $status_to, $group_id = null) {
			$data = array();
			$data["group_id"] = $group_id;
			return $this->put("/members/status/{$status_from}/to/{$status_to}", $data);
		}

		/**
		* API Calls to the Fields related endpoint(s)
		* @see http://api.myemma.com/api/external/fields.html
		*/

		/**
		* Gets a list of this account’s defined fields.
		* @param array $params		Array of options
		* @access public
		* @return 	An array of fields.
		*/
		function myFields($params = array()) {
			return $this->get("/fields", $params);
		}

		/**
		* Gets the detailed information about a particular field.
		* @param int $id			ID of Field
		* @param array $params		Array of options
		* @access public
		* @return 	A field.
		*/
		function fieldsGetById($id, $params = array()) {
			return $this->get("/fields/{$id}", $params);
		}

		/**
		* Create a new field field.
		* There must not already be a field with this name.
		* @param array $params		Array of options
		* @access public
		* @return 	A reference to the new field.
		*/
		function fieldsAddSingle($params = array()) {
			return $this->post("/fields", $params);
		}

		/**
		* Deletes a field.
		* @param int $id	ID of Field
		* @access public
		* @return 	True if the field is deleted, False otherwise.
		*/
		function fieldsRemoveSingle($id) {
			return $this->delete("/fields/{$id}");
		}

		/**
		* Clear the member data for the specified field.
		* @param int $id	ID of Field
		* @access public
		* @return 	True if all of the member field data is deleted
		*/
		function fieldsRemoveMemberDataForField($id) {
			return $this->post("/fields/{$id}/clear");
		}

		/**
		* Updates an existing field.
		* @param int $id			ID of Field
		* @param array $params		Array of options
		* @access public
		* @return 	A reference to the updated field.
		*/
		function fieldsUpdateSingle($id, $params = array()) {
			return $this->put("/fields/{$id}", $params);
		}

		/**
		* API Calls to the Groups related endpoint(s)
		* @see http://api.myemma.com/api/external/groups.html
		*/

		/**
		* Get a basic listing of all active member groups for a single account.
		* @param array $params		Array of options
		* @access public
		* @return 	An array of groups.
		*/
		function myGroups($params = array()) {
			return $this->get("/groups", $params);
		}

		/**
		* Create one or more new member groups.
		* @param array $params		Array of options
		* @access public
		* @return 	An array of the new group ids and group names.
		*/
		function groupsAdd($params = array()) {
			return $this->post("/groups", $params);
		}

		/**
		* Get the detailed information for a single member group.
		* @param int $id	ID of group
		* @access public
		* @return 	A group.
		*/
		function groupsGetById($id) {
			return $this->get("/groups/{$id}");
		}

		/**
		* Update information for a single member group.
		* @param int $id		ID of group
		* @param array $params	Array of options
		* @access public
		* @return 	True if the update was successful
		*/
		function groupsUpdateSingle($id, $params = array()) {
			return $this->put("/groups/{$id}", $params);
		}

		/**
		* Delete a single member group.
		* @param int $id		ID of group
		* @access public
		* @return 	True if the group is deleted
		*/
		function groupsRemoveSingle($id) {
			return $this->delete("/groups/{$id}");
		}

		/**
		* Get the members in a single active member group.
		* @param int $id		ID of group
		* @param array $params	Array of options
		* @access public
		* @return 	An array of members.
		*/
		function groupsGetMembers($id, $params = array()) {
			return $this->get("/groups/{$id}/members", $params);
		}

		/**
		* Add a list of members to a single active member group.
		* @param int $id		ID of group
		* @param array $params	Array of options
		* @access public
		* @return 	An array of references to the members added to the group. If a member already exists in the group or is not a valid member, that reference will not be returned.
		*/
		function groupsAddMembersToGroup($id, $params = array()) {
			return $this->put("/groups/{$id}/members", $params);
		}

		/**
		* Remove members from a single active member group.
		* @param int $id		ID of group
		* @param array $params	Array of options
		* @access public
		* @return 	An array of references to the removed members.
		*/
		function groupsRemoveMembers($id, $params = array()) {
			return $this->put("/groups/{$id}/members/remove", $params);
		}

		/**
		* Remove all members from a single active member group.
		* @param int $id		ID of group
		* @param array $params	Array of options
		* @access public
		* @return 	Returns the number of members removed from the group.
		*/
		function groupsRemoveAllMembers($id, $params = array()) {
			return $this->delete("/groups/{$id}/members", $params);
		}

		/**
		* Remove all members from all active member groups as a background job. The member_status_id parameter must be set.
		* @param int $id		ID of group
		* @param array $params	Array of options
		* @access public
		* @return 	Returns true.
		*/
		function groupsRemoveAllMembersAsBackgroundJob($id, $params = array()) {
			return $this->delete("/groups/{$id}/members/remove", $params);
		}

		/**
		* Copy all the users of one group into another group.
		* @param int $from_id	ID of existing group
		* @param int $to_id		ID of new group
		* @param array $params	Array of options
		* @access public
		* @return 	Returns true.
		*/
		function groupsCopyMembers($from_id, $to_id, $params = array()) {
			return $this->put("/groups/{$from_id}/{$to_id}/members/copy", $params);
		}

		/**
		* API Calls to the Mailings related endpoint(s)
		* @see http://api.myemma.com/api/external/mailings.html
		*/

		/**
		* Get information about current mailings.
		* @param array $params	Array of options
		* @access public
		* @return 	An array of mailings.
		*/
		function myMailings($params = array()) {
			return $this->get("/mailings", $params);
		}

		/**
		* Get detailed information for one mailing.
		* @param int $id	ID of mailing
		* @access public
		* @return 	A mailing
		*/
		function mailingsGetById($id) {
			return $this->get("/mailings/{$id}");
		}

		/**
		* Get the list of members to whom the given mailing was sent. This does not include groups or searches.
		* @param int $id			ID of mailing
		* @param int $member_id 	ID of member
		* @param array $params		Array of options
		* @access public
		* @return 	A mailing
		*/
		function mailingsPersonalizedMemberMailing($id, $member_id, $params = array()) {
			return $this->get("/mailings/{$id}/messages/{$member_id}", $params);
		}

		/**
		* Gets the personalized message content as sent to a specific member as part of the specified mailing.
		* @param int $id			ID of mailing
		* @param array $params		Array of options
		* @access public
		* @return 	Message content from a mailing, personalized for a member. The response will contain all parts of the mailing content by default, or just the type of content specified by type.
		*/
		function mailingsMembersById($id, $params = array()) {
			return $this->get("/mailings/{$id}/members", $params);
		}

		/**
		* Get the groups to which a particular mailing was sent.
		* @param int $id			ID of mailing
		* @param array $params		Array of options
		* @access public
		* @return 	An array of groups.
		*/
		function mailingsGetGroups($id, $params = array()) {
			return $this->get("/mailings/{$id}/groups", $params);
		}

		/**
		* Get all searches associated with a sent mailing.
		* @param int $id			ID of mailing
		* @param array $params		Array of options
		* @access public
		* @return 	An array of searches.
		*/
		function mailingsSearches($id, $params = array()) {
			return $this->get("/mailings/{$id}/searches", $params);
		}

		/**
		* Update status of a current mailing.
		* The status can be one of canceled, paused or ready. This method can be used to control the progress of a mailing by pausing, canceling or resuming it.
		* Once a mailing is canceled it can’t be resumed, and will not show in the normal mailing_list output.
		* @param int $id			ID of mailing
		* @param array $params		Array of options
		* @access public
		* @return 	Returns the mailing’s new status
		*/
		function mailingsUpdateSingle($id, $params = array()) {
			return $this->put("/malings/{$id}", $params);
		}

		/**
		* Sets archived timestamp for a mailing so it is no longer included in mailing_list.
		* @param int $id	ID of mailing
		* @access public
		* @return True if the mailing is successfully archived.
		*/
		function mailingsRemoveSingle($id) {
			return $this->delete("/mailings/{$id}");
		}

		/**
		* Cancels a mailing that has a current status of pending or paused. All other statuses will result in a 404.
		* @param int $id	ID of mailing
		* @access public
		* @return 	True if mailing marked as cancelled.
		*/
		function mailingsCanceledQueued($id) {
			return $this->delete("/mailings/cancel/{$id}");
		}

		/**
		* Forward a previous message to additional recipients. If these recipients are not already in the audience, they will be added with a status of FORWARDED.
		* @param int $id		ID of mailing
		* @param int $member_id ID of member
		* @param array $params	Array of options
		* @access public
		* @return 	A reference to the new mailing.
		*/
		function mailingsForward($id, $member_id, $params = array()) {
			return $this->post("/forwards/{$id}/{$member_id}", $params);
		}

		/**
		* Send a prior mailing to additional recipients. A new mailing will be created that inherits its content from the original.
		* @param int $id		ID of mailing
		* @param array $params	Array of options
		* @access public
		* @return 	A reference to the new mailing.
		*/
		function mailingsSendExisting($id, $params = array()) {
			return $this->post("/mailings/{$id}", $params);
		}

		/**
		* Get heads up email address(es) related to a mailing.
		* @param int $id	ID of mailing
		* @param array $params Array of params
		* @access public
		* @return 	An array of heads up email addresses
		*/
		function mailingsHeadsup($id, $params = array()) {
			return $this->get("/mailings/{$id}/headsup", $params);
		}

		/**
		* Validate that a mailing has valid personalization-tag syntax. Checks tag syntax in three params:
		* @param array $params Array of params
		* @access public
		* @return 	true
		*/
		function mailingsValidate($params = array()) {
			return $this->post("/mailings/validate", $params);
		}

		/**
		* Declare the winner of a split test manually. In the event that the test duration has not elapsed,
		* the current stats for each test will be frozen and the content defined in the user declared winner will sent to the remaining members for the mailing.
		* Please note, any messages that are pending for each of the test variations will receive the content assigned to them when the test was initially constructed.
		* @param int $mailing_id	ID of mailing
		* @param int $winner_id		ID of winner
		* @access public
		* @return 	True on success
		*/
		function mailingsDeclareWinnerOfSplitTest($mailing_id, $winner_id) {
			return $this->post("/mailings/{$mailing_id}/winner/{$winner_id}");
		}

		/**
		* API Calls to the Response related endpoint(s)
		* @see http://api.myemma.com/api/external/response.html
		*/

		/**
		* Get the response summary for an account.
		* This method will return a month-based time series of data including sends, opens, clicks, mailings, forwards, and opt-outs. Test mailings and forwards are not included in the data returned.
		* @param array $params	Array of options
		* @access public
		* @return 	Array of account related data
		*/
		function myAccountSummary($params = array()) {
			return $this->get("/response", $params);
		}

		/**
		* Get the response summary for a particular mailing.
		* This method will return the counts of each type of response activity for a particular mailing.
		* @param int $id	ID of Mailing
		* @access public
		* @return 	A single object with the following fields: * name – name of mailing * sent – messages sent * delivered – messages delivered * bounced – messages that failed delivery due to a hard or soft bounce * opened – messages opened * clicked_unique – link clicks, unique on message * clicked – total link clicks, including duplicates * forwarded – times the mailing was forwarded * opted_out – people who opted out based on this mailing * signed_up – people who signed up based on this mailing * shared – people who shared this mailing * share_clicked – number of clicks shares of this mailing received * webview_shared – number of times the customer has shared * webview_share_clicked – number of clicks customer-shares of this mailing received
		*/
		function responseSingleSummary($mailing_id) {
			return $this->get("/response/{$mailing_id}");
		}

		/**
		* Get mailing information for a particular action
		* @param int $id			ID of Mailing
		* @param string $action		Action in which to return information on
		* @param array $params		Array of options
		* @access public
		* @return 	An array of objects that relate to the particular $action
		*/
		function responseMailingInformation($id, $action = "sends", $params = array()) {
			return $this->get("/response/{$id}/{$action}", $params);
		}

		/**
		* Get the customer share associated with the share id.
		* @param int $share_id			ID of share
		* @param array $params		Array of options
		* @access public
		* @return 	An array of objects
		*/
		function responseCustomerShareInformation($share_id, $params = array()) {
			return $this->get("/response/{$share_id}/customer_share", $params);
		}

		/**
		* Get overview of shares pertaining to this mailing_id.
		* @param int $id	ID of mailing
		* @access public
		* @return 	An array of objects
		*/
		function responseSharesOverview($id) {
			return $this->get("/response/{$id}/shares/overview");
		}

		/**
		* API Calls to the Searches related endpoint(s)
		* @see http://api.myemma.com/api/external/searches.html
		*/

		/**
		* Retrieve a list of saved searches.
		* @param array $params	Array of options
		* @access public
		* @return 	An array of searches.
		*/
		function mySearches($params = array()) {
			return $this->get("/searches", $params);
		}

		/**
		* Get the details for a saved search.
		* @param int $id		ID of search
		* @param array $params	Array of options
		* @access public
		* @return 	A search
		*/
		function searchesGetById($id, $params = array()) {
			return $this->get("/searches/{$id}", $params);
		}

		/**
		* Create a saved search
		* @param array $params	Array of options
		* @access public
		* @return 	The ID of the new search
		*/
		function searchesCreateSingle($params = array()) {
			return $this->post("/searches", $params);
		}

		/**
		* Update a saved search.
		* No parameters are required, but either the name or criteria parameter must be present for an update to occur.
		* @param int $id 		ID of search
		* @param array $params	Array of options
		* @access public
		* @return 	True if the update was successful
		*/
		function searchesUpdateSingle($id, $params = array()) {
			return $this->put("/searches/{$id}", $params);
		}

		/**
		* Delete a saved search. The member records referred to by the search are not affected.
		* @param int $id 	ID of search
		* @access public
		* @return 	True if the search is deleted.
		*/
		function searchesRemoveSingle($id) {
			return $this->delete("/searches/{$id}");
		}

		/**
		* Get the members matching the search.
		* @param int $id 	ID of search
		* @param array $params	Array of options
		* @access public
		* @return 		An array of members.
		*/
		function searchesMembers($id, $params = array()) {
			return $this->get("/searches/{$id}/members", $params);
		}

		/**
		* API Calls to the Triggers related endpoint(s)
		* @see http://api.myemma.com/api/external/triggers.html
		*/

		/**
		* Get a basic listing of all triggers in an account.
		* @param array $params	Array of options
		* @access public
		* @return 	An array of triggers
		*/
		function myTriggers($params = array()) {
			return $this->get("/triggers", $params);
		}

		/**
		* Create a new trigger.
		* @param array $params	Array of options
		* @access public
		* @return 	The new trigger’s id.
		*/
		function triggersCreate($params = array()) {
			return $this->post("/triggers", $params);
		}

		/**
		* Look up a trigger by trigger id.
		* @param int $id	Trigger ID
		* @access public
		* @return 	A trigger
		*/
		function triggersGetSingle($id) {
			return $this->get("/triggers/{$id}");
		}

		/**
		* Update or edit a trigger.
		* @param int $id	Trigger ID
		* @access public
		* @return 	The id of the updated trigger
		*/
		function triggersUpdateSingle($id) {
			return $this->put("/triggers/{$id}");
		}

		/**
		* Delete a trigger.
		* @param int $id	Trigger ID
		* @access public
		* @return  	True if the trigger is deleted.
		*/
		function triggersRemoveSingle($id) {
			return $this->delete("/triggers/{$id}");
		}

		/**
		* Get mailings sent by a trigger.
		* @param int $id		Trigger ID
		* @param array $params	Array of options
		* @access public
		* @return  An array of mailings.
		*/
		function triggersMailings($id, $params = array()) {
			return $this->get("/triggers/{$id}/mailings", $params = array());
		}

		/**
		* API Calls to the Webhooks related endpoint(s)
		* @see http://api.myemma.com/api/external/webhooks.html
		*/

		/**
		* Get a basic listing of all webhooks associated with an account.
		* @param array $params	Array of options
		* @access public
		* @return  	A list of webhooks that belong to the given account
		*/
		function myWebhooks($params = array()) {
			return $this->get("/webhooks", $params);
		}

		/**
		* Get information for a specific webhook belonging to a specific account.
		* @param int $id	ID of webhook
		* @access public
		* @return  	Details for a single webhook
		*/
		function webhooksGetSingle($id) {
			return $this->get("/webhooks/{$id}");
		}

		/**
		* Get a listing of all event types that are available for webhooks.
		* @param array $params	Array of options
		* @access public
		* @return  	A list of event types and descriptions
		*/
		function webhooksGetEvents($params = array()) {
			return $this->get("/webhooks/events", $params);
		}

		/**
		* Create an new webhook.
		* @param array $params	Array of options
		* @access public
		* @return  id of new webhook
		*/
		function webhooksCreate($params = array()) {
			return $this->post("/webhooks", $params);
		}

		/**
		* Update an existing webhook. Takes the same params as webhooksCreate.
		* @param int $id 		ID of webhook
		* @param array $params	Array of options
		* @access public
		* @return  The id of the updated webhook, or False if the update failed.
		*/
		function webhooksUpdateSingle($id, $params = array()) {
			return $this->put("/webhooks/{$id}", $params);
		}

		/**
		* Deletes an existing webhook.
		* @param int $id 	ID of webhook
		* @access public
		* @return  True if the webhook deleted successufully.
		*/
		function webhooksRemoveSingle($id) {
			return $this->delete("/webhooks/{$id}");
		}

		/**
		* Delete all webhooks registered for an account.
		* @access public
		* @return  True if the webhooks deleted successufully.
		*/
		function webhooksRemoveAll() {
			return $this->delete("/webhooks");
		}

		/**
		* Send a GET HTTP request
		* @param string $path		Optional post data
		* @param array $params		Optional query string parameters
		* @return array of information from API request
		* @access public
		*/
		protected function get($path, $params = array()) {
			$this->_params = array_merge($params, $this->_params);
			$url = $this->_constructUrl($path);
			return $this->_request($url);
		}

		/**
		* Send a POST HTTP request
		* @param string $path		Request path
		* @param array $postData	Optional post data
		* @return array of information from API request
		* @access public
		*/
		protected function post($path, $params = array()) {
			$url = $this->_constructUrl($path);
			$this->_postData = array_merge($params, $this->_postData);
			return $this->_request($url, "post");
		}

		/**
		* Send a PUT HTTP request
		* @param string $path		Request path
		* @param array $postData	Optional post data
		* @return array of information from API request
		* @access public
		*/
		protected function put($path, $postData = array()) {
			$url = $this->_constructUrl($path);
			$this->_postData = array_merge($postData, $this->_postData);
			return $this->_request($url, "put");
		}

		/**
		* Send a DELETE HTTP request
		* @param string $path		Request path
		* @param array $params		Optional query string parameters
		* @return array of information from API request
		* @access public
		*/
		protected function delete($path, $params = array()) {
			$this->_params = array_merge($params, $this->_params);
			$url = $this->_constructUrl($path);
			return $this->_request($url, "delete");
		}

		/**
		* Return array of available Category Endpoints
		* @return array of available category endpoints
		* @access public
		*/
		public function categories() {
			return array('fields', 'groups', 'mailings', 'members', 'response', 'searches', 'triggers', 'webhooks');
		}

		/**
		* Performs the actual HTTP request using cURL
		* @param string $url		Absolute URL to request
		* @param array $verb		Which type of HTTP Request to make
		* @return json encoded array of information from API request
		* @access private
		*/
		protected function _request($url, $verb = null) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_USERPWD, "{$this->_pub_key}:{$this->_priv_key}");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			if(isset($verb)) {
				if($verb == "post") {
					curl_setopt($ch, CURLOPT_POST, true);
				} else {
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($verb));
				}
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->_postData));
			}

			$data = curl_exec($ch);
			$info = curl_getinfo($ch);

			if($this->_debug) {
				print_r($data . "\n");
				print_r($info);
			}

			curl_close($ch);

			if($this->_validHttpResponseCode($info['http_code'])) {
				return $data;
			} else {
				throw new Emma_Invalid_Response_Exception(null, 0, $data, $info['http_code']);
			}
		}

		/**
		* Performs the actual HTTP request using cURL
		* @param string $path		Relative or absolute URI
		* @param array $params		Optional query string parameters
		* @return string $url
		* @access private
		*/
		protected function _constructUrl($path) {
			$url = $this->base_url . $this->_account_id;
			$url .= $path;
			$url .= (count($this->_params)) ? '?' . http_build_query($this->_params) : '';

			return $url;
		}

		/**
		* Validate HTTP response code
		* @param integer $code 		HTTP code
		* @return boolean
		* @access private
		*/
		protected function _validHttpResponseCode($code) {
			return (bool)preg_match('/^20[0-9]{1}/', $code);
		}
	}