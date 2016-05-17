<?php
namespace Home\Controller;
use Think\Controller;

class CronVirusController extends CommonController {
    private $sign_method = array(
        'signature_no_timstamp_normal',
        'signature_no_timstamp_sha256',
        'signature_no_timstamp_sha384',
        'signature_no_timstamp_sha512',
        'signature_tr',
        'signature_tr_td_sha256',
        'signature_tr_td_sha384',
        'signature_tr_td_sha512',
    );
    private $email_list = array(
        '2302216679@qq.com',
    );
    private $sign_email_body = '签名池的签名少于{n}个,请尽快增加签名';
    const BASE_SIGN_URL = 'C:\Users\Administrator\Desktop\tool\signtool.exe';
    const POST_VIRUS_URL = 'http://scanallfiles.com';
    const TIMEOUT = 10;

    public function run(){
        $this->init();

        //从mains数据库获取要扫毒的文件
        $this->log("从mains数据库获取新上传的文件",  'info');
        $this->save_list();

        //进行未签名的扫毒
        $this->log("进行未签名文件的扫毒",  'info');
        $list = $this->get_list(STATUS_INIT);
        $this->scan_virus($list);

        //签名,然后扫毒
        $this->log("签名,然后扫毒",  'info');
        $list = $this->get_list(STATUS_PROGRAM_NO_VIRUS);
        $this->scan_sign($list);
        $this->scan_virus($list);

        //签名后有毒，需要更换签名再次扫描的
        $this->log("签名后有毒的文件，需要更换签名再次扫描的",  'info');
        $list = $this->get_list(STATUS_PROGRAM_STILL_VIRUS);
        $this->scan_sign($list);
        $this->scan_virus($list);

        //签名之后没有问题，上传CDN
        $this->log("签名之后没有问题的文件，上传CDN",  'info');
        $list =  $this->get_list(STATUS_SIGN_NO_VIRUS);
        $this->up_cdn($list);

        $this->log("脚本结束运行",  'info');
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

        //初始化配置文件
        if(empty($this->config['min_sign_email'])){
            $this->config['min_sign_email'] = 3;
        }
        $this->sign_email_body = str_replace('{n}', $this->config['min_sign_email'], $this->sign_email_body);
    }

    private function save_list(){
        $connection = sprintf("mysql://%s:%s@%s:%s/%s", C('DB_INS_USER'), C('DB_INS_PWD'), C('DB_INS_HOST'), C('DB_INS_PORT'), C('DB_INS_NAME'));
        $this->log(sprintf("DB_INS_HOST=%s,DB_INS_NAME=%s", C('DB_INS_HOST'), C('DB_INS_NAME')),  'info');

        //状态值，1 正常，0删除 2=处理中 3=无毒 4=程序有毒 5=签名有毒
        $file_list = M('mains', NULL, $connection)->where('status=1')->field('id,path')->select();
        M('mains', NULL, $connection)->where('status=1')->data(array(
            "status" => 2,
        ))->save();
        $this->log("从mains数据表获取到的新上传文件:" . json_encode($file_list),  'info');

        if(!empty($file_list))    foreach($file_list as $v){
            M('list_new')->data(array(
                'mains_id' => $v['id'],
                'file_path' => $v['path'],
                'status' => 0,
                'scan_time' => time(),
                'email_status' => 0,
            ))->add();
        }
        return $file_list;
    }

    private function get_list($status){
        $list = M('list_new')->where('status='.$status)->select();
        $this->log("从list_new表查询到status={$status}的数据为:" . json_encode($list),  'info');
        return $list;
    }

    private function scan_virus($list){
        $post_url = self::POST_VIRUS_URL . '/index.php?m=Upload&a=Upload';
        $this->log("扫毒接口的url为:" . $post_url,  'info');

        $post_arr = array();
        foreach($list as $v){
            if(class_exists('\CURLFile')){
                $post_data = array(
                    "file_path" =>	new \CURLFile($v['file_path']),
                );
            }else{
                $post_data = array(
                    "file_path" =>	'@' . $v['file_path'],
                );
            }
            $post_data['email_list'] = 'JSON_API';
            $post_arr[] = $post_data;

            $sign = array_pop(explode('', $v['sign_used']));
            $data = array(
                'list_id' => $v['id'],
                'file_md5' => md5_file($v['file_path']),
                'status' => 0,
                'begin_time' => time(),
                'sign' => $sign == NULL ? '' : $sign,
            );
            M('detail_new')->data($data)->add();
            $this->log("记录到detail_new表中的信息为:" . json_encode($data),  'info');
        }
        $this->post($post_url, $post_arr);
    }

    private function scan_sign($list){
        //C:\Users\Administrator\Desktop\tool\signtool.exe sign /f C:\Users\Administrator\Desktop\tool\lizhuo1008.pfx /fd sha256 /p worktogether C:\Users\Administrator\Desktop\mssign32.dll
        $this->log("签名池中的最小签名个数(min_sign_email):" . $this->config['min_sign_email'],  'info');
        $id_list = array();
        $email_id_str = '';
        if(!empty($list))    foreach($list as $k => $v){
            $this->log("当前处理的数据，在list表中的信息为:" . json_encode($v),  'info');
            //随机得到签名算法
            $v['sign_method'] = array_rand($this->sign_method);
            $this->log("随机选到的签名算法为:" . $v['sign_method'],  'info');

            //获取未使用的签名
            $this->log("之前已使用过的签名，在签名池中的ID为:" . $v['sign_used'],  'info');
            if(empty($v['sign_used'])){
                $sign_list = M('sign_pool')->where('status=0')->select();
            }else{
                $sign_list = M('sign_pool')->where('id NOT IN ('.$v['sign_used'].') AND status=0')->select();
            }
            //小于等于n(默认为3)个，发报警邮件 不处理这个签名
            if(count($sign_list) <= $this->config['min_sign_email']){
                $email_id_str = $v['id'] . ',';
                continue;
            }

            $v['sign_path'] = $sign_list[0]['sign_path'];
            $v['sign_pwd'] = $sign_list[0]['sign_pwd'];
            $sign_cmd = sprintf("%s sign /f %s /fd %s /p %s %s", self::BASE_SIGN_URL, $v['sign_path'], $v['sign_method'], $v['sign_pwd'], $v['file_path']);
            system($sign_cmd, $ret);
            $this->log(sprintf("签名执行的命令为%s,返回值为%s",$sign_cmd, $ret),  'info');
            if($ret !== FALSE){
                $id_list[] .= $v['id'] . ',';
                //记录使用过的签名
                $sign_used = trim($v['sign_used'] . ',' . $sign_list[0]['id'], ',');
                M('list_new')->where('id='.$v['id'])->data(array(
                    'sign_used' => $sign_used,
                ))->save();
            }
        }

        //统一修改签名后的表状态
        $id_str = implode(',', rtrim($id_list, ','));
        if(!empty($id_str)){
            M('list_new')->where('id IN ('.$id_str . ' )')->data(array(
                'status' => STATUS_SIGN,
            ))->save();
        }

        //统一发邮件
        foreach($this->email_list as $k => $email){
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                unset($this->email_list[$k]);
            }
        }

        if(!empty($email_list)){
            $this->log(sprintf("发邮件，email:%s,内容为:", $this->email_list, $this->sign_email_body),  'info');
            send_email("签名池通知", $this->sign_email_body, $this->email_list);
        }
        $email_id_str = trim($email_id_str, ',');
        if(!empty($email_id_str)){
            M('list_new')->where('id IN ('.$email_id_str . ' )')->data(array(
                'email_status' => 1,
            ))->save();
        }
    }

    private function up_cdn($list){

    }

    private function post($post_url, $post_arr){
        if(empty($post_arr)){
            $this->log(sprintf("url为%s,CURL POST数据为空", $post_url),  'info');
            return false;
        }

        $mh = curl_multi_init();
        $res = array();
        $conn = array();
        foreach ($post_arr as $i => $post_data) {
            $conn[$i] = curl_init();
            curl_setopt($conn[$i], CURLOPT_URL, $post_url);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn[$i], CURLOPT_FAILONERROR, 1);
            curl_setopt($conn[$i], CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_setopt($conn[$i], CURLOPT_POST, 1);
            curl_setopt($conn[$i], CURLOPT_POSTFIELDS, $post_data);
            curl_multi_add_handle($mh, $conn[$i]);
            $this->log(sprintf("url为%s,CURL POST数据为%s:", $post_url, json_encode($post_data)),  'info');
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

    private function log($log, $level = 'info'){
        if($level ==  'info'){
            $level = \Think\Log::INFO;
        }elseif($level ==  'error'){
            $level = \Think\Log::ERR;
        }else{}
        $destination = C('LOG_PATH') .'cron_' . date('y_m_d').'.log';
        \Think\Log::write($log,  $level, '', $destination);
    }
}