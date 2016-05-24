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

}