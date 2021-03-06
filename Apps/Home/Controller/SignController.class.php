<?php
namespace Home\Controller;
use Think\Controller;

class SignController extends CommonController {
    const SUCCESS_URL = '/Home/Sign/index';

	public function index(){
		$result = D('Sign')->findAll(30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}

	public function save(){
		$id = I('get.id', 0, 'int');
		$data = D('Sign')->find($id);
		$this->assign('data', $data);
		$this->display();
	}

	public function save_up(){
		$sign_name = I('post.sign_name', '', 'string');
		$sign_pwd = I('post.sign_pwd', '', 'string');
		$status = I('post.status', 0, 'int');
		$back = I('post.back', '', 'string');
		$id = I('post.id', 0, 'int');

		if(empty($sign_name)){
			$this->error('签名名称不能为空');
		}

		if(empty($sign_pwd)){
			$this->error('签名密码不能为空');
		}

		$sign_info = D('Sign')->find($id);
		if(!empty($id) && empty($sign_info)){
			$this->error('签名不存在');
		}

		$is_upload = false;
		$save_path =  'Sign/';
		$sign_path = UPLOAD_DIR . $save_path .  $_FILES['sign_path']['name'];
		$old_sign_path = $sign_info['sign_path'];
		if(!empty($id)){
			if(!empty($_FILES['sign_path']['name']) && $old_sign_path != $sign_path){
				$is_upload = true;
			}
		}else{
			$is_upload = true;
		}

		if($is_upload){
			$sign_path = UPLOAD_DIR . $save_path .  $_FILES['sign_path']['name'];
			if($old_sign_path == $sign_path){
				$this->error('签名文件已存在，请编辑已有签名');
			}

			$upload = new \Think\Upload();
			$upload->autoSub = false;
			$upload->rootPath = ROOT_PATH . UPLOAD_DIR;
			$upload->savePath = $save_path;
			$upload->saveName = '';
			$info = $upload->uploadOne($_FILES['sign_path']);
			if(!$info){
				$this->error($upload->getError());
			}

		}else{
			$sign_path = '';
		}

		if(!empty($sign_path)){
			$check_sign_path = CHECK_SIGN_URL_MICROSOFT . '_' .time();
			copy(CHECK_SIGN_URL, $check_sign_path);
			$sign_cmd = sprintf("%s sign /f %s /p %s /t %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_URL, $check_sign_path);
			system($sign_cmd, $ret);
			if($ret !== 0) {
				unlink($sign_path);
				$this->error('签名错误(比如密码)');
			}
		}

		$status = D('Sign')->save($sign_name, $sign_path, $sign_pwd, $status, $back, $id);

		if($status){
			if(empty($id)){
				$this->success('添加成功', self::SUCCESS_URL);
			}else{
				$this->success('修改成功', self::SUCCESS_URL);
			}
		}else{
			if(empty($id)){
				$this->error('添加失败');
			}else{
				$this->error('修改失败');
			}
		}
	}

	public function delete(){
		$id = I('get.id', 0, 'int');
		if(empty($id)){
			$this->error('删除失败');
		}

		$status = D('Sign')->delete($id);

		if($status){
			$this->success('删除成功', self::SUCCESS_URL);
		}else{
			$this->error('删除失败');
		}
	}

	public function virus(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('扫毒结果不存在');
		}

		$info = D('Sign')->find($id);
		$list = D('Sign')->virus($id);
		$this->assign('list', $list);
		$this->assign('info', $info);
		$this->display();
	}

	public function virus_detail(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('扫毒结果不存在');
		}

		$list = D('Sign')->virus_detail($id);
		$this->assign('list', $list);
		$this->display();
	}

}