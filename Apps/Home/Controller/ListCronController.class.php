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


}