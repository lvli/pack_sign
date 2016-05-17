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
        $this->save_list();

        //进行未签名的扫毒
        $list = $this->get_list(STATUS_INIT);
        $this->scan_virus($list);

        //签名,然后扫毒
        $list = $this->get_list(STATUS_PROGRAM_NO_VIRUS);
        $this->scan_sign($list);
        $this->scan_virus($list);

        //签名后有毒，需要更换签名再次扫描的
        $list = $this->get_list(STATUS_PROGRAM_STILL_VIRUS);
        $this->scan_sign($list);
        $this->scan_virus($list);

        //签名之后没有问题，上传CDN
        $list =  $this->get_list(STATUS_SIGN_NO_VIRUS);
        $this->up_cdn($list);

        $this->end();
    }

    private function scan_sign($list){
        //C:\Users\Administrator\Desktop\tool\signtool.exe sign /f C:\Users\Administrator\Desktop\tool\lizhuo1008.pfx /fd sha256 /p worktogether C:\Users\Administrator\Desktop\mssign32.dll
        $id_list = array();
        $email_id_str = '';
        if(!empty($list))    foreach($list as $k => $v){
            //随机得到签名算法
            $v['sign_method'] = array_rand($this->sign_method);

            //获取未使用的签名
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
            send_email("签名池通知", $this->sign_email_body, $this->email_list);
        }
        $email_id_str = trim($email_id_str, ',');
        M('list_new')->where('id IN ('.$email_id_str . ' )')->data(array(
            'email_status' => 1,
        ))->save();
    }

    private function get_list($status){
        return M('list_new')->where('status='.$status)->select();
    }

    private function save_list(){
        $file_list = M('ins.mains')->where('status=1')->select();
        if(!empty($file_list))    foreach($file_list as $v){
            M('list_new')->data(array(
                'mains_id' => $v['id'],
                'file_path' => $v['path'],
                'status' => 0,
                'scan_time' => time(),
                'email_status' => 0,
            ))->save();
        }
        return $file_list;
    }

    private function scan_virus($list){
        $post_url = self::POST_VIRUS_URL . '/index.php?m=Upload&a=Upload';

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
            M('detail_new')->data(array(
                'list_id' => $v['id'],
                'file_md5' => md5_file($v['file_path']),
                'status' => 0,
                'begin_time' => time(),
                'sign' => $sign == NULL ? '' : $sign,
            ))->add();
        }
       $this->post($post_url, $post_arr);
    }

    private function post($post_url, $post_arr){
        if(empty($post_arr)){
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

    private function up_cdn($list){

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
}