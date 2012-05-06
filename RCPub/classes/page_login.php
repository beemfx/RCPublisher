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
			RCSession_Disconnect();
		}

		if($_POST['stage']==1 && RCSession_GetUserProp('user_level')==0)
		{
			$user_name = $_POST['uname'];
			$pwd =       $_POST['pwd_hash'];
			$pwd_salt =  $_POST['pwd_salt'];
		
			$this->m_bLoggedIn = RCSession_Connect($user_name, $pwd, $pwd_salt, true);
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
			print("<p>Successfully logged out.</p>\n");
			$nStage=0;
		}

		if(RCSession_GetUserProp('user_level')>0 && $nStage!=1)
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
		?>
		<form action=<?php print CreateHREF(PAGE_LOGIN)?> method="post" name="LoginForm">
		<p>Username: <input style="width:30%" type="text" name="uname"/></p>
		<p>Password: <input style="width:30%" type="password" name="pword"/></p>
		<input type="hidden" name="pwd_hash" id="pwd_hash"/>
		<input type="hidden" name="pwd_salt" id="pwd_salt" value=<?php printf('"%s"', RCSession_GetUserProp('login_key'))?>/>
		<input type="hidden" name="stage" value="1"/>
		<!--<p><input class="button" type="submit" value="Login"/></p>-->
		<p><input type="button" onclick="javascript:onSubmit()" value="Login"/></p>
		</form>
		<?php
	}
}
?>
