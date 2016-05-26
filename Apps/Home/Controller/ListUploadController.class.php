<?php
namespace Home\Controller;
use Think\Controller;

class ListUploadController extends CommonController {
	public function index(){
		$search_name = I('get.search_name', '', 'string');
		$result = D('ListUpload')->findAll(30, $search_name);
		$this->assign('list', $result['list']);
		$this->assign('pagination', $result['pagination']);
		$this->assign('search_name', $search_name);
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
		set_time_limit(0);
		$p = I('post.p', 1, 'int');
		$id = I('post.id', 0, 'int');
		$sign = I('post.sign', '', 'string');

		if(empty($id) || empty($sign)){
			$this->error('参数错误');
		}

		$status = D('ListUpload')->confirm_sign($id, $sign);
		if($status !== false){
			$this->success('修改成功', '/Home/ListUpload/index/p/' . $p);
		}else{
			$this->error('修改失败');
		}
	}

	public function upload(){
		$sign_list = M('sign_pool')->where('status = 0')->order('id ASC')->select();
		$this->assign('sign_list', $sign_list);
		$this->display();
	}

	public function upload_up(){
		$p = I('post.p', 1, 'int');
		$name = I('post.name', '', 'string');
		$ver = I('post.ver', '', 'string');
		$sign = I('post.sign', '', 'string');
		$description = I('post.description', '', 'string');

		if(empty($name)){
			$this->error('名称不能为空');
		}

		$save_path =  'Mains/';
		$file_path = DOWNLOAD_MAIN_SIGN_URL . $save_path .  $_FILES['file_path']['name'];

		$upload = new \Think\Upload();
		$upload->autoSub = false;
		$upload->rootPath = DOWNLOAD_MAIN_SIGN_URL;
		$upload->savePath = $save_path;
		$upload->saveName = '';
		$upload->uploadReplace = true;
		$info = $upload->uploadOne($_FILES['file_path']);
		if(!$info){
			$this->error($upload->getError());
		}

		$new_save_path = str_replace('Sign', 'Unsign', $file_path);
		if(!is_dir(dirname($new_save_path))){
			mkdir(dirname($new_save_path), 0755, true);
		}
		$ret = copy($file_path, $new_save_path);
		if(!$ret || !is_file($new_save_path)){
			$this->error('添加失败');
		}

		$status = D('ListUpload')->upload($name, $ver, $description, $file_path, $sign);

		if($status){
			$this->success('添加成功', '/index.php/Home/ListUpload/index/p/' . $p);
		}else{
			$this->error('添加失败');
		}
	}

	public function delete(){
		$p = I('post.p', 1, 'int');
		$id = I('get.id', 0, 'int');
		if(empty($id)){
			$this->error('删除失败');
		}

		$mains_id = (int)M('list_new')->where("id={$id}")->getField('mains_id');
		if($mains_id > 0){
			$this->error('请去ggg后台删除');
		}

		$status = D('ListUpload')->delete($id);

		if($status){
			$this->success('删除成功', '/index.php/Home/ListUpload/index/p/' . $p);
		}else{
			$this->error('删除失败');
		}
	}


}