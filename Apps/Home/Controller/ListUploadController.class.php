<?php
namespace Home\Controller;
use Think\Controller;

class ListUploadController extends CommonController {
	public function index(){
		$result = D('ListUpload')->findAll(30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}

	public function virus(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('扫毒结果不存在');
		}

		$info = D('ListUpload')->find($id);
		$list = D('ListUpload')->virus($id);
		$this->assign('list', $list);
		$this->assign('info', $info);
		$this->display();
	}

	public function virus_detail(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('扫毒结果不存在');
		}

		$list = D('ListUpload')->virus_detail($id);
		$this->assign('list', $list);
		$this->display();
	}

	public function jump_step(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('记录不存在');
		}

		$status = D('ListUpload')->jump_step($id);
		if($status){
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}

	public function confirm_sign(){
		$id = I('get.id', 0, 'int');
		if(empty($id)){
			$this->error('记录不存在');
		}

		$list = D('ListUpload')->find($id);
		$sign_list = M('sign_pool')->where('status = 0')->order('id ASC')->select();

		if(empty($list)){
			$this->error('记录不存在');
		}
		$list['file_name'] = basename($list['file_path']);
		$this->assign('list', $list);
		$this->assign('sign_list', $sign_list);
		$this->assign('id', $id);
		$this->display();
	}

	public function confirm_sign_up(){
		$p = I('post.p', 1, 'int');
		$id = I('post.id', 0, 'int');
		$sign = I('post.sign', '', 'string');

		if(empty($id) || empty($sign)){
			$this->error('参数错误');
		}

		$status = D('ListUpload')->confirm_sign($id, $sign);
		if($status){
			$this->success('修改成功', '/Home/ListUpload/index/p/' . $p);
		}else{
			$this->error('修改失败');
		}
	}

}