<?php
namespace Home\Model;
use Think\Model;

class ConfigModel extends Model{
	protected $tableName = 'config';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function findAll(){
		$list = M('config')->select();
		$config = array();
		foreach($list as $v){
			$config[$v['name']] = $v['value'];
		}
		if(!isset($config['min_sign_num'])){
			$config['min_sign_num'] = C('DEFAULT_MIN_SIGN_NUM');
		}

		return $config;
	}

	function save($min_sign_num, $email_list){
		$list = M('config')->select();
		$config = array();
		foreach($list as $v){
			$config[$v['name']] = $v['id'];
		}

		$min_sign_num_data = array(
			'name' => 'min_sign_num',
			'value' => $min_sign_num,
		);
		if(!isset($config['min_sign_num'])){
			$this->table->data($min_sign_num_data)->add();
		}else{
			$this->table->where("name='min_sign_num'")->data($min_sign_num_data)->save();
		}

		$email_list_data = array(
			'name' => 'email_list',
			'value' => $email_list,
		);
		if(!isset($config['email_list'])){
			$this->table->data($email_list_data)->add();
		}else{
			$this->table->where("name='email_list'")->data($email_list_data)->save();
		}
	}

}