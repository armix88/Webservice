<?php
	define('KEY' , '3756da348811a0f4288d559cad7c72b0');
	define('SUCCESS', '<success>1</success>');
	define('XML' , 'xml');
	define('JSON', 'json');
	define('METHODS', array ("GET" => INPUT_GET, "POST" => INPUT_POST, "COOKIE" => INPUT_COOKIE, "SERVER" => INPUT_SERVER, "ENV" => INPUT_ENV));

	require_once('config.inc.php');
	require_once('mail.php');
	
	function retrieveParameters($method, $filter)
	{	
		if($filter!= null AND count($filter) > 0)
			$parameters = ($tmp = filter_input_array(METHODS[$method],$filter)) ? $tmp : Array();
		else
			$parameters = ($tmp = filter_input_array(METHODS[$method])) ? $tmp : Array();
		
		return $parameters;
	}
	
	function createXML($message, $xslt_file) : String
	{
		$XML_String = "<?xml version=\"1.0\"?>\n";
		if ($xslt_file) $XML_String .= "<?xml-stylesheet href=\"$xslt_file\" type=\"text/xsl\" ?>";
		$XML_String .= "<result>\n" . $message . "</result>";
		return $XML_String;
	}
	
	function createJSON($XML_String) : String
	{
		$fileContents = str_replace(array("\n", "\r", "\t"), '', $XML_String);
        $fileContents = trim(str_replace('"', "'", $fileContents));
        $simpleXml = simplexml_load_string($fileContents);
		$JSON_string = JSON_encode($simpleXml);
		$JSON_string = str_replace("{", '{\n', $JSON_string);
		$JSON_string = str_replace("}", '}\n', $JSON_string);
		$JSON_string = str_replace(",", ',\n', $JSON_string);
		return $JSON_string;
	}
 
	function checkLoginCredential($username, $password) : String
	{
		$SQL_query = "SELECT email, password FROM user_anag WHERE email = ? AND authorized = 1";
		
		$results = executeQuery($SQL_query, array($username));
		
		if($results->num_rows > 0)
		{
			$row = $results->fetch_array(MYSQLI_ASSOC);
			if($row['email'] == $username AND $row['password'] == $password)
				$message = SUCCESS;
			else
				throw new Exception("Username or Password not matching the registered values");
		}
		else
			throw new Exception("Wrong Username or Password");
		
		return $message;
	}
	
	function convertResultsIntoXml($results) : String
	{
		$message = SUCCESS;
		$r = 1;
		// rows
		while ($row = $result->fetch_array(MYSQLI_ASSOC)) {    
			$message .= "\t<row>\n"; 
			$i = 0;
			$message .= "\t\t<row_id>" . $r . "</row_id>\n";
			// cells
			foreach ($row as $cell) {
				$cell = str_replace("&", "&amp;", $cell);
				$cell = str_replace("<", "&lt;", $cell);
				$cell = str_replace(">", "&gt;", $cell);
				$cell = str_replace("\"", "&quot;", $cell);
				$col_name = mysqli_fetch_field_direct($result, $i)->name;
				$message .= "\t\t<" . $col_name . ">" . $cell . "</" . $col_name . ">\n";
				$i++;
			}
			$message .= "\t</row>\n"; 
			$r++;
		}
		$result->free();
	}
	
	function executeQuery($query, array $parameters)
	{	
		$type = "";
				
		foreach($parameters as $i => &$val)
		{
			$type .= variableType($val);
			$ref_params[$i] = &$val; 
		}
		
		$bind_params = array_merge(array(&$type),$ref_params);
		
		$mysqli = new mysqli($GLOBALS['host'], $GLOBALS['user'], $GLOBALS['pass'], $GLOBALS['database']);
		if ($mysqli->connect_error) {
			throw new Exception('Connect Error (' . $mysqli->connect_errno .') '. $mysqli->connect_error);
		}
		else
		{
			$stmt = $mysqli->prepare($query);

			call_user_func_array(array(&$stmt, 'bind_param'), $bind_params); 
			
			if (!$stmt->execute())
				throw new Exception('Error executing query ' . $query);
			else
				if(strpos($query,"SELECT") === 0)
					$results = $stmt->get_result();
				else
					$results = $stmt->affected_rows;
			$stmt->close();
		}
		$mysqli->close();
		
		return $results;
	}
	
	function sendValidationKey($values)
	{
		if($values == null OR count($values) < 1)
			throw new Exception ('Wrong insert parameters');
		
		$username = $values['email'];
		if($username == null OR $username == "")
			throw new Exception ('Wrong username');
		
		$SQL_InsertUser = "INSERT INTO user_anag (<columns>) VALUES (<placeholder>)";
		$SQL_InsertKey  = "INSERT INTO authorization (username, generated_key) VALUES (? , ?)";
		
		$placeholder = join(',', array_fill(0, count($values), '?'));
		$columns = join(',',array_keys($values));
		
		$SQL_InsertUser = str_replace("<columns>", $columns, $SQL_InsertUser);
		$SQL_InsertUser = str_replace("<placeholder>", $placeholder, $SQL_InsertUser);

		$result = executeQuery($SQL_InsertUser, array_values($values));
		if($result < 1)
			throw Exception('Error registering user');
		
		$generated_key = sha1($username, FALSE);
		$values = array($username, $generated_key);
		
		$result = executeQuery($SQL_InsertKey, array_values($values));
		
		if($result < 1)
			throw Exception('Error inserting key values');
		
		$message = "Dear user,\r\nWe are sending this email to verify your email address.\r\nPlease click on the below link or copy and part it into your browser to verify your email and complete the registration.\r\n\r\n";
		$message .= "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . "?key=" . KEY . "&tag=auth&username=" . $username . "&verification_key=".  $generated_key;
		$message .= "\r\n\r\nThanks.\r\nRegards\r\nTeam " . $GLOBALS['database'];
		
		$subject = "Email verification " . $GLOBALS['database'];
		
		return sendEmail(array("to"=>$username, "subject"=>$subject, "message"=>$message));
	}
	
	function verifyUser($username, $key) : bool
	{
		$ret = false;
		
		if($username == null OR $username == "")
			throw new Exception ('Wrong username');
		
		$SQL_Select = "SELECT username, generated_key, Max(creation_date) FROM authorization WHERE username = ? GROUP BY  username, generated_key";
		
		$result = executeQuery($SQL_Select, array($username));
		$row = $result->fetch_array(MYSQLI_ASSOC);
		if($username == $row['username'] AND $key == $row['generated_key'])
			$ret= true;		
	
		return $ret;
	}
	
	function authorizeUser($username)
	{	
		if($username == null OR count($username) < 1)
			throw new Exception ('Wrong insert parameters');
		
		$SQL_UpdateUser = "UPDATE user_anag SET authorized = 1 WHERE email = ?";
		$SQL_DeleteKey = "DELETE FROM authorization WHERE username = ?";
						
		$result = executeQuery($SQL_UpdateUser , array($username));
		if($result < 1)
			throw Exception('Error authorizing user '. $values['email']);
		
		$result = executeQuery($SQL_DeleteKey , array($username));	
	}
	
	function encrypt($string)
	{
		return md5($string);
	}
	
	function variableType($variable)
	{
		$type = substr(gettype($variable),0,1);
		return $type;
	}
	
	function sendPicture($username, $picture_id, $recipients, $message)
	{
		$result = executeQuery("SELECT * FROM send_hist WHERE username = ? AND id = ?", array($username, $picture_id));
		
		if($result->num_rows>0)
		{
			$row = $result->fetch_array(MYSQLI_ASSOC);
			$filename = '/var/www/html/webservice/temp/photo.jpg';
			file_put_contents($filename, $row['photo']);
		}
		else 
			$filename = '';
			
		$parameters = array("to"=>$recipients,
							"cc"=>$username,
							"subject"=>"Invio Foto ".$row['photo_name'],
							"message"=>$message,
							"attachment"=>$filename
		);
		sendEmail($parameters);
		unlink($filename);
	}
	
	function saveDatas($username, $photo, $datas , $photo_name, $description)
	{
		$imgData = base64_encode($photo);
		$strMessage = "<img src= 'data:image/jpeg;base64,". $imgData . "' />";
		return $strMessage;		
	}
?>