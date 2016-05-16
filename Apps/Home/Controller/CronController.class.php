<?php
namespace Home\Controller;
use Think\Controller;

class CronController extends Controller {
    const TIMEOUT = 10;

    private $post_pack_arr = array();
    private $post_sign_arr = array();
    private $post_virus_arr = array();

    public function run(){
        $this->init();

        $this->pack();
        $this->sign();
        $this->virus();

        $this->post($this->post_pack_arr);

    }

    private function init(){
        if(!IS_CLI){
            echo "请在命令行模式下运行此脚本";
            exit;
        }
    }

    private function pack(){
        $pack_list = M('result')->where('pack_status = ' . TASK_STATUS_INIT)->find();
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
                'pack_start_time' => time(),
            ))->save();
        }
    }

    private function sign(){
        $pack_list = M('result')->where('pack_status = ' . TASK_STATUS_INIT)->find();
    }

    private function virus(){
        $pack_list = M('result')->where('pack_status = ' . TASK_STATUS_INIT)->find();
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