<?php
namespace Home\Controller;
use Think\Controller;

class ListCronController extends CommonController {
	public function index(){
		$result = D('ListCron')->findAll(30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}

	public function virus(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('扫毒结果不存在');
		}

		$info = D('ListCron')->find($id);
		$list = D('ListCron')->virus($id);
		$this->assign('list', $list);
		$this->assign('info', $info);
		$this->display();
	}

	public function virus_detail(){
		$id = I('get.id', 0, 'int');

		if(empty($id)){
			$this->error('扫毒结果不存在');
		}

		$list = D('ListCron')->virus_detail($id);
		$this->assign('list', $list);
		$this->display();
	}


}