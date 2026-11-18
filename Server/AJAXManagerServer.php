<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

class AJAXManagerServer {
	
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
	private $ASK;
	private $ERROBJ;
	private $ERRORS;
	private $af;
	private $av;
	
	public function __construct($DB_HOST, $DB_USER, $DB_PASSWORD,
								$DB_NAME, $DB_TABLE_NAME,
								$PAGE_NAME) {
		
		$this->DB_Credentials = array($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME);
		$this->dbTableName = $DB_TABLE_NAME;
		$this->idFieldName = "id";
		$this->af = "FIELD";
		$this->av = "VALUE";
		$this->tokenField = array($this->af=>"Token_Val", $this->av=>"");
		$this->tokenHashField = array($this->af=>"Token_Hash", $this->av=>"");
		$this->dateCreatedField = array($this->af=>"Date_Created", $this->av=>"");
		$this->initialisationTokenField = array($this->af=>"Initialisation_Token", $this->av=>"");
		$this->visitorDetailsField = array($this->af=>"Visitor_Details", $this->av=>"");
		$this->sessionField = array($this->af=>"Session_ID", $this->av=>session_id());
		$this->pageField = array($this->af=>"Auth_Page", $this->av=>$PAGE_NAME);
		$this->ASK = "";
		$this->ERROBJ = array();
		$this->ERRORS = false;
		$this->ISKStoreName = "AMSIS";
		$this->ASKStoreName = "AMSAS";
		$this->initialise_ISK_Store();
		$this->initialise_ASK_Store();
		
	}
	
	
	public function Initialise() {
		$visitorDetails = $this->get_Visitor_Vars();
		$ISKToken = $this->get_Random_Token();
		$newDateTime = $this->get_Index_Date();
		$ISKIndex = $this->sessionField[$this->av].":".
					$visitorDetails.":".
					$this->pageField[$this->av].":".
					$ISKToken;
		$NewISK = hash("sha256", $ISKIndex.":".
						 $newDateTime);
		$ISKIndex .= ":".$newDateTime;
		$_SESSION[$this->ISKStoreName][$ISKIndex] = $NewISK;
		return "{ 'CN' : '".base64_encode($this->sessionField[$this->av].$ISKToken)."', 'CV' : '".$NewISK."' }";
	}
	
	public function Authorise() {
		//return "bubblegum";
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
					if($ISKProperties["SessionID"] == $this->sessionField[$this->av]) {
						//The Session ID associated with the ISK is the same as the response call session.
						if($ISKProperties["VisitorDetails"] == $visitorDetails) {
							//**** Successful authentication ****
							$firstASKObj = $this->initialise_ASK($ISKProperties["SessionID"].":".
																 $ISKProperties["VisitorDetails"].":".
																 $ISKProperties["AuthPage"].":".
																 $ISKProperties["ISKToken"]);
							$firstASK = $firstASKObj['FirstASK'];
							$this->visitorDetailsField[$this->av] = $ISKProperties["VisitorDetails"];
							$this->pageField[$this->av] = $ISKProperties['AuthPage'];
							$this->initialisationTokenField[$this->av] = $ISKProperties["ISKToken"];
							$this->tokenField[$this->av] = $firstASKObj['ASKToken'];
							$this->tokenHashField[$this->av] = $firstASK;
							$this->dateCreatedField[$this->av] = $this->index_Date_To_MYSQL($firstASKObj['CreationDateTime']);
							unset($_SESSION[$this->ISKStoreName][$ISKIndex]);
							setcookie($authCookieName, "", time()-3600);
							//return "oggedy boogedy";
							//return $this->DB_Credentials[0].$this->DB_Credentials[1].$this->DB_Credentials[2].$this->DB_Credentials[3];
							$this->create_Auth_Record();
						}
						else {
							$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
							//The Client that requested the ISK isn't the same as the one responding with the ISK.
						}
					}
					else {
						$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
						//Session IDs for creation of ISK and response of ISK don't match.  Fake request, different client used to respond.
					}
				}
				else {
					$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
					//The sent ISK can't be found in the Store.  The ISK is a fake request.
				}
			}
			else {
				$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
				//No auth cookie found corresponding to the cookie name sent.
			}
		}
		else {
			$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
			//Cookie name of auth cookie not sent.  Can't get ISK from cookie as ISK cookie name is not known.
		}
		$this->ASK = $firstASK;
		return $this->Response("");
	}
	
	public function Authenticate() {
		$Authenticated = false;
		$newASK = "";
		if(isset($_REQUEST['k'])) {
			$ASK = $_REQUEST['k'];
		$visitorDetails = $this->get_Visitor_Vars();
		$sendingASKIndex = array_search($ASK, $_SESSION[$this->ASKStoreName]);
		$ASKProperties = $sendingASKIndex ? $this->ASK_Index_To_Obj($sendingASKIndex) : null;
		if(isset($ASKProperties)) {
			//ISK has a valid entry in the store.
			if($ASKProperties["SessionID"] == $this->sessionField[$this->av]) {
				//The Session ID associated with the ISK is the same as the response call session.
				if($ASKProperties["VisitorDetails"] == $visitorDetails) {
					//The Client that requested the ISK has the same agent properties as the Client that responded with the ISK.
					//**** Successful authentication ****
					$ASKProperties['ASKToken'] = $this->get_Random_Token();
					$ASKProperties['CreationDateTime'] = $this->get_Index_Date();
					$ASKIndex = implode(":", $ASKProperties);
					$Authenticated = true;
					$newASK = md5($ASKIndex);
					$this->pageField[$this->av] = $ASKProperties['AuthPage'];
					$this->initialisationTokenField[$this->av] = $ASKProperties['ISKToken'];
					$this->visitorDetailsField[$this->av] = $ASKProperties['VisitorDetails'];
					$this->tokenField[$this->av] = $ASKProperties['ASKToken'];
					$this->tokenHashField[$this->av] = $newASK;
					$this->dateCreatedField[$this->av] = $this->index_Date_To_MYSQL($ASKProperties['CreationDateTime']);
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
					else {
						$this->setError("Authentication failure. - ", 100);
						//The database connection failed.
					}
				}
				else {
					$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
					//The Client that requested the ISK isn't the same as the one responding with the ISK.
				}
			}
			else {
				$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
				//Session IDs for creation of ISK and response of ISK don't match.  Fake request, different client used to respond.
			}
		}
		else {
			$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
			//The sent ISK can't be found in the Store.  The ISK is a fake request.
		}
		}
		else {
			$this->setError("Authentication failure.  Access to this resource has been blocked", 100);
			//Old ASK not sent.
		}
		$this->ASK = $newASK;
		return $Authenticated;
	}
	
	public function Response($DATA_TO_SEND) {
		$returnObj = $this->construct_Return_Object();
		$ERROR = array();
		$ERRORS = $this->ERRORS;
		if($ERRORS) {
			$DATA_TO_SEND = null;
			$ERROR = $this->ERROBJ;
		}
		$returnObj->ERROR = $ERROR;
		$returnObj->ERRORS = $ERRORS;
		$returnObj->DATA->TYPE = gettype($DATA_TO_SEND);
		$returnObj->DATA->VALUE = $DATA_TO_SEND;
		$returnObj->TOKEN = $this->ASK;
		return json_encode($returnObj);
	}
	
	public function setError($ERR, $ERRCODE) {
		$this->ERRORS = true;
		$this->ERROBJ[count($this->ERROBJ)] = (object) array( "LAYER"=>$ERRCODE, "MESSAGE"=>$ERR );
	}
	
	private function construct_Return_Object() {
		$returnObj = (object) array("ERROR"=> array(),
									"ERRORS"=>false,
								    "DATA"=> (object) array("TYPE"=>"Null", "VALUE"=>null),
								    "TOKEN"=>"");
		return $returnObj;
	}
	
	public function Query($METHOD) {
		if($METHOD == "GET" OR $METHOD == "") {
			return $_REQUEST['q'];
		}
		elseif($METHOD == "POST") {
			return $_POST['q'];
		}
	}
	
	private function sanitise_Return_Data($DATA) {
		if(gettype($DATA) == "object") {
			$DATA = json_encode($DATA);
		}
		return $DATA;
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
		if(!isset($_SESSION[$this->ISKStoreName])) {
			$_SESSION[$this->ISKStoreName] = array();
		}
	}
	
	private function initialise_ASK_Store() {
		if(!isset($_SESSION[$this->ASKStoreName])) {
			$_SESSION[$this->ASKStoreName] = array();
		}
		
	}
	
	private function get_Visitor_Vars() {
		$visitorDetails = "";
		$visitorDetails .= (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "No Details");
		$visitorDetails .= (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "No Details");
		$visitorDetails .= (isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : "No Details");
		return $visitorDetails;
	}

	private function check_For_Auth_Record() {
		$total = 0;
		$FIELDS_TO_INCLUDE = array("id");
		$WHERE_CLAUSE = array();
		$WHERE_CLAUSE[0] = $this->sessionField;
		$WHERE_CLAUSE[1] = $this->visitorDetailsField;
		$WHERE_CLAUSE[2] = $this->pageField;
		$WHERE_CLAUSE[3] = $this->initialisationTokenField;
		$WHERE_CLAUSE[4] = $this->dateCreatedField;
		$query = $this->build_Select_Query("SELECT", $FIELDS_TO_INCLUDE, $WHERE_CLAUSE, null);
		//return $query;
		$dbObj = $this->create_DB_Link();
		if(!isset($dbObj[1])) {
			$stmt = $dbObj[0]->prepare($query);
			$stmt->bind_param("sssis", $this->sessionField[$this->av], 
									   $this->visitorDetailsField[$this->av], 
									   $this->pageField[$this->av], 
									   $this->initialisationTokenField[$this->av], 
									   $this->dateCreatedField[$this->av]);
			$stmt->execute();
			$result = $stmt->get_result();
			$this->close_DB_Link($dbObj);
			$total = $result->num_rows;
		}
		return $total;
	}
	
	
	
	private function create_Auth_Record() {
		$record = $this->check_For_Auth_Record();
		//return $record;
		if($record == 0) {
			$query = $this->build_Insert_Query();
			$dbObj = $this->create_DB_Link();
			if(!isset($dbObj[1])) {
				$result = $dbObj[0]->query($query);
				$this->close_DB_Link($dbObj);
			}
		}
	}
	
	private function build_Select_Query($SELECT, $FIELDS_TO_INCLUDE, $WHERE_CLAUSE, $TABLE) {
		//return $WHERE_CLAUSE;
		$tbl = isset($TABLE) ? $TABLE : $this->dbTableName;
		$query = $SELECT." ".implode(", ", $FIELDS_TO_INCLUDE)." From ".$tbl;
		$where = array_fill(0, count($WHERE_CLAUSE), null);
		$w = 0;
		$sm = "";
		while($w < count($WHERE_CLAUSE)) {
			$where[$w] .= $WHERE_CLAUSE[$w][$this->af]." = ?";
			$w ++;
		}
		$query .= isset($where) ? " where ".implode(" AND ", $where) : "";
		return $query;
	}
	
	private function build_Insert_Query() {
		$query = "Insert Into ".$this->dbTableName." (".$this->idFieldName.", ".
												   $this->sessionField[$this->af].", ".
												   $this->initialisationTokenField[$this->af].", ".
												   $this->pageField[$this->af].", ".
												   $this->tokenField[$this->af].", ".
												   $this->tokenHashField[$this->af].", ".
												   $this->dateCreatedField[$this->af].", ".
												   $this->visitorDetailsField[$this->af].") ".
												   "VALUES (0, '".
												   $this->sessionField[$this->av]."', ".
												   $this->initialisationTokenField[$this->av].", '".
												   $this->pageField[$this->av]."', ".
												   $this->tokenField[$this->av].", '".
												   $this->tokenHashField[$this->av]."', '".
												   $this->dateCreatedField[$this->av]."', '".
												   $this->visitorDetailsField[$this->av]."')";
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
			$sm = is_numeric($WHERE_CLAUSE[$w][$this->av]) ? "" : "'";
			$where[$w] = $WHERE_CLAUSE[$w][$this->af]." = ".$sm.$WHERE_CLAUSE[$w][$this->av].$sm;
			$w ++;
		}
		$set = array();
		$s = 0;
		while($s < count($FIELDS_TO_UPDATE)) {
			$sm = is_numeric($FIELDS_TO_UPDATE[$s][$this->av]) ? "" : "'";
			$set[$s] = $FIELDS_TO_UPDATE[$s][$this->af]." = ".$sm.$FIELDS_TO_UPDATE[$s][$this->av].$sm;
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
		//return "just before new db connection";
		$dbLinkError = "";
		try {
			$dbLink = new mysqli($this->DB_Credentials[0], $this->DB_Credentials[1], $this->DB_Credentials[2], $this->DB_Credentials[3]);
			} catch (mysqli_sql_exception $e) {
			error_log($e->getMessage());
			$dbLinkError = getMessage();
			exit("Database connection failed.".$e->getMessage());
		}
		return array($dbLink, $dbLinkError);
	}
	
}
?>