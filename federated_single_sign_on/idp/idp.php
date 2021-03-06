<?php
	//session_name("2620368ghwahw90w");
	//session_set_cookie_params(0, '/', '.virtual.vm');
	//ini_set('session.cookie_domain', '.virtual.vm');
	session_start();
	session_unset();
	/* Request shared key from Kerberos Server Simulation*/
	$kerberos_port = 50002; // Get the port for the SAML-AAI/Kerbero service.
	$kerberos_ip = "192.168.1.59"; // Get the IP address for the target host.

	/* Create a TCP/IP socket. */
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if ($socket === false)
	{
		echo "socket_create() failed: reason: ".socket_strerror(socket_last_error())."<br>";
	}
	else
	{
		//echo "Creating Socket...OK.<br>";
	}

	//echo "Attempting to connect to '$kerberos_ip' on port '$kerberos_port'...<br>";

	$connect = socket_connect($socket, $kerberos_ip, $kerberos_port);
	if ($connect === false)
	{
		echo "socket_connect() failed.<br>Reason: ".socket_strerror(socket_last_error($socket))."<br>";
	}
	else
	{
		//echo "OK.<br>";
	}

	$request = "req_sk;";
	$request .= "idp".';';
	$request .= "home.virtual.vm".';';
	$request .= "192.168.1.59".';';

	$reply = '';

	//echo "Sending Kerberos shared key request...";
	socket_write($socket, $request, strlen($request));
	//echo "OK.\n";

	//echo "<br>Recieving response...";
	$shared_key = socket_read($socket, 1024);
	//echo $shared_key;
	$_SESSION["idp_shared_key"] = $shared_key;
	
	socket_close($socket);
	//echo "OK.<br><br>";


	/***************************************************************/

	//	assume that end-user enter the in-correct username and password.
	$auth_fail = false;
	$count = 0;
	
	/* initiate DOMDocument for accessing xml file */
	$doc = new DOMDocument();
	$doc->load( 'userdatabase.xml' );

	/* get xml element that with the tag:"users". */
	$users = $doc->getElementsByTagName( "users" );
	foreach( $users as $user )
	{
		//	get username
		$usernames = $user->getElementsByTagName( "username" );
		$username = $usernames->item(0)->nodeValue;

		//	get password
		$passwords = $user->getElementsByTagName( "password" );
		$password = $passwords->item(0)->nodeValue;

		//	get role
		$roles = $user->getElementsByTagName( "role" );
		$role = $roles->item(0)->nodeValue;
		
		//	assert that the end-user is authenticated
		//	this will be used later to generate assertion
		$authenticateds = $user->getElementsByTagName("authenticated");
		$authenticated = $authenticateds->item(0)->nodeValue;

		//	indicate the domain of the end-user resides in
		$domains = $user->getElementsByTagName( "domain" );
		$domain = $domains->item(0)->nodeValue;
	}
	
	if (isset($_POST["submit"]))
	{
		//	loop through all the users
		for ($i=0; $i<($usernames->length); $i++)
		{
			//	if found a matching pair of username and password
			if ($_POST["user"] == $usernames->item($i)->nodeValue && 
                $_POST["pass"] == $passwords->item($i)->nodeValue)
			{
				$_SESSION["username"] = $_POST["user"];
				$_SESSION["idp_domain"] = $domains->item($i)->nodeValue;
				$_SESSION["role"] = $roles->item($i)->nodeValue;

				//	$_SESSION["authenticated"] asserts that the end-user is authenticated
				//	this will be used later to generate assertion
				$_SESSION["authenticated"] = true;

				if (isset($_GET["url"])) {
					$url = $_GET["url"];
				}
				else
				{
					$url = "http://home.virtual.vm/idp/auth_success.php";
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
					header("Location: " . $url);
				}
			}	//	end of if statment for authentication
		}	//	end of $i for loop
		$auth_fail = true;
	}
?>
<html>
<head>
	<title>Identity Provider Simulation (home.virtual.vm)</title>
</head>
<body>
	<img src="../img/dit_crest_2010.gif"/>
    <img src="../img/dit_logo_2010.gif"/><br><br>
	<center>
		Identity Provider Simulation (home.virtual.vm) <br><br>
		<?php session_start();
			if($auth_fail)
				echo "<center><font color=\"red\" weight=\"strong\">
                        Wrong username or password</font></center>";
		?>

		<form method="post">
			<table>
				<tr>
					<td>Username:</td>
					<td><input type="text" name="user" /></td>
				</tr>
				<tr>
					<td>Password:</td>
					<td><input type="password" name="pass" /></td>
				</tr>
				<tr>
                    <td>
                    <input type="submit" name="submit" value="Login" />
                    <input type="reset" name="reset" value="Reset"/>
                    </td>
				</tr>
			</table>
		</form>
        
        <form action="auth_key.php" method="post" enctype="multipart/form-data">
            <label for="key">Kerberos TGT:</label>
            <input type="file" name="tgt" id="tgt" /> 
            <input type="submit" name="submit" value="Submit" />
        </form>
	</center>
	<br><br>
	<a href="../">Back to Index</a><br><br>
</body>
</html>
