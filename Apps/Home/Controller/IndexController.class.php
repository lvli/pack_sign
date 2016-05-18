<?php
namespace Home\Controller;
use Think\Controller;

class IndexController extends CommonController {

	public function index(){
		$this->redirect('/Home/ListUpload/index');
	}
}