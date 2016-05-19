<?php
namespace Home\Controller;
use Think\Controller;

class CallbackController extends CommonController {
    protected $log_prefix = 'callback';

    //获取扫毒后的结果
    public function newVirusResult(){
        $data_raw = $_REQUEST['data'];
        $data_raw = str_replace('\'', '"', $data_raw);
        $data_raw = trim($data_raw, '"');
        $data = json_decode($data_raw, true);
        $this->log("获取到的接口数据为:" . $data_raw,  'info');

        if(!empty($data) && !empty($data['name'])){
            if($data['status'] == 0){
                $status = 1;//无毒
            }else{
                $status = 2;//有毒
            }
            $detail = M('detail_new')->where("file_md5='{$data['name']}'")->find();
            $this->log("detail_new中查询到的数据为:" . json_encode($detail),  'info');
            M('detail_new')->where("id='{$detail['id']}'")->data(array(
                'status' => $status,
                'end_time' => time(),
            ))->add();
            $this->log("修改detail_new表中id={$detail['id']}的status={$status}",  'info');

            $list = M('list_new')->where("id={$detail['list_id']}")->find();
            $this->log("list_new:" . json_encode($list),  'info');

            if($status == 1){ //如果无毒的话
                if(empty($list['sign_used'])){
                    $list_status = STATUS_PROGRAM_NO_VIRUS;
                }else{
                    $list_status = STATUS_SIGN_NO_VIRUS;
                }
            }else{  //如果有毒的话
                if(empty($list['sign_used'])){
                    $list_status = STATUS_PROGRAM_VIRUS;
                }else{
                    $list_status = STATUS_SIGN_STILL_VIRUS_NO_CHECK;
                }
            }

            //修改list表
            M('list_new')->where("id={$detail['list_id']}")->data(array(
                'status' => $list_status,
                'scan_time' => time(),
            ))->save();
            $this->log("修改list_new表id={$detail['list_id']}的status={$list_status}" . json_encode($list),  'info');
        }
    }

    //获取签名扫毒后的结果
    public function SignResult(){
        $data_raw = $_REQUEST['data'];
        $data_raw = str_replace('\'', '"', $data_raw);
        $data_raw = trim($data_raw, '"');
        $data = json_decode($data_raw, true);
        $this->log("获取到的接口数据为:" . json_encode($data_raw),  'info');

        if(!empty($data) && !empty($data['name'])){
            if($data['status'] == 0){
                $status = 1;//无毒
            }else{
                $status = 2;//有毒
            }
            $data = array(
                'status' => $status,
                'end_time' => time(),
            );
            M('check_sign')->where("sign_md5='{$data['name']}'")->data($data)->save();
            $sign_pool_id = M('check_sign')->where("sign_md5='{$data['name']}'")->getField('sign_pool_id');

            //停用有问题的签名
            if($status == 1){
                M('sign_pool')->where("id='{$sign_pool_id}'")->data(array(
                    'status' => 1,
                    'edittime' => time(),
                    'back' => '签名报毒已被系统停用',
                ))->save();
            }
        }
    }

    //CDN上文件的扫描
    public function cdnVirusResult(){
        $data_raw = $_REQUEST['data'];
        $data_raw = str_replace('\'', '"', $data_raw);
        $data_raw = trim($data_raw, '"');
        $data = json_decode($data_raw, true);
        $this->log("获取到的接口数据为:" . json_encode($data_raw),  'info');

        $time = time();
        if(!empty($data) && !empty($data['name'])){
            $id = M('detail_new')->where("file_md5='{$data['name']}'")->getField('list_id');
            if($data['status'] == 0){ //无毒
                M('list_cron')->where("id={$id}")->data(array(
                    'status' => STATUS_PROGRAM_NO_VIRUS,
                    'scan_time' => $time,
                ))->save();
            }else{ //有毒
                //修改list表 如果有毒，把list_new上的状态改为初始状态，按新文件的流程继续扫描
                M('list_new')->where("id={$id}")->data(array(
                    'status' => STATUS_INIT,
                    'scan_time' => $time,
                ))->save();
                M('list_cron')->where("id={$id}")->data(array(
                    'status' => STATUS_PROGRAM_VIRUS,
                    'scan_time' => $time,
                ))->save();
                $connection = sprintf("mysql://%s:%s@%s:%s/%s", C('DB_INS_USER'), C('DB_INS_PWD'), C('DB_INS_HOST'), C('DB_INS_PORT'), C('DB_INS_NAME'));
                $this->log(sprintf("DB_INS_HOST=%s,DB_INS_NAME=%s", C('DB_INS_HOST'), C('DB_INS_NAME')),  'info');

                $list_new = M('list_cron')->where("id={$id}")->find();
                M('mains', NULL, $connection)->where('id='.$list_new['mains_id'])->data(array(
                    "sign_status" => MAINS_STATUS_PROGRAM_VIRUS,
                ))->save();
            }
        }
    }

    private function log($log, $level = 'info'){
        $this->log_to_table($log, $level, $this->log_prefix);
    }
}