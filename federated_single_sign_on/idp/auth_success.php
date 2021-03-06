<html>
<head>
	<title>Authenticated successfully.</title>
</head>
<body>
<img src="../img/dit_crest_2010.gif"/>
<img src="../img/dit_logo_2010.gif"/><br><br>
<center>

<?php 
	//session_name("2620368ghwahw90w");
	//session_set_cookie_params(0, '/', '.virtual.vm');
	//ini_set('session.cookie_domain', '.virtual.vm');
	session_start();

	if($_SESSION["authenticated"])
	{
		$username = $_SESSION["username"];
		$role = $_SESSION["role"];
		$issue_date = date('H:i, jS F Y');
		$issue_domain = $_SESSION["idp_domain"];
		$value = $username.';'.$role.';'.$issue_domain.';'.'authenticated';
		
		setcookie("user", $value, time()+3600, "/", "virtual.vm");
		//print_r($_COOKIE);
		echo "User \"$username\" has authenticated successfully<br>";
		echo "Assertion message's details are as following:<br><br>";
		echo"
		<table>
		<tr>
		<td>Username:</td><td>$username</td>
		</tr>
		<tr>
		<td>Issue Date:</td><td>$issue_date</td>
		</tr>
		<tr>
		<td>Issue Domain:</td><td>$issue_domain</td>
		</tr>
		<tr>
		<td>Role:</td><td>$role</td>
		</tr>
		</table>
		<br><br><br>
		";
		
		try {
			$url = "http://foreign.virtual.vm/sp/sp.php";

			/* create a dom document with encoding utf8 */
			$doc = new DOMDocument('1.0', 'UTF-8');
			$doc->formatOutput = true;
			$xml->preserveWhiteSpace = false;

			/* create the root element of the xml tree */
			$xmlRoot = $doc->createElement("assertion");
		
			/* append it to the document created */
			$doc->appendChild($xmlRoot);

			/* Add the username section */
			$username_xml = $doc->createElement("Username");
			$username_xml->appendChild(
				$doc->createTextNode($username));
			$xmlRoot->appendChild($username_xml);
		
			/* Add the IssueDate section */
			$issue_date_xml = $doc->createElement("IssueDate");
			$issue_date_xml->appendChild(
				$doc->createTextNode($issue_date));
			$xmlRoot->appendChild($issue_date_xml);
		
			/* Add the IssueDomain section */
			$issue_domain_xml = $doc->createElement("IssueDomain");
			$issue_domain_xml->appendChild(
				$doc->createTextNode($issue_domain));
			$xmlRoot->appendChild($issue_domain_xml);
		
			/* Add the Role section */
			$role_xml = $doc->createElement("Role");
			$role_xml->appendChild(
				$doc->createTextNode($role));
			$xmlRoot->appendChild($role_xml);

			/* save the xml variables into a single variable */
			$outXML = $doc->saveXML();

			/* Display XML in it's original format. */
			/*
			echo "Display XML:
			<br>
			<!--Start of XML variables-->
			<pre>
			".htmlentities($outXML)."
			</pre>
			<!--End of XML variables-->";
			*/
			
			/* Send XML assertion to SP with curl and POST */
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_MUTE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('xml' => htmlentities($outXML)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);

			if(curl_errno($ch))
			{
				echo "<br>Error<br>";
				print curl_error($ch);
			}
			else
			{
				//echo $output."<br>";
				curl_close($ch);
				//echo "<font color=\"red\">The displayed assertion message has been sent to $url.<br>";
			}
			
		}
		catch(Exception $e)
		{
			$message = $e->getMessage();
			echo $message;
		}
		
		if (isset($_GET["url"])) {
			$url = $_GET["url"];
		}
		else
		{
			$url = "http://foreign.virtual.vm/sp/sp.php";
		}
		
		if (!isset($_COOKIE[session_name()]))
		{
			if (strstr($url, "?"))
			{
				header("Location: " . $url .
				"&" . session_name() . "=" . session_id());
			}
			else
			{
				header("Location: " . $url .
				"?" . session_name() . "=" . session_id());
			}
		}
		else
		{
		?>
		<br><br>Click Ok to continue.<br>
		<input id="submit" type="submit" name="continue" value="Ok" ONCLICK="window.location.href='http://foreign.virtual.vm/sp/sp.php'"/>
		
		<?php session_start();
			//header("Refresh: 10; URL=$url");
		}
		
	}
	else
	{
		/* in case someone try to access this page 
		 * without authentication first.
		 * This line will re-direct the page to idp.php
		 */
		header("Location: http://home.virtual.vm/idp/idp.php");
	}
?>

</center>
<br><br>
<a href="idp.php">Back to Log in</a><br><br>
<a href="../index.php" name="logout">Back to Index</a><br><br>
</body>
</html>
