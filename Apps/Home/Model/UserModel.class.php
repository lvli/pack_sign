<?php
namespace Home\Model;
use Think\Model;

class UserModel extends Model{
	protected $tableName = 'admin';
	protected  $table;

	public function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	public function is_login(){
		return session('?user_id') && session('?user_name');
	}

	public function login($user, $password){
		$password = $this->encrypt($password);
		$user_info = $this->table->where("name='{$user}' AND password='{$password}'")->find();
		if(intval($user_info['id']) > 0){
			session('admin_id', intval($user_info['id']));
			session('admin_name', $user_info['name']);
			return true;
		}else{
			return false;
		}
	}

	public function logout(){
		session('admin_id', NULL);
		session('admin_name', NULL);
		return true;
	}

	public function oldPasssword($password){
		$id = intval(session('admin_id'));
		$password = $this->encrypt($password);
		$user_id = (int)$this->table->where("id={$id} AND password='{$password}'")->getField('id');
		if($user_id > 0){
			return true;
		}else{
			return false;
		}
	}

	public function edit($password){
		$id = intval(session('admin_id'));
		$password = $this->encrypt($password);
		$data = array(
				'password' => $password,
				'addtime' => time(),
		);
		$status = $this->table->where("id={$id}")->data($data)->save();
		if($status){
			$this->logout();
		}
		return $status;
	}

	public function oldUser($user){
		$user_id = (int)$this->table->where("name='{$user}'")->getField('id');
		if($user_id > 0){
			return true;
		}else{
			return false;
		}
	}

	public function register($user, $password){
		$data = array(
			'name' => $user,
			'password' => $this->encrypt($password),
			'status' => 0,
			'addtime' => time(),
		);
		return $this->table->data($data)->add();
	}

	private function encrypt($password){
		return  md5($password);
	}
}