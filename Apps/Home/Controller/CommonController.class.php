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
            'Cron',
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

        //记录操作日志
        $this->action_log();

        $params = explode('/', __SELF__);
        $menu_hl = $params[2] . '_' . $params[3];
        $menu_hl = strstr($menu_hl, '?', true);
        $this->assign('menu_hl', $menu_hl);
    }

    protected function log_to_table($content, $level = 'error', $type = ''){
        if(!LOG_VERBOSE && $level == 'info'){
            return false;
        }

        return M('log')->data(array(
            'type' => $type,
            'level' => strtolower($level),
            'content' => $content,
            'url' => $_SERVER['REQUEST_URI'],
            'ip' => get_client_ip(1, true),
            'addtime' => time(),
        ))->add();
    }

    private function action_log(){
        $exclude_actions = array(
            'User/login',
            'User/login_up',
            'Callback',
            'Cron',
            'Action/index',
        );

        if(!empty($exclude_actions))    foreach($exclude_actions as $actions){
            if(stripos(__SELF__, $actions) !== false){
                return false;
            }
        }

        return M('action_log')->data(array(
            'url' => $_SERVER['REQUEST_URI'],
            'post' => json_encode($_POST),
            'admin_id' => session('admin_id'),
            'ip' => get_client_ip(1, true),
            'addtime' => time(),
        ))->add();
    }
}