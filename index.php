<html>
 <head>
  <title>PHP Test</title>
 </head>
 <body>
 <?php 
	error_reporting(E_ALL);
 
	include("config.inc.php");
	
	$SQL_query = "SELECT * FROM user_anag WHERE email IS NOT NULL";
	
	$mysqli = new mysqli($host, $user, $pass, $database);
	if ($mysqli->connect_error) {
		die('Connect Error (' . $mysqli->connect_errno . ') '. $mysqli->connect_error);
	}
	$result = $mysqli->query($SQL_query);
	if ($result->connect_error) {
		die('Error executing query ' .$SQL_query .'<br /> '. $mysqli->connect_errno . ') '. $mysqli->connect_error);
	}
	
	// we produce XML
	header("Content-type: text/xml");
	$XML = "<?xml version=\"1.0\"?>\n";
	if ($xslt_file) $XML .= "<?xml-stylesheet href=\"$xslt_file\" type=\"text/xsl\" ?>";
	
	// root node
	$XML .= "<result>\n";
	// rows
	while ($row = $result->fetch_array(MYSQLI_ASSOC)) {    
	$XML .= "\t<row>\n"; 
	$i = 0;
	// cells
	foreach ($row as $cell) {
		// Escaping illegal characters - not tested actually ;)
		$cell = str_replace("&", "&amp;", $cell);
		$cell = str_replace("<", "&lt;", $cell);
		$cell = str_replace(">", "&gt;", $cell);
		$cell = str_replace("\"", "&quot;", $cell);
		$col_name = mysql_field_name($result,$i);
		// creates the "<tag>contents</tag>" representing the column
		$XML .= "\t\t<" . $col_name . ">" . $cell . "</" . $col_name . ">\n";
		$i++;
	}
	$XML .= "\t</row>\n"; 
	}
	$XML .= "</result>\n";
	
	// output the whole XML string
	echo $XML;

 ?> 
 </body>
</html>