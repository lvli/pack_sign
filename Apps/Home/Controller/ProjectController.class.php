<?php
namespace Home\Controller;
use Think\Controller;

class ProjectController extends Controller {
    const SUCCESS_URL = '/Home/Project/index';

	public function index(){
		$result = D('Project')->findAll(30);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->display();
	}

	public function save(){
		$id = I('get.id', 0, 'int');
		$data = D('Project')->find($id);
		$this->assign('data', $data);
		$this->display();
	}

	public function save_up(){
		$project_name = I('post.project_name', 0, 'string');
		$pack_ip = I('post.pack_ip', 0, 'validate_ip');
		$pack_port = I('post.pack_port', 0, 'int');
		$pack_env = I('post.pack_env', 0, 'string');
		$pack_path = I('post.pack_path', 0, 'string');
		$pack_workpath = I('post.pack_workpath', 0, 'string');
		$back = I('post.back', 0, 'string');
		$id = I('post.id', 0, 'int');

		$pack_ip = ip2long($pack_ip);
        if(empty($project_name)){
			$this->error('项目名称不能为空');
		}
		if(empty($pack_ip)){
			$this->error('打包服务器IP不合法');
		}
		if(empty($pack_port)){
			$this->error('打包服务器端口不能为空');
		}
		if(empty($pack_path)){
			$this->error('打包服务器脚本路径不能为空');
		}

		$status = D('Project')->save($project_name, $pack_ip, $pack_port, $pack_env, $pack_path, $pack_workpath, $back, $id);

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

		$status = D('Project')->delete($id);

		if($status){
			$this->success('删除成功', self::SUCCESS_URL);
		}else{
			$this->error('删除失败');
		}
	}


}