<?php
namespace Home\Model;
use Think\Model;

class UserModel extends Model{
	protected $tableName = 'admin';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function is_login(){
		return session('?user_id') && session('?user_name');
	}

	function login($user, $password){
		$user_info = $this->table->where("name='{$user}' AND password='{$password}'")->getField('id');
		if(intval($user_info['id']) > 0){
			session('admin_id', intval($user_info['id']));
			session('admin_name', $user_info['name']);
			return true;
		}else{
			return false;
		}
	}

	function logout(){
		session('admin_id', NULL);
		session('admin_name', NULL);
	}
}