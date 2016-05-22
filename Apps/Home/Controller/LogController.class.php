<?php
namespace Home\Controller;
use Think\Controller;

class LogController extends CommonController {
	public function index(){
		$type = I('request.type', '', 'string');
		$level = I('request.level', '', 'string');
		$begin_time = I('request.begin_time', '', 'string');
		$end_time = I('request.end_time', '', 'string');

		$result = D('Log')->findAll(30, $type, $level, $begin_time, $end_time);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}


}