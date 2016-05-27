<?php
namespace Home\Controller;
use Think\Controller;

class ActionController extends CommonController {
	public function index(){
		$begin_time = I('request.begin_time', '', 'string');
		$end_time = I('request.end_time', '', 'string');

		$result = D('Action')->findAll(30, $begin_time, $end_time);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}


}