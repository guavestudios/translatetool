<?php

@session_start();

class auth{

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
		unset($_SESSION['auth']);
	}
	
	public static function ed(){
		if(isset($_SESSION['auth']['loggedIn']) and $_SESSION['auth']['loggedIn'] == true){
			return true;
		}else{
			return false;
		}
	}
	
	public static function has($role){
		return in_array($role, $_SESSION['auth']['roles']);
	}
	
}

?>