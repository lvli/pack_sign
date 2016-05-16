<?php
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {

    public function __construct(){
        parent::__construct();
        $exclude_actions = array(
            'User/login',
            'User/login_up',
        );
        if(!empty($exclude_actions))    foreach($exclude_actions as $actions){
            if(stripos($actions, __SELF__) !== false){
                $status = D('User')->is_login();
                if(!$status){
                    $this->redirect('/Home/User/login');
                }
            }
        }

    }
}