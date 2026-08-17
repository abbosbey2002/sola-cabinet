<?
/********************************************************************************
*								oracle9.php										*
*								--------------------							*
*		begin					: OCT 31 2005									*
*		copyright				: (C) 2005 Perfectum Mobile						*
*		email					: wwwazad@yahoo.com								*
*		Written by				: Azad Atadjanov								*
********************************************************************************/

if ( !defined('ARM_IN') )
	die("Hacking attempt");

class Oracle_db
{
//Variables
    private $user = '';
    private $password = '';
    private $database = '';
    private $persistency = false;
    private $error = '';
// Database connection handle
	private $conn = NULL;
	private $curs = NULL;
//Query variables
    private $result = '';
    public $allResults = array();
    public $allCurResults = array();

//Constructor
	function __construct($user, $password, $database, $persistency = false)	{
		$this->user = $user;
		$this->password = $password;
		$this->database = $database;
		$this->persistency = $persistency;
	}

	function __destruct()	{
		return oci_close($this->conn);
	}

	function open()	
	{
		$this->TrackErrors(1);
		$php_errormsg='';
		@$this->conn = ($this->persistency) ? oci_pconnect($this->user, $this->password, $this->database) : oci_connect($this->user, $this->password, $this->database);
		$this->error = @$php_errormsg;
		$this->TrackErrors(0);
		return $php_errormsg;
	}

	function parse($sql = '', $auto_execute = true)	{
		//echo $sql,'<br>';
		$this->result = oci_parse($this->conn, $sql);
		if ($auto_execute)
		{
			$this->execute($this->result);
			
			//if (!empty($this->error)) echo "MyERR:". $this->error . "; MySQL:". $sql,'<br>';
		}
		return ($this->result != false);
	}

	function execute()	{
		$this->TrackErrors(1);
		@oci_execute($this->result);
		$this->error = @$php_errormsg;
		$this->TrackErrors(0);
	}

    function executeDefault() {
        $this->TrackErrors(1);
        @oci_execute ( $this->result, OCI_DEFAULT);
        $this->error = @$php_errormsg;
        $this->TrackErrors(0);
    }

	function executeCurs()	{
		$this->TrackErrors(1);
		$ret = oci_execute($this->curs);
		$this->error = @$php_errormsg;
		$this->TrackErrors(0);
		return $ret;
	}

	function cursor()	{
		$this->curs = oci_new_cursor($this->conn);
	}

	//using this method make's fetchObject, fetchArray, fetchRow useless
	function fetchAll()	{
		return oci_fetch_all($this->result, $this->allResults, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
	}

	function fetchAllCur()	{
		return oci_fetch_all($this->curs, $this->allCurResults, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
	}

	function getAllResults()	{
		return $this->allResults;
	}

	function fetchAssoc()	{
		return oci_fetch_assoc($this->result);
	}

	function fetchAssocCur()	{
		return oci_fetch_assoc($this->curs);
	}

	function fetchArray()	{
		return oci_fetch_array($this->result);
	}

	function fetchObject()	{
		return oci_fetch_object($this->result);
	}

	function fetch()	{
		return oci_fetch($this->result);
	}

	function numRows() {
		return oci_num_rows($this->result);
	}

	function error() {
		return oci_error($this->conn);
	}

	function getError()	{
		return $this->error;
	}

	function commit(){
		return oci_commit($this->conn);
	}

    function rollback() {
        return oci_rollback($this->conn);
    }

	function freeStatment()	{
		return oci_free_statement($this->result);
	}

	function bindByName($ph_name, $var, $maxlength = null)	{
		return oci_bind_by_name($this->result, $ph_name, $var, $maxlength);
	}

	function bindByNameCur($ph_name, $maxlength = -1)	{
		return oci_bind_by_name($this->result, $ph_name, $this->curs, $maxlength, OCI_B_CURSOR);
	}

	function TrackErrors($val)	{
		ini_set('track_errors', $val);
	}
	
	function getBlob() {
		
	}
}
?>