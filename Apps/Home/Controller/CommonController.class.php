<?php
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {

    public function __construct(){
        parent::__construct();
        $exclude_actions = array(
            '/Home/User/login',
            '/Home/User/login_up',
        );
        if(!in_array(__SELF__, $exclude_actions)){
            $status = D('User')->is_login();
            if(!$status){
                $this->redirect('/Home/User/login');
            }
        }
    }
}