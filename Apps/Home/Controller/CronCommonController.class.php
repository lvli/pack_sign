<?php
namespace Home\Controller;
use Think\Controller;

//定时任务的基类
class CronCommonController extends CommonController {
    const CHECK_SIGN_URL = 'c:\windows\notepad.exe';

    protected $log_prefix = '';
    protected $table_detail = '';
    protected $table_list = '';
    protected $sign_method = array(
        'signature_no_timstamp_normal',
        'signature_no_timstamp_sha256',
        'signature_no_timstamp_sha384',
        'signature_no_timstamp_sha512',
        'signature_tr',
        'signature_tr_td_sha256',
        'signature_tr_td_sha384',
        'signature_tr_td_sha512',
    );
    protected $email_list = array(
        '2302216679@qq.com',
    );
    protected $sign_email_body = '签名池的签名少于{n}个,请尽快增加签名';
    const BASE_SIGN_URL = 'C:\Users\Administrator\Desktop\tool\signtool.exe';
    const POST_VIRUS_URL = 'http://scanallfiles.com';
    const TIMEOUT = 10;

    protected function init(){
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

    protected function get_list($status){
        $list = M($this->table_list)->where('status='.$status)->select();
        $this->log(sprintf("从%s表查询到status={$status}的数据为:",$this->table_list ,json_encode($list)),  'info');
        return $list;
    }

    protected function scan_virus($list){
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
            $post_data['email_list'] = array_merge($this->email_list, array('JSON_API_PS'));
            $post_data['email_list'] = implode(',',  $post_data['email_list']);
            $post_arr[] = $post_data;

            $sign = array_pop(explode('', $v['sign_used']));
            $data = array(
                'list_id' => $v['id'],
                'file_md5' => md5_file($v['file_path']),
                'status' => 0,
                'begin_time' => time(),
                'sign' => $sign == NULL ? '' : $sign,
            );
            M($this->table_detail)->data($data)->add();
            $this->log(sprintf("记录到%s表中的信息为:",$this->table_detail, json_encode($data)),  'info');
        }
        $this->post($post_url, $post_arr);
    }

    protected function scan_signed_cdn($list){
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
            $post_data['email_list'] = array_merge($this->email_list, array('JSON_API_CDN'));
            $post_data['email_list'] = implode(',',  $post_data['email_list']);
            $post_arr[] = $post_data;

            $sign = array_pop(explode('', $v['sign_used']));
            $data = array(
                'list_id' => $v['id'],
                'file_md5' => md5_file($v['file_path']),
                'status' => 0,
                'begin_time' => time(),
                'sign' => $sign == NULL ? '' : $sign,
            );
            M($this->table_detail)->data($data)->add();
            $this->log(sprintf("记录到%s表中的信息为:",$this->table_detail, json_encode($data)),  'info');
        }
        $this->post($post_url, $post_arr);
    }

    protected function check_scan($list){
        if(empty($list)){
            return false;
        }

        $sign_list = array();//需要验证的签名列表
        foreach($list as $v){
            $sign = array_pop(explode(',', $v['sign_used']));
            if(empty($sign_list[$sign])){
                $sign_list[$sign] = M('sign_pool')->where('id={$v}')->find();
            }
        }

        foreach($sign_list as $v){
            $sign_cmd = sprintf("%s sign /f %s /fd %s /p %s %s", self::BASE_SIGN_URL, $v['sign_path'], $this->sign_method[0], $v['sign_pwd'], self::CHECK_SIGN_URL);
            system($sign_cmd, $ret);
            if($ret !== FALSE){
                $post_url = self::POST_VIRUS_URL . '/index.php?m=Upload&a=Upload';
                if(class_exists('\CURLFile')){
                    $post_data = array(
                        "file_path" =>	new \CURLFile($v['file_path']),
                    );
                }else{
                    $post_data = array(
                        "file_path" =>	'@' . $v['file_path'],
                    );
                }
                $post_data['email_list'] = array_merge($this->email_list, array('JSON_API_SIGN'));
                $post_data['email_list'] = implode(',',  $post_data['email_list']);
                $data = array(
                    'sign_pool_id' => $v['id'],
                    'status' => 0,//0=未开始 1=无毒 2=有毒
                    'begin_time' => time(),
                );
                M('check_sign')->data($data)->add();
                $this->post($post_url, array($post_data));
            }
        }
    }

    protected function scan_sign($list){
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
            $sign_list = M('sign_pool')->where('status=0')->select();
            //小于等于n(默认为3)个，发报警邮件 不处理这个签名
            if(count($sign_list) <= $this->config['min_sign_email']){
                $email_id_str = $v['id'] . ',';
                $this->log("小于等于n(默认为3)个，发报警邮件 不处理这个签名",  'info');
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
                M($this->table_list)->where('id='.$v['id'])->data(array(
                    'sign_used' => $sign_used,
                ))->save();
            }
        }

        //统一修改签名后的表状态
        $id_str = implode(',', rtrim($id_list, ','));
        if(!empty($id_str)){
            $this->log(sprintf("统一修改签名后的表状态,id_str=%s,status=%s", $id_str, STATUS_SIGN),  'info');
            M($this->table_list)->where('id IN ('.$id_str . ' )')->data(array(
                'status' => STATUS_SIGN,
            ))->save();
        }

        //统一发邮件
        foreach($this->email_list as $k => $email){
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                unset($this->email_list[$k]);
            }
        }

        $this->log(sprintf("邮件列表为%s", json_encode($this->email_list)),  'info');
        if(!empty($email_list)){
            $this->log(sprintf("发邮件，email:%s,签名池通知,内容为:", $this->email_list, $this->sign_email_body),  'info');
            send_email("签名池通知", $this->sign_email_body, $this->email_list);
        }
        $email_id_str = trim($email_id_str, ',');
        if(!empty($email_id_str)){
            M($this->table_list)->where('id IN ('.$email_id_str . ' )')->data(array(
                'email_status' => 1,
            ))->save();
        }
    }

    protected function up_cdn($list){
        require_once(ROOT_PATH .'Lib/cdn.php');
        $cdn = new \CDN();
        foreach($list as $v){
            $cdn->put_cdn_file($v['file_path']);
            M($this->table_list)->where('id=' . $v['id'])->data(array(
                'status' => STATUS_CDN_UPLOADED,
            ))->save();

            //修改main表上的状态为已上传CDN
            $this->log('修改main表上的状态为已上传CDN',  'info');
            $connection = sprintf("mysql://%s:%s@%s:%s/%s", C('DB_INS_USER'), C('DB_INS_PWD'), C('DB_INS_HOST'), C('DB_INS_PORT'), C('DB_INS_NAME'));
            $this->log(sprintf("DB_INS_HOST=%s,DB_INS_NAME=%s", C('DB_INS_HOST'), C('DB_INS_NAME')),  'info');
            M('mains', NULL, $connection)->where('id='.$v['mains_id'])->data(array(
                'sign_status' => MAINS_STATUS_UPLOADED_CDN,
            ))->save();
        }
    }

    protected function post($post_url, $post_arr){
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

    protected function downloadUSigned($file_list){
        $cdn_dwonload_url = C('CDN_DOWANLOAD_URL');
        foreach($file_list as &$v){
            $v['download_url'] = $cdn_dwonload_url . $v['path'];
            $v['save_path'] = DOWNLOAD_URL . $v['path'];
        }
        $this->download($file_list);
    }

    protected function downloadUnSign($file_list){
        $ggg_domain_url = C('GGG_DOMAIN_URL');
        foreach($file_list as &$v){
            $v['download_url'] = $ggg_domain_url . $v['path'];
            $v['save_path'] = DOWNLOAD_MAIN_URL . $v['path'];
        }
        $this->download($file_list);
    }

    protected function download($file_list){
        if(empty($file_list)){
            return false;
        }

        $file_list = array_chunk($file_list, 20);
        foreach($file_list as $v){
            $this->_download($v);
        }
    }

    private function _download($file_list){
        $this->log(sprintf("开始下载文件，列表为%s", json_encode($file_list)),  'info');

        $mh = curl_multi_init();
        $res = array();
        $conn = array();
        foreach ($file_list as $i => $v) {
            $conn[$i] = curl_init();
            curl_setopt($conn[$i], CURLOPT_URL, $v['download_url']);
            curl_setopt($conn[$i], CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn[$i], CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn[$i], CURLOPT_FAILONERROR, 1);
            curl_setopt($conn[$i], CURLOPT_TIMEOUT, self::TIMEOUT);
            curl_multi_add_handle($mh, $conn[$i]);
        }

        do{
            curl_multi_exec($mh, $active);
        }while($active);

        foreach ($file_list as $i => $v) {
            if (!is_dir(dirname($v['save_path']))) {
                mkdir(dirname($v['save_path']), 0755, true);
            }

            $res[$i] = curl_multi_getcontent($conn[$i]);
            $fp = fopen($v['save_path'], 'w');
            fwrite($fp, $res[$i]);
            fclose($fp);
            unset($res[$i]);
            curl_close($conn[$i]);
        }
        $this->log("下载文件结束",  'info');
    }

    protected function log($log, $level = 'info'){
        if($level ==  'info'){
            $level = \Think\Log::INFO;
        }elseif($level ==  'error'){
            $level = \Think\Log::ERR;
        }else{}
        $destination = C('LOG_PATH') .$this->log_prefix . date('y_m_d').'.log';
        \Think\Log::write($log,  $level, '', $destination);
    }
}