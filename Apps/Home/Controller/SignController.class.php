<?php
namespace Home\Controller;
use Think\Controller;

class SignController extends Controller {
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
		$sign_path = I('post.sign_path', 0, 'string');
		$sign_pwd = I('post.sign_pwd', 0, 'string');
		$status = I('post.status', 0, 'int');
		$back = I('post.back', 0, 'string');
		$id = I('post.id', 0, 'int');

        if(empty($sign_path)){
			$this->error('签名文件路径不能为空');
		}
		if(empty($sign_pwd)){
			$this->error('签名密码不能为空');
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