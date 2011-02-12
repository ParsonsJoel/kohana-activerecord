<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Arm Auth User Model.
 *
 * @package    Arm Auth
 * @author     Devi Mandiri <devi.mandiri@gmail.com>
 * @copyright  (c) 2011 Devi Mandiri
 * @license    MIT
 */
class User extends Arm {

	static $has_many = array(
		array('user_tokens'),
		array('roles_users'),		
		array('roles', 'through' => 'roles_users')		
	);
	
	static $validates_presence_of = array(
		array('username'),
		array('email'),
		array('password')
	);
	
	static $validates_size_of = array(
		array('username', 'within' => array(4,32)),
		array('email', 'within' => array(4,127)),
		array('password', 'minimum' => 6)		
	);
	
	static $validates_format_of = array(
		array('username', 'with' => '/^[-\pL\pN_.]++$/uD'),
		array('email', 'with' => '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD')
	);
		
	static $validates_uniqueness_of = array(
		array('username'),
		array('email')
	);	

	static $after_validation = array('filters');
	
	public function filters()
	{
		if ( ! $this->salt)
			// Generate a random 22 character salt
			$this->salt = Text::random('alnum', 22);
		
		$this->password	= Bonafide::instance()->hash($this->password, $this->salt);
	}	
	
	/**
	 * Get unique key based on value.
	 * 
	 * @param mixed $value	Key value for match
	 * @return string		Unique key name to attempt to match against
	 */
	public static function unique_key($value)
	{
		if (Valid::email($value))
		{
			return 'email';
		} 
		elseif (is_string($value))
		{
			return 'username';
		}
		return 'id';
	}
	
	/**
	 * Update password.
	 * 
	 * @param string	Current/Old password
	 * @param string	New password
	 * @param mixed 	Key value for match
	 * @param string 	New salt
	 * @return boolean
	 */
	public function update_password($old, $new, $key, $new_salt = NULL)
	{
		if ($old === NULL OR $new === NULL)
			return FALSE;		
		
		$user = User::find(array(static::unique_key($key) => $key));
		
		if ( ! $user)
		{
			return FALSE;
		}
		
		if ( ! Bonafide::instance()->check($old, $user->password, $user->salt))
			return FALSE;
		
		if ( ! $new_salt)
			// Generate new salt
			$new_salt = Text::random('alnum', 22);
			
		return $user->update_attribute('password', Bonafide::instance()->hash($new, $new_salt));
	}

	/**
	 * Check for unique key existence.
	 * 
	 * @param mixed	Key value for match
	 * @return boolean
	 */
	public function unique_key_exists($value)
	{
		return User::exists(array(static::unique_key($value) => $value));
	}

	/**
	 * Complete the login for a user by incrementing the logins and saving login timestamp.
	 *
	 * @param   object   user model object
	 * @return  void
	 */
	public function complete_login()
	{
		if (! $this->loaded())
		{
			return;
		}
		
		$this->update_attribute('logins', $this->logins + 1);
		$this->update_attribute('last_login', time());		
	}

	/**
	 * Check if user has a particular role.
	 * 
	 * @param mixed $role 	Role to test for, can be Role object, string role name of integer role id
	 * @return bool			Whether or not the user has the requested role
	 */
	public function has_role($role)
	{
		if ($role instanceof Role)
		{
			$key = 'id';
			$val = $role->id;
		}
		elseif (is_string($role))
		{
			$key = 'name';
			$val = $role;
		}
		else
		{
			$key = 'id';
			$val = (int) $role;
		}
		
		foreach ($this->roles as $user_role)
		{
			if ($user_role->{$key} === $val)
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}

}
