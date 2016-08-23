<?php
	error_reporting(E_ALL);
	ini_set('memory_limit', '2048M');
	
	require_once('functions.php');
	
	header("Content-type: text/xml");
	
	try{
		$result = executeQuery("SELECT * FROM send_hist WHERE username = ?", array("bembe83@libero.it"));
		
		if($result->num_rows>0)
		{
			$row = $result->fetch_array(MYSQLI_ASSOC);
		//	$filename = saveTempFile($row['photo'],"photo.jpg");
			$filename = '/var/www/html/webservice/temp/photo.jpg';
			file_put_contents($filename, $row['photo']);
			$photo = 
			$url = "http://www.bembe.tk/webservice/api.php?key=".KEY."&tag=savedatas&useranme=bembe83@libero.it&photo=".base64_encode($row['photo']);
		}
		else 
			$filename = '';
			
// 		$parameters = array("to"=>"armix88@gmail.com",
// 							"cc"=>"bembe83@libero.it",
// 							"subject"=>"Test Invio Foto",
// 							"message"=>"Questo foto arriva dal DB!! :-D",
// 							"attachment"=>$filename
// 		);
// 		echo(sendEmail($parameters));
// 		unlink($filename);
		
		header("Location:" . $url);
		
	}catch (Exception $e)
	{
		echo("\t<error>Exception: " .  $e->getMessage() . "</error>\n");
		error_log($e->getMessage());
	}	
	
?>