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

		$is_upload = false;
		if(!empty($id)){
			$old_sign_path = D('Sign')->find($id);
			if(!empty($sign_path) && $old_sign_path != $sign_path){
				$is_upload = true;
			}
		}else{
			$is_upload = true;
		}

		if($is_upload){
			$save_path =  sprintf("%s/%s/%s/", date('Y'), date('m'),  date('d'));
			$upload = new \Think\Upload();
			$upload->autoSub = false;
			$upload->rootPath = UPLOAD_DIR;
			$upload->savePath = $save_path;
			$upload->saveName = '';
			$info = $upload->uploadOne($_FILES['sign_path']);
			if(!$info){
				$this->error($upload->getError());
			}
			$sign_path = UPLOAD_DIR . $save_path .  $_FILES['sign_path']['name'];
		}else{
			$sign_path = '';
		}

		$status = D('Sign')->save($sign_path, $sign_pwd, $status, $back, $id);

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


}