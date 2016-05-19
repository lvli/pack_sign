<?php
namespace Home\Controller;
use Think\Controller;

class CommonController extends Controller {

    public function __construct(){
        parent::__construct();
        require  COMMON_PATH.'Common/constants.php';

        $exclude_actions = array(
            'User/login',
            'User/login_up',
            'Callback',
        );

        $jump_flag = false;
        if(!empty($exclude_actions))    foreach($exclude_actions as $actions){
            if(stripos(__SELF__, $actions) !== false){
                $jump_flag = true;
            }
        }

        if(!$jump_flag){
            $status = D('User')->is_login();
            if(!$status){
                $this->redirect('/Home/User/login');
            }
        }

        $params = explode('/', __SELF__);
        $menu_hl = $params[2] . '_' . $params[3];
        $this->assign('menu_hl', $menu_hl);
    }
}