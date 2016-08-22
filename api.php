<?php
	error_reporting(E_ALL);
	ini_set('memory_limit', '2048M');
	
	require_once('functions.php');
	
	header("Content-type: text/xml");
	
	$methods = $_SERVER['REQUEST_METHOD'];
	$parameters = retrieveParameters($methods, null);
	
	if(array_key_exists('out',$parameters) AND $parameters['out'] != "")
		$output = $parameters['out'];
	else
		$output = XML;
	
	if(array_key_exists('key',$parameters) AND $parameters['key'] != "")
		$key = $parameters['key'];
	else
		$key = "";
	
	try
	{				
		if($output != XML AND $output != JSON)
			throw new Exception('Wrong output type ' . $output . '. Allowed only ' . XML . ' and ' . JSON);
				
		if($key == null OR $key != KEY)
		{
			throw new Exception('Invalid key '. $key);
		}
		else
		{		
			if(array_key_exists('tag',$parameters))
				$tag = $parameters['tag'];
			else
				$tag = "";
				
			if(array_key_exists('username',$parameters))
				$username = $parameters['username'];
			else if(array_key_exists('email',$parameters))
				$username = $parameters['email'];
			else
				$username = NULL;
				
			if(array_key_exists('password',$parameters))
				$password = $parameters['password'];
			else
				$password = NULL;
				
			if(array_key_exists('fullname',$parameters))
				$fullname =$parameters['fullname'];
			else if(array_key_exists('full_name',$parameters))
				$fullname =$parameters['full_name'];
			else
				$fullname = "";
			
			if(array_key_exists('verification_key',$parameters))
				$verif_key = $parameters['verification_key'];
			else
				$verif_key = NULL;
			
			if (array_key_exists ('recipient', $parameters ))
				$recipient = $parameters ['recipient'];
			else
				$recipient = NULL;
			
			switch($tag){
				case"login":
					if(isset($username) AND issset($password))
						$message = checkLoginCredential($username, $password);
					else
						throw new Exception('username/email or password key missed.');
					break;
				case "register":
					$auth = 0;
					if(isset($username) AND issset($password))					
						$message = sendValidationKey(array("email"=>$username,"full_name"=>$fullname,"password"=>$password, "authorized"=>$auth));
					else
						throw new Exception('username/email or password key missed.');
					break;
				case "auth":
					if(isset($username) AND issset($verif_key))
					if(verifyUser($username,$verif_key))
					{
						authorizeUser($username);
						$message = SUCCESS;
					}
					else
						throw new Exception('Wrong key provided or username never registered');
					break;
				case "request":
					$SQL_query = $parameters['query'];
					$param1 = $parameters['param1'];
					$results = executeQuery($SQL_query, array($param1));
					$message = convertResultsIntoXml($results);
					break;
				case "send":
					if(!isset($username) OR !isset($recipient))
						throw new Exception('Sender or Recipient key missed.');
					sendPicture($username, 1, $recipient,"<HTML>Questa invece la sta inviando il <b>WebService</b><br />Best Regards<br />" . $username . "</HTML>");
					$message = SUCCESS;
					break;
				default:
					throw new Exception('No valid tag value');
			}			
		}
	}catch (Exception $e)
	{
		$message = "\t<error>Exception: " .  $e->getMessage() . "</error>\n";
		error_log($e->getMessage());
	}	
	
	$XML_String = createXML($message,"");
	
	if($output == XML)	
		echo ($XML_String);
	else
		echo(createJSON($XML_String));
		
?> 