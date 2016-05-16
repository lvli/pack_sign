<?php
namespace Home\Controller;
use Think\Controller;

class TaskController extends CommonController {
    const SUCCESS_URL = '/Home/Task/index/project_id/';

	public function index(){
		$project_id = I('get.project_id', 0, 'int');
		$result = D('Task')->findAll($project_id, 30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);

		$project_name = D('Project')->findOne($project_id, 'project_name');
		if(empty($project_name)){
			$this->error('项目不存在');
		}
		$this->assign('project_id', $project_id);
		$this->assign('project_name', $project_name);
		$this->display();
	}

	public function save(){
		$id = I('get.id', 0, 'int');
		$project_id = I('get.project_id', 0, 'int');
		$data = D('Task')->find($id);
		$this->assign('data', $data);
		$project_name = D('Project')->findOne($project_id, 'project_name');
		if(empty($project_name)){
			$this->error('项目不存在');
		}
		$this->assign('project_name', $project_name);
		$this->assign('project_id', $project_id);
		$this->assign('id', $id);
		$this->display();
	}

	public function save_up(){
		$project_id = I('post.project_id', '', 'int');
		$is_sign = I('post.is_sign', 0, 'int');
		$is_virus = I('post.is_virus', 0, 'int');
		$sign_path = I('post.sign_path', '', 'string');
		$back = I('post.back', '', 'string');
		$id = I('post.id', 0, 'int');

        if(empty($project_id)){
			$this->error('项目ID不能为空');
		}

		$status = D('Task')->save($project_id, $is_sign, $is_virus, $sign_path, $back, $id);

		if($status){
			if(empty($id)){
				$this->success('添加成功', self::SUCCESS_URL . $project_id);
			}else{
				$this->success('修改成功', self::SUCCESS_URL . $project_id);
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
		$project_id = I('get.project_id', 0, 'int');
		if(empty($project_id)){
			$this->error('项目ID不能为空');
		}

		$id = I('get.id', 0, 'int');
		if(empty($id)){
			$this->error('删除失败');
		}

		$status = D('Task')->delete($id);

		if($status){
			$this->success('删除成功', self::SUCCESS_URL . $project_id);
		}else{
			$this->error('删除失败');
		}
	}

	public function run(){
		$project_id = I('get.project_id', 0, 'int');
		$task_id = I('get.task_id', 0, 'int');

		if(empty($project_id)){
			$this->error('项目ID不能为空');
		}
		if(empty($task_id)){
			$this->error('任务ID不能为空');
		}

		$status = D('Result')->save_up($task_id);
		if($status){
			$this->success('开始运行', self::SUCCESS_URL . $project_id);
		}else{
			$this->error('运行失败');
		}
	}

}