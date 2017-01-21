<?php

# phprpc載入
include('php/phprpc_server.php');

# 數據庫信息初始化
define('MYSQL_HOST', '127.0.0.1');
define('USERNAME', 'root');
define('PASSWORD', '123456');
define('DATABASE', 'Gamedb');

$conn = mysql_connect(MYSQL_HOST, USERNAME, PASSWORD);

mysql_select_db(DATABASE, $conn);

# 函數
# 注冊帳號
function reg($user, $password, $email, $sex)
{
	if(!eregi("^[a-z0-9]{4,26}$", $user))
	{
		return '<center>帳號只能由小寫字母和數字組成...長度在5-25的範圍之內...</center>';
	}
	if(!eregi("^[a-zA-Z0-9]{4,26}$", $password))
	{
		return '<center>密碼只能由字母和數字組成...長度在5-25的範圍之內...</center>';
	}
	if(!eregi("^[a-zA-Z0-9]+[-_.a-zA-Z0-9]+@[-_a-zA-Z0-9]+(\.[-_a-zA-Z0-9]+)+$", $email))
	{
		return '<center>Email內容不符合規則...</center>';
	}
	if($sex != 'F' && $sex != "M")
	{
		return '<center>性別錯誤...</center>';
	}
	global $conn;
	$sql = "SELECT `userid` FROM `login` WHERE `userid` = '" . $user . "';";
	$result = mysql_query($sql, $conn);
	if(mysql_num_rows($result))
	{
		return '<center>帳號重複...</center>';
	}
	$sql = "INSERT INTO `login` (`userid`, `user_pass`, `sex`, `email`) VALUES ('" . $user . "', '" . $password . "', '" . $sex . "', '" . $email . "');";
	if(mysql_query($sql, $conn))
	{
		return '<center>帳號注冊成功!</center>';
	}
	else
	{
		return '<center>帳號注冊失敗...</center>';
	}
}

# 修改密碼
function repass($user, $oldpassword, $password)
{
	if(!eregi("^[a-z0-9]{4,26}$", $user))
	{
		return '<center>帳號不符合規則...</center>';
	}
	if(!eregi("^[a-zA-Z0-9]{4,26}$", $oldpassword))
	{
		return '<center>舊密碼不符合規則...</center>';
	}

	if(!eregi("^[a-zA-Z0-9]{4,26}$", $password))
	{
		return '<center>新密碼只能由字母和數字組成...長度在5-25的範圍之內...</center>';
	}
	global $conn;
	$sql = "SELECT * FROM `login` WHERE `userid` = '" . $user . "'AND `user_pass` = '" . $oldpassword . "'";
	$result = mysql_query($sql, $conn);
	if(!mysql_num_rows($result))
	{
		return '<center>帳號或密碼錯誤...</center>';
	}
	$sql = "UPDATE `login` SET `user_pass` = '" . $password . "' WHERE `userid` = '" . $user . "';";
	if(mysql_query($sql, $conn))
	{
		return '<center>密碼修改成功!</center>';
	}
	else
	{
		return '<center>密碼修改失敗...</center>';
	}
}

# 登陸
function login($user, $password)
{
	if(!eregi("^[a-z0-9]{4,26}$", $user))
	{
		return '<center>帳號不符合規則...</center>';
	}
	if(!eregi("^[a-zA-Z0-9]{4,26}$", $password))
	{
		return '<center>密碼不符合規則...</center>';
	}
	global $conn;
	$sql = "SELECT * FROM `login` WHERE `userid` = '" . $user . "'AND `user_pass` = '" . $password . "'";
	$result = mysql_query($sql, $conn);
	if(!mysql_num_rows($result))
	{
		return '<center>帳號或密碼錯誤...</center>';
	}
	else
	{
		$token = sha1(microtime());
		$token = substr($token, 0, 10);
		@session_start();
		$_SESSION['login'] = $token;
		$_SESSION['user'] = $user;
		$json = "{token: '" . $token . "', table: [";
		$sql = "SELECT `char_num`, `name` FROM `char` WHERE `account_id` = (SELECT `account_id` FROM `login` WHERE `userid` = '" . $user . "') ORDER BY `char_num` ASC";
		$result = mysql_query($sql, $conn);
		if(!mysql_num_rows($result))
		{
			return '<center>帳號未創建過人物...</center>';
		}
		while($i = mysql_fetch_row($result))
		{
			$json .= "{id: " . $i[0] . ", name: '" . $i[1] . "'},";
		}
		$json = substr($json, 0, strlen($json)-1);
		$json .= "]}";
		return $json;
	}
}

# 卡號處理
function help($token, $char_num, $unequip, $warp)
{
	if($token != $_SESSION['login'])
	{
		@session_start();
		unset($_SESSION['login']);
		return '<center>登入錯誤...</center>'.$token;
	}
	$echo = "<center>";
	global $conn;
	if($unequip)
	{
		$sql = "SELECT `char_id` FROM `char` WHERE `account_id` = (SELECT `account_id` FROM `login` WHERE `userid` = '" . $_SESSION['user'] . "') AND `char_num` = " . $char_num . ";";
		$result = mysql_query($sql, $conn);
		$i = mysql_fetch_row($result);
		$charid = $i[0];
		$sql = "UPDATE `inventory` SET `equip` = '0' WHERE `char_id` = " . $charid . ";";
		if(mysql_query($sql, $conn))
		{
			$echo .= '裝備卸下完成!</ br></ br>';
		}
		else
		{
			$echo .= '裝備卸下失敗...</ br></ br>';
		}
	}
	if($warp)
	{
		$sql = "UPDATE `ragnarok`.`char` SET `save_map` = 'prontera', `save_x` = '116', `save_y` = '72' WHERE `account_id` = (SELECT `account_id` FROM `login` WHERE `userid` = '" . $_SESSION['user'] . "') AND `char_num` = " . $char_num . ";";
		$result = mysql_query($sql, $conn);
		if(mysql_query($sql, $conn))
		{
			$echo .= '傳送完成!';
		}
		else
		{
			$echo .= '傳送失敗...';
		}
	}
	$echo .= "</center>";
	return $echo;
}

$server = new PHPRPC_Server();
$server->add(array('reg', 'repass', 'login', 'help'));
$server->start();