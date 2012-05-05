<?php
require_once('page_base.php');

class CLoginPage extends CPageBase
{
	public function CLoginPage()
	{
		parent::CPageBase('Login', 0);
	}

	protected function DisplayPre()
	{
		if(isset($_GET['logout']))
		{
			session_destroy();
			setcookie('rclogs', '', -1);
			$_SESSION['user_level']=0;
		}

		if($_POST['stage']==1 && $_SESSION['user_level']==0){
			$this->m_bLoggedIn = $this->AuthenticateUser();
			if($this->m_bLoggedIn)
			{
				//Now set a cookie that the user is logged in, may want to add a checkbox
				//that says "Remember me".
				setcookie('rclogs', $_SESSION['user_id'], time()+3600*24*365);
			}
		}
	}

	protected function DisplayPost()
	{
	?>
	<script type="text/javascript" src="js/functions.js"></script>
	<script type="text/javascript" src="js/md5.js"></script>
	<script type="text/javascript" src="js/sha1.js"></script>

	<script type="text/javascript">
	function onSubmit()
	{
		encryptPassword();
		document.LoginForm.submit();
	}

	function encryptPassword()
	{
		var uname = document.LoginForm.uname;
		var pword = document.LoginForm.pword;
		var pwd_hash = document.LoginForm.pwd_hash;
		var pwd_salt = document.LoginForm.pwd_salt;

		pwd_hash.value = hex_sha1(hex_md5(pword.value) + pwd_salt.value);
		pword.value = "HashedPassword2345";
	}


	function highlightLinks() {
		var linkList = document.getElementById("about_copyright").getElementsByTagName("p");
		for (i = 0; i < linkList.length; i++) {
			linkList[i].className = "";
		}
	}
	</script>
	<?php
	}

	protected function DisplayContent()
	{
		print("<h1>Login</h1>\n");
		$nStage = $_POST['stage'];

		if(isset($_GET['logout']))
		{
			//session_destroy();
			//$_SESSION['user_level']=0;
			print("<p>Successfully logged out.</p>\n");
			$nStage=0;
		}

		if($_SESSION['user_level']>0 && $nStage!=1)
		{
			print("<p>Already logged in. ");
			print('<a href='.CreateHREF(PAGE_LOGIN, 'logout').'>Log out</a>');
			print("or return <a href=".CreateHREF(PAGE_HOME).">home</a>.</p>\n");
			return;
		}

		//There are basically two login pages, one for typing in the username
		//and password (stage 0) and the other authenticates (stage 1). If
		//any stage failes, it goes back to 0.
		if($nStage==0)
		{
			$this->DisplayLoginForm();
		}
		else if($nStage==1)
		{
			if($this->m_bLoggedIn)
			{
				print("<p>Successfully logged in. Return to the <a href=".CreateHREF(PAGE_HOME).">main page</a>.");
			}
			else
			{
				$this->ShowWarning('Username or password could not be authenticated.');
				$this->DisplayLoginForm();
			}
		}

	}

	private $m_bLoggedIn;

	private function DisplayLoginForm()
	{
		if(!isset($_SESSION['login_key']))
		{
			$code = 'abcdefghijklmnopqrstuvwxyz12345678';
			$key='';
			srand(time());
			for($i=0; $i<64; $i++)
			{
				$key = $key.$code[rand()%(strlen($code))];
			}
			$_SESSION['login_key'] = $key;
		}
		?>
		<form action=<?php print CreateHREF(PAGE_LOGIN)?> method="post" name="LoginForm">
		<p>Username: <input style="width:30%" type="text" name="uname"/></p>
		<p>Password: <input style="width:30%" type="password" name="pword"/></p>
		<input type="hidden" name="pwd_hash" id="pwd_hash"/>
		<input type="hidden" name="pwd_salt" id="pwd_salt" value=<?php printf('"%s"', $_SESSION['login_key'])?>/>
		<input type="hidden" name="stage" value="1"/>
		<!--<p><input class="button" type="submit" value="Login"/></p>-->
		<p><input type="button" onclick="javascript:onSubmit()" value="Login"/></p>
		</form>
		<?php
	}

	private function AuthenticateUser()
	{
		$bRes = false;

		$user_name = $_POST['uname'];
		$pwd =       $_POST['pwd_hash'];
		$pwd_salt =  $_POST['pwd_salt'];
		//printf("The username: %s has password: %s (%s), %s\n", $user_name, $pwd, $_POST['pword'], $pwd_salt);
		//The password now needs to be authenticated:

		$strQ = 'select id, txtPassword from tblUser where txtUserName = "'.$user_name.'"';
		$res = $this->DoQuery($strQ);
		if(!$res)
		{
			return false;
		}
		//Should only have gotten one row.
		if($res->num_rows==1)
			$row = $res->fetch_assoc();

		$res->free();

		if(!isset($row))
			return false;

		//Encrypt the retrieved password using the salt:
		$ep=sha1($row['txtPassword'].$pwd_salt);
		//printf("<p>Submitted: %s, Retrieved: %s</p>\n", $pwd, $ep);
		if(!($ep==$pwd))
				return false;

		//So we authenticated, now run the login.


		$strQ = 'select id, txtUserName, nAccessLevel, txtAlias from tblUser where id="'.$row['id'].'"';
		$res = $this->DoQuery($strQ);
		if(true == $res)
		{
			//Should only have gotten one row.
			if($res->num_rows == 1)
			{
				$row = $res->fetch_assoc();
				$_SESSION['user'] = $row['txtUserName'];
				$_SESSION['user_alias'] = $row['txtAlias'];
				$_SESSION['user_id'] = $row['id'];
				$_SESSION['user_level'] = $row['nAccessLevel'];
				$bRes=true;
			}
			$res->free();
		}

		//Update the IP address where login occured.

		$qry = 'update tblUser set txtLastIP="'.$_SERVER['REMOTE_ADDR'].'" where id='.$row['id'];
		$this->DoQuery($qry);

		return $bRes;
	}
}
?>
