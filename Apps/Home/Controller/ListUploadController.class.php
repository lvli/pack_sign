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


}