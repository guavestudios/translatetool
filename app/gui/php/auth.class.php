<?php

class auth{
	private static function ensureSessionStarted(){
		if (session_status() !== PHP_SESSION_ACTIVE) {
			session_start();
		}
	}

	private static function validate($username, $passwd){
		$users = config::get('users');
		foreach($users as $user){
			if($user['username'] == $username and $user['passwd'] == $passwd){
				return $user['roles'];
			}
		}
		return array();
	}
	
	public static function login($username, $passwd){
		self::ensureSessionStarted();
		$roles = self::validate($username, $passwd);
		if(!empty($roles)){
			$_SESSION['auth']['loggedIn'] = true;
			$_SESSION['auth']['username'] = $username;
			$_SESSION['auth']['roles'] = $roles;
			return true;
		}else{
			return false;
		}
	}
	
	public static function logout(){
		self::ensureSessionStarted();
		unset($_SESSION['auth']);
	}
	
	public static function ed(){
		self::ensureSessionStarted();
		if(isset($_SESSION['auth']['loggedIn']) and $_SESSION['auth']['loggedIn'] == true){
			return true;
		}else{
			return false;
		}
	}
	
	public static function has($role){
		self::ensureSessionStarted();
		if (!isset($_SESSION['auth']['roles']) || !is_array($_SESSION['auth']['roles'])) {
			return false;
		}
		return in_array($role, $_SESSION['auth']['roles'], true);
	}
	
}

?>