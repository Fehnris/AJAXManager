<?php

class AJAXAuthObject {
	
	private $DB_Credentials;
	private $DB_Error;
	private $dbTableName;
	private $whereClause;
	private $sessionField;
	private $pageField;
	private $tokenField;
	private $tokenHashField;
	private $initialisationTokenField;
	private $dateCreatedField;
	private $idFieldName;
	private $visitorDetails;
	private $visitorDetailsField;
	private $ISKStoreName;
	private $ASKStoreName;
	
	public function __construct($DB_HOST, $DB_USER, $DB_PASSWORD,
								$DB_NAME, $DB_TABLE_NAME,
								$PAGE_NAME) {
		
		$this->DB_Credentials = array($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
		$this->dbTableName = $DB_TABLE_NAME;
		$this->idFieldName = "id";
		$this->tokenField = array("FIELD"=>"Token_Val", "VALUE"=>"");
		$this->tokenHashField = array("FIELD"=>"Token_Hash", "VALUE"=>"");
		$this->dateCreatedField = array("FIELD"=>"Date_Created", "VALUE"=>"");
		$this->initialisationTokenField = array("FIELD"=>"Initialisation_Token", "VALUE"=>"");
		$this->visitorDetailsField = array("FIELD"=>"Visitor_Details", "VALUE"=>"");
		$this->sessionField = array("FIELD"=>"Session_ID", "VALUE"=>session_id());
		$this->pageField = array("FIELD"=>"Auth_Page", "VALUE"=>$PAGE_NAME);
		
	}
	
	
	public function create_ISK() {
		$visitorDetails = $this->get_Visitor_Vars();
		$ISKToken = $this->get_Random_Token();
		$newDateTime = $this->get_Index_Date();
		$ISKIndex = $this->sessionField['VALUE'].":".
					$visitorDetails.":".
					$this->pageField['VALUE'].":".
					$ISKToken;
		$NewISK = hash("sha256", $ISKIndex.":".
						 $nowDateTime);
		$ISKIndex .= ":".$nowDateTime;
		$_SESSION[$this->ISKStoreName][$ISKIndex] = $NewISK;
		return array("AuthCookieName"=>base64_encode($this->sessionField['VALUE'].$ISKToken),
					 "AuthCookieValue"=>$NewISK);
	}
	
	public function authorise_ISK() {
		$firstASK = "";
		if(isset($_GET['t'])) {
			$authCookieName = $_GET['t'];
			//Cookie name of ISK auth cookie is known.
			if(isset($_COOKIE[$authCookieName])) {
				$visitorDetails = $this->get_Visitor_Vars();
				//Cookie contains valid base64 value.
				$ISK = $_COOKIE[$authCookieName];
				$ISKIndex = array_search($ISK, $_SESSION[$this->ISKStoreName]);
				$ISKProperties = $ISKIndex ? $this->ISK_Index_To_Obj($ISKIndex) : null;
				if(isset($ISKProperties)) {
					//ISK has a valid entry in the store.
					if($ISKProperties["SessionID"] == $this->sessionField['VALUE']) {
						//The Session ID associated with the ISK is the same as the response call session.
						if($ISKProperties["VisitorDetails"] == $visitorDetails) {
							//**** Successful authentication ****
							$firstASKObj = $this->initialise_ASK($ISKProperties["SessionID"].":".
																 $ISKProperties["VisitorDetails"].":".
																 $ISKProperties["AuthPage"].":".
																 $ISKProperties["ISKToken"]);
							$firstASK = $firstASKObj['FirstASK'];
							$this->visitorDetailsField['VALUE'] = $ISKProperties["VisitorDetails"];
							$this->pageField['VALUE'] = $ISKProperties['AuthPage'];
							$this->initialisationTokenField['VALUE'] = $ISKProperties["ISKToken"];
							$this->tokenField['VALUE'] = $firstASKObj['ASKToken'];
							$this->tokenHashField['VALUE'] = $firstASK;
							$this->dateCreatedField['VALUE'] = $this->index_Date_To_MYSQL($firstASKObj['CreationDateTime']);
							unset($_SESSION[$this->ISKStoreName][$ISKIndex]);
							setcookie($authCookieName, "", time()-3600);
							$this->create_Auth_Record();
						}
						else { //The Client that requested the ISK isn't the same as the one responding with the ISK.
						}
					}
					else { //Session IDs for creation of ISK and response of ISK don't match.  Fake request, different client used to respond.
					}
				}
				else { //The sent ISK can't be found in the Store.  The ISK is a fake request.
				}
			}
			else { //No auth cookie found corresponding to the cookie name sent.
			}
		}
		else { //Cookie name of auth cookie not sent.  Can't get ISK from cookie as ISK cookie name is not known.
		}
		//$firstASK = "SESSION ARRAY".$_SESSION[$this->ISKStoreName];
		return $firstASK;
	}
	
	public function authorise_ASK() {
		$newASK = "";
		if(isset($_REQUEST['k'])) {
			$ASK = $_REQUEST['k'];
		$visitorDetails = $this->get_Visitor_Vars();
		$sendingASKIndex = array_search($ASK, $_SESSION[$this->ASKStoreName]);
		$ASKProperties = $sendingASKIndex ? $this->ASK_Index_To_Obj($sendingASKIndex) : null;
		if(isset($ASKProperties)) {
			//ISK has a valid entry in the store.
			if($ASKProperties["SessionID"] == $this->sessionField['VALUE']) {
				//The Session ID associated with the ISK is the same as the response call session.
				if($ASKProperties["VisitorDetails"] == $visitorDetails) {
					//The Client that requested the ISK has the same agent properties as the Client that responded with the ISK.
					//**** Successful authentication ****
					$ASKProperties['ASKToken'] = $this->get_Random_Token();
					$ASKProperties['CreationDateTime'] = $this->get_Index_Date();
					$ASKIndex = implode(":", $ASKProperties);
					$newASK = md5($ASKIndex);
					$this->pageField['VALUE'] = $ASKProperties['AuthPage'];
					$this->initialisationTokenField['VALUE'] = $ASKProperties['ISKToken'];
					$this->visitorDetailsField['VALUE'] = $ASKProperties['VisitorDetails'];
					$this->tokenField['VALUE'] = $ASKProperties['ASKToken'];
					$this->tokenHashField['VALUE'] = $newASK;
					$this->dateCreatedField['VALUE'] = $this->index_Date_To_MYSQL($ASKProperties['CreationDateTime']);
					$_SESSION[$this->ASKStoreName][$ASKIndex] = $newASK;
					unset($_SESSION[$this->ASKStoreName][$sendingASKIndex]);
					$set = array($this->tokenField, $this->tokenHashField, $this->dateCreatedField);
					$where = array($this->sessionField, $this->visitorDetailsField, $this->pageField, $this->initialisationTokenField);
					$query = $this->build_Update_Query($set, $where);
					$dbObj = $this->create_DB_Link();
		
					if(!isset($dbObj[1])) {
						$result = $dbObj[0]->query($query);
						$this->close_DB_Link($dbObj);
					}
				}
				else {
					//The Client that requested the ISK isn't the same as the one responding with the ISK.
				}
			}
			else {
				//Session IDs for creation of ISK and response of ISK don't match.  Fake request, different client used to respond.
			}
		}
		else {
			//The sent ISK can't be found in the Store.  The ISK is a fake request.
		}
		}
		else {
			//Old ASK not sent.
		}
		return $newASK;
	}
	
	private function get_Index_Date() {
		$dObj = new dateTime();
		$newDateTime = $dObj->format("Y%m%d#H^i^s");
		return $newDateTime;
	}
	
	private function index_Date_To_MYSQL($indexDate) {
		$dateMYSQL = str_replace("^", ":", str_replace("#", " ", str_replace("%", "-", $indexDate)));
		return $dateMYSQL;
	}
	
	private function get_Random_Token() {
		return mt_rand(1000000, 2000000);
	}
	
	private function initialise_ASK($ISKIndex) {
		$newDateTime = $this->get_Index_Date();
		$ASKToken = $this->get_Random_Token();
		$ASKIndex = $ISKIndex.":".$ASKToken.":".$newDateTime;
		$firstASK = md5($ASKIndex);
		$_SESSION[$this->ASKStoreName][$ASKIndex] = $firstASK;
		return array("ASKToken"=>$ASKToken, "CreationDateTime"=>$newDateTime, "FirstASK"=>$firstASK);
	}
	
	private function ASK_Index_To_Obj($ASKIndex) {
		$a = explode(":", $ASKIndex);
		return array("SessionID"=>$a[0], "VisitorDetails"=>$a[1], "AuthPage"=>$a[2], "ISKToken"=>$a[3], "ASKToken"=>$a[4], "CreationDateTime"=>$a[5]);
	}
	
	private function ISK_Index_To_Obj($ISKIndex) {
		$a = explode(":", $ISKIndex);
		return array("SessionID"=>$a[0], "VisitorDetails"=>$a[1], "AuthPage"=>$a[2], "ISKToken"=>$a[3]);
	}
	
	private function initialise_ISK_Store() {
		if(!is_array($_SESSION[$this->ISKStoreName])) {
			$_SESSION[$this->ISKStoreName] = array();
		}
	}
	
	private function initialise_ASK_Store() {
		if(!is_array($_SESSION[$this->ASKStoreName])) {
			$_SESSION[$this->ASKStoreName] = array();
		}
	}
	
	private function get_Visitor_Vars() {
		$visitorDetails .= (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "No Details");
		$visitorDetails .= (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "No Details");
		$visitorDetails .= (isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : "No Details");
		return $visitorDetails;
	}

	private function check_For_Auth_Record() {
		$FIELDS_TO_INCLUDE = array("id");
		$WHERE_CLAUSE = array();
		$WHERE_CLAUSE[0] = array($this->sessionField);
		$WHERE_CLAUSE[1] = array($this->visitorDetailsField);
		$WHERE_CLAUSE[2] = array($this->pageField);
		$WHERE_CLAUSE[3] = array($this->initialisationTokenField);
		$WHERE_CLAUSE[4] = array($this->dateCreatedField);
		$query = $this->build_Select_Query($FIELDS_TO_INCLUDE, $WHERE_CLAUSE);
		$dbObj = $this->create_DB_Link();
		
		$total = 0;
		
		if(!isset($dbObj[1])) {
			if ($result = $dbObj[0]->query($query)) {
				$total = $result->num_rows;
			}
			$this->close_DB_Link($dbObj);
		}
		return $total;
	}
	
	private function create_Auth_Record() {
		$record = $this->check_For_Auth_Record();
		if($record == 0) {
			$query = $this->build_Insert_Query();
			$dbObj = $this->create_DB_Link();
			if(!isset($dbObj[1])) {
				$result = $dbObj[0]->query($query);
				$this->close_DB_Link($dbObj);
			}
		}
	}
	
	private function build_Select_Query($FIELDS_TO_INCLUDE, $WHERE_CLAUSE) {
		$query = "Select ".implode(", ", $FIELDS_TO_INCLUDE)." From ".$this->dbTableName;
		$where = array();
		$w = 0;
		while($w < count($WHERE_CLAUSE)) {
			$sm = is_numeric($WHERE_CLAUSE[$w]['VALUE']) ? "" : "'";
			$where[$w] = $WHERE_CLAUSE[$w]['FIELD']." = ".$sm.$WHERE_CLAUSE[$w]['VALUE'].$sm;
			$w ++;
		}
		$query .= isset($where) ? " where ".implode(" AND ", $where) : "";
		return $query;
	}
	
	private function build_Insert_Query() {
		$query = "Insert Into ".$this->dbTableName." (".$this->idFieldName.", ".
												   $this->sessionField['FIELD'].", ".
												   $this->initialisationTokenField['FIELD'].", ".
												   $this->pageField['FIELD'].", ".
												   $this->tokenField['FIELD'].", ".
												   $this->tokenHashField['FIELD'].", ".
												   $this->dateCreatedField['FIELD'].", ".
												   $this->visitorDetailsField['FIELD'].") ".
												   "VALUES (0, '".
												   $this->sessionField['VALUE']."', ".
												   $this->initialisationTokenField['VALUE'].", '".
												   $this->pageField['VALUE']."', ".
												   $this->tokenField['VALUE'].", '".
												   $this->tokenHashField['VALUE']."', '".
												   $this->dateCreatedField['VALUE']."', '".
												   $this->visitorDetailsField['VALUE']."')";
		//echo $query;
		return $query;
	}
	
	private function build_Where_Clause() {
		
	}
	
	private function build_Update_Query($FIELDS_TO_UPDATE, $WHERE_CLAUSE) {
		$query = "Update ".$this->dbTableName;
		$where = array();
		$w = 0;
		while($w < count($WHERE_CLAUSE)) {
			$sm = is_numeric($WHERE_CLAUSE[$w]['VALUE']) ? "" : "'";
			$where[$w] = $WHERE_CLAUSE[$w]['FIELD']." = ".$sm.$WHERE_CLAUSE[$w]['VALUE'].$sm;
			$w ++;
		}
		$set = array();
		$s = 0;
		while($s < count($FIELDS_TO_UPDATE)) {
			$sm = is_numeric($FIELDS_TO_UPDATE[$s]['VALUE']) ? "" : "'";
			$set[$s] = $FIELDS_TO_UPDATE[$s]['FIELD']." = ".$sm.$FIELDS_TO_UPDATE[$s]['VALUE'].$sm;
			$s ++;
		}
		$query .= isset($set) ? " set ".implode(", ", $set) : "";
		$query .= isset($where) ? " where ".implode(" AND ", $where) : "";
		//echo $query;
		return $query;
	}
	
	private function close_DB_Link($dbObj) {
		$dbObj[0]->close();
	}
	
	private function create_DB_Link() {
		$dbLink = new mysqli($this->DB_Credentials[0], $this->DB_Credentials[1], $this->DB_Credentials[2], $this->DB_Credentials[3]);
		if (mysqli_connect_errno()) { $dbLinkError = "Connect failed: ".mysqli_connect_error(); }
		return array($dbLink, $dbLinkError);
	}
	
}
?>