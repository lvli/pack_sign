<?php
namespace Home\Controller;
use Think\Controller;

class LogController extends CommonController {
	public function index(){
		$result = D('Log')->findAll(30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}


}