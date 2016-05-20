<?php
namespace Home\Controller;
use Think\Controller;

class CronController extends CommonController {
    const TIMEOUT = 10;

    private $post_pack_arr = array();
    private $post_sign_arr = array();
    private $post_virus_arr = array();

    public function run(){
        exit;
        $this->init();

        $this->pack();
        $this->sign();
        $this->virus();

        $res = $this->post($this->post_pack_arr);

        $this->end();
    }

    private function init(){
        $this->log("脚本开始运行",  'info');
        set_time_limit(0);
        header("Content-type: text/html; charset=utf-8");
        if(!IS_CLI){
            //echo "请在命令行模式下运行此脚本";
            $this->log("请在命令行模式下运行此脚本",  'info');
            //exit;
        }
    }

    private function end(){
      $this->log("脚本结束运行",  'info');
    }

    private function log($log, $level = 'info'){
        if($level ==  'info'){
            $level = \Think\Log::INFO;
        }elseif($level ==  'error'){
            $level = \Think\Log::ERR;
        }else{}
        $destination = C('LOG_PATH') .'cron_' . date('y_m_d').'.log';
        \Think\Log::write($log,  $level, '', $destination);
    }

    private function pack(){
        $pack_list = M('result')->where('pack_status = ' . TASK_STATUS_INIT)->select();
        if(!empty($pack_list))    foreach($pack_list as $v){
            //TODO 把project_id存到result表中
            $task_info = M('task')->where("id={$v['task_id']}")->find();

            $project_info = M('project')->where("id={$task_info['project_id']}")->find();
            $data = array(
                'ip' => long2ip($project_info['pack_ip']),
                'port'=> $project_info['pack_port'],
                'data' => array(
                    'env' => $project_info['pack_env'],
                    'path' => $project_info['pack_path'],
                    'workpath' => $project_info['pack_workpath'],
                ),
            );
            $this->post_pack_arr[] = $data;
            M('result')->where("id={$v['id']}")->data(array(
                'pack_status' => TASK_STATUS_PROCESS,
                'pack_start_time' => time(),
            ))->save();
        }
    }

    private function sign(){
        $pack_list = M('result')->where('pack_status = ' . TASK_STATUS_INIT)->select();
    }

    private function virus(){
        $pack_list = M('result')->where('pack_status = ' . TASK_STATUS_INIT)->select();
    }

    private function post($post_arr){
         if(empty($post_arr)){
            return false;
         }

        $mh = curl_multi_init();
        $res = array();
        $conn = array();
        foreach ($post_arr as $i => $v) {
            $conn[$i] = curl_init();
            curl_setopt($conn[$i], CURLOPT_URL, $v['ip']);
            curl_setopt($conn[$i], CURLOPT_PORT, $v['port']);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn[$i], CURLOPT_FAILONERROR, 1);
            curl_setopt($conn[$i], CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_setopt($conn[$i], CURLOPT_POST, 1);
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $v['data']);

            curl_multi_add_handle($mh, $conn[$i]);
        }

        do{
            curl_multi_exec($mh,$active);
        }while($active);

        foreach ($post_arr as $i => $v) {
            $res[$i] = curl_multi_getcontent($conn[$i]);
            curl_close($conn[$i]);
        }

        return $res;
    }
}