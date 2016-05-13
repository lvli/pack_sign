<?php
namespace Home\Controller;
use Think\Controller;

class ResultController extends CommonController {
    const SUCCESS_URL = '/Home/Result/index/task_id/';

	public function index(){
		$task_id = I('get.task_id', 0, 'int');
		$result = D('Result')->findAll($task_id, 30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);

		//$task_name = D('Project')->findOne($task_id, 'task_name');
		/*if(empty($task_name)){
			$this->error('任务不存在');
		}*/
		$this->assign('task_id', $task_id);
		//$this->assign('task_name', $task_name);
		$this->display();
	}

}