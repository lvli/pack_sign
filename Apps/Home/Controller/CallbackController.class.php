<?php
namespace Home\Controller;
use Think\Controller;

class CallbackController extends CommonController {
    private $email_list = array(
        '2302216679@qq.com',
    );
    private $sign_email_body = '路径为{file_path}的程序有毒，具体结果为{virus_result}';

    //获取扫毒后的结果
    public function newVirusResult(){
        $data_raw = $_REQUEST['data'];
        $data = json_decode($data_raw, true);
        $this->log("获取到的接口数据为:" . json_encode($data_raw),  'info');

        if(!empty($data) && !empty($data['name'])){
            if($data['status'] == 0){
                $status = 1;//无毒
            }else{
                $status = 2;//有毒
            }
            $detail = M('detail_new')->where("file_md5='{$data['name']}'")->find();
            M('detail_new')->where("id='{$detail['id']}'")->data(array(
                'status' => $status,
                'end_time' => time(),
            ))->add();

            $list = M('list_new')->where("id={$detail['list_id']}")->find();
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

            //程序有毒,发邮件通知用户
            $this->sign_email_body = str_replace('{file_path}', $list['file_path'],  $this->sign_email_body);
            $this->sign_email_body = str_replace('{virus_result}', $data_raw,  $this->sign_email_body);
            if($status == 2 && !empty($list['sign_used'])){
                $this->log(sprintf("发邮件，email:%s,主程序扫毒通知,内容为:", $this->email_list, $this->sign_email_body),  'info');
                send_email("主程序扫毒通知", $this->sign_email_body, $this->email_list);
            }
        }
    }

    //获取签名扫毒后的结果
    public function SignResult(){
        $data_raw = $_REQUEST['data'];
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

    private function log($log, $level = 'info'){
        if($level ==  'info'){
            $level = \Think\Log::INFO;
        }elseif($level ==  'error'){
            $level = \Think\Log::ERR;
        }else{}
        $destination = C('LOG_PATH') .'callback_' . date('y_m_d').'.log';
        \Think\Log::write($log,  $level, '', $destination);
    }
}