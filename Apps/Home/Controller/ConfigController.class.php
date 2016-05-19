<?php
namespace Home\Controller;
use Think\Controller;

class ConfigController extends CommonController {

	public function index(){
		$data = D('Config')->findAll();
		$this->assign('data', $data);
		$this->display();
	}

	public function save_up(){
		$min_sign_num = I('post.min_sign_num', '', 'string');
		$email_list = I('post.email_list', '', 'string');

		$email_list = trim($email_list, ',');
		if(!empty($email_list))	foreach(explode(',', $email_list) as $v){
			if(!filter_var($v, FILTER_VALIDATE_EMAIL)){
				$this->error('email格式不合法');
			}
		}

		D('Config')->save($min_sign_num, $email_list);

		$this->success('修改成功', '/Home/Config/index');
	}
}