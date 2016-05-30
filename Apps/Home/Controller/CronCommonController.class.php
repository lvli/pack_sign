<?php
namespace Home\Controller;
use Think\Controller;

//定时任务的基类
class CronCommonController extends CommonController {
    const SEND_EMAIL_TIME_RANGE = 3600;
    const DEAL_TIMEOUT = 300;

    protected $log_prefix = '';
    protected $table_detail = '';
    protected $table_list = '';
    protected $sign_method = array('signature_normal', 'signature_normal_sha256', 'signature_normal_sha384', 'signature_normal_sha512', 'signature_tr', 'signature_tr_td_sha256', 'signature_tr_td_sha384', 'signature_tr_td_sha512', 'signature_append_sha256', 'signature_append_sha384', 'signature_append_sha512',);
    protected $email_list = array();
    protected $sign_email_body = '签名池的签名少于{n}个,请尽快增加签名';
    const TIMEOUT = 10;
    const POST_TIME_INTERVEL = 2000000;//2秒 单位为毫秒

    protected function init() {
        $this->log("脚本开始运行", 'info');
        set_time_limit(0);
        header("Content-type: text/html; charset=utf-8");

        //初始化配置文件
        $config = D('Config')->findAll();
        $this->config['min_sign_email'] = $config['min_sign_num'];
        $this->email_list = explode(',', $config['email_list']);
        $this->sign_email_body = str_replace('{n}', $this->config['min_sign_email'], $this->sign_email_body);

    }

    protected function get_list($status) {
        $list = M($this->table_list)->where('status=' . $status)->select();
        $this->log(sprintf("从%s表查询到status=%s的数据为:%s", $this->table_list, $status, json_encode($list)), 'info');
        //去掉正在处理的数据
        foreach($list as $k => $v){
           $info =  M($this->table_detail)->where("list_id={$v['id']} AND status = 0")->order('id DESC')->find();
            if(time() - $info['begin_time'] >= self::DEAL_TIMEOUT){
                M($this->table_detail)->where("list_id={$v['id']} AND status = 0")->delete();
                $this->log(sprintf("从%s表去掉超时的数据,time=%s,begin_time=%s,id=%s,deal_timeout=%s", $this->table_detail, time(), $info['begin_time'], $info['id'], self::DEAL_TIMEOUT),  'info');
            }else
           if(!empty($info['id'])){
                unset($list[$k]);
           }
        }
        $this->log(sprintf("从%s表去掉正在处理的数据以后，剩下的数据%s", $this->table_list, json_encode($list)), 'info');
        return $list;
    }

    protected function init_list($list){
        if(empty($list)){
            return array();
        }

        foreach($list as $v){
            M('list_new')->data(array(
                'sign_used' => '',
            ))->where("id={$v['id']}")->save();
        }
        return $list;
    }

    protected function scan_virus($list, $flag = false) {
        $post_url = POST_VIRUS_URL . '/index.php?m=Upload&a=Upload';
        $this->log("扫毒接口的url为:" . $post_url, 'info');

        $post_arr = array();
        foreach($list as $v) {
            $v['file_path'] = $this->get_file_path($v['file_path']);
            if($flag){
                $new_save_path = str_replace('Sign', 'Unsign', $v['file_path']);
                if(!is_file($new_save_path)){
                    $this->log(sprintf("未签名文件的路径不存在，路径为%s", $new_save_path),  'error');
                    continue;
                }
                copy($new_save_path, $v['file_path']);
            }

            if(class_exists('\CURLFile')) {
                $post_data = array("file_path" => new \CURLFile($v['file_path']),);
            } else {
                $post_data = array("file_path" => '@' . $v['file_path'],);
            }
            $post_data['email_list'] = 'JSON_API_PS';
            $post_arr[] = $post_data;

            $sign = array_pop(explode('', $v['sign_used']));
            $data = array('list_id' => $v['id'], 'file_md5' => md5_file($v['file_path']), 'status' => 0, 'begin_time' => time(), 'sign' => $sign == NULL ? '' : $sign,);
            M($this->table_detail)->data($data)->add();
            $this->log(sprintf("记录到%s表中的信息为:%s", $this->table_detail, json_encode($data)), 'info');
        }
        $this->post($post_url, $post_arr);
    }

    protected function scan_signed_cdn($list) {
        $post_url = POST_VIRUS_URL . '/index.php?m=Upload&a=Upload';
        $this->log("扫毒接口的url为:" . $post_url, 'info');

        foreach($list as $v) {
            if(class_exists('\CURLFile')) {
                $post_data = array("file_path" => new \CURLFile($v['file_path']),);
            } else {
                $post_data = array("file_path" => '@' . $v['file_path'],);
            }
            $post_data['email_list'] = 'JSON_API_CDN';

            $sign = array_pop(explode('', $v['sign_used']));
            $data = array('list_id' => $v['cron_id'], 'file_md5' => md5_file($v['file_path']), 'status' => 0, 'begin_time' => time(), 'sign' => $sign == NULL ? '' : $sign,);
            M($this->table_detail)->data($data)->add();
            $this->log(sprintf("记录到%s表中的信息为:%s", $this->table_detail, json_encode($data)), 'info');

            //批量扫描cdn上的文件，间隔时间不能太短
            $this->post($post_url, array($post_data));
            usleep(self::POST_TIME_INTERVEL);
        }

    }

    protected function check_scan($list) {
        if(empty($list)) {
            return false;
        }

        $sign_list = array();//需要验证的签名列表
        foreach($list as $v) {
            $sign = array_pop(explode(',', $v['sign_used']));
            if(empty($sign_list[$sign])) {
                $sign_list[$sign] = M('sign_pool')->where("id={$sign}")->find();
            }
        }

        foreach($sign_list as $v) {
            $check_sign_path = CHECK_SIGN_URL_MICROSOFT;
            copy(CHECK_SIGN_URL, $check_sign_path);
            if(!is_file($check_sign_path)){
                $this->log(sprintf("给微软程序加签名,微软程序不存在,路径为%s", $check_sign_path), 'error');
                return false;
            }
            $v['sign_path'] = $this->get_file_path($v['sign_path']);
            $sign_cmd = $this->get_sign_cmd($v['sign_path'], $v['sign_pwd'], $this->sign_method[0], $check_sign_path);
            system($sign_cmd, $ret);
            $this->log(sprintf("给微软程序加签名命令为%s,返回值为:%s", $sign_cmd, $ret), 'info');
            if($ret === 0) {
                $post_url = POST_VIRUS_URL . '/index.php?m=Upload&a=Upload';
                if(class_exists('\CURLFile')) {
                    $post_data = array("file_path" => new \CURLFile($check_sign_path),);
                } else {
                    $post_data = array("file_path" => '@' . $check_sign_path,);
                }
                $post_data['email_list'] = 'JSON_API_SIGN';
                $data = array(
                    'sign_md5' => md5_file($check_sign_path),
                    'sign_pool_id' => $v['id'],
                    'status' => 0,//0=未开始 1=无毒 2=有毒
                    'begin_time' => time(),
                );
                M('check_sign')->data($data)->add();
                $this->log(sprintf("给微软程序加签名,check_sign表增加的数据为", json_encode($data)), 'info');
                $this->post($post_url, array($post_data));
                $this->log(sprintf("给微软程序加签名,给扫毒接口发送数据,url=%s,post_data=%s", $post_url, json_encode($post_data)), 'info');
            }
        }
        return true;
    }

    protected function scan_sign($list){
        //C:\Users\Administrator\Desktop\tool\signtool.exe sign /f C:\Users\Administrator\Desktop\tool\lizhuo1008.pfx /fd sha256 /p worktogether C:\Users\Administrator\Desktop\mssign32.dll
        $this->log("签名池中的最小签名个数(min_sign_email):" . $this->config['min_sign_email'],  'info');
        $email_id_str = '';
        if(!empty($list))    foreach($list as $k => $v){
            $this->log("当前处理的数据，在list表中的信息为:" . json_encode($v),  'info');
            //随机得到签名算法
            $v['sign_method'] = array_rand($this->sign_method);
            $this->log("随机选到的签名算法为:" . $v['sign_method'],  'info');

            //获取未使用的签名
            $this->log("之前已使用过的签名，在签名池中的ID为:" . $v['sign_used'],  'info');
            $sign_list = M('sign_pool')->where('status=0')->select();
            $sign_arr = array();
            foreach($sign_list as $s){
                $sign_arr[$s['id']] = $s;
            }
            //小于等于n(默认为3)个，发报警邮件 不处理这个签名
            if(count($sign_arr) <= $this->config['min_sign_email']){
                $email_id_str = $v['id'] . ',';
                $this->log("小于等于n(默认为3)个，发报警邮件 不处理这个签名",  'info');
                unset($list[$k]);
                continue;
            }

            if(empty($v['confirm_sign'])){
                $sign_key = array_rand($sign_arr);
            }else{
                $sign_key = $v['confirm_sign'];
                $this->log("用户指定的签名ID为:" . $sign_key,  'info');
            }

            $this->log("sign_key:" . json_encode($sign_key),  'info');
            $v['sign_path'] = $sign_arr[$sign_key]['sign_path'];
            $v['sign_path'] = $this->get_file_path($v['sign_path']);
            $v['sign_pwd'] = $sign_arr[$sign_key]['sign_pwd'];

            $v['file_path'] = $this->get_file_path($v['file_path']);
            $new_save_path = str_replace('Sign', 'Unsign', $v['file_path']);
            if(!is_file($new_save_path)) {
                unset($list[$k]);
                $this->log(sprintf("未签名文件的路径不存在，路径为%s", $new_save_path), 'error');
            }elseif(empty($v['sign_path']) || empty(  $v['sign_pwd'])){
                unset($list[$k]);
                $this->log(sprintf("指定的签名已经被停用或者签名池中已经无签名可用了，不处理了,sign_key=%s", $sign_key), 'info');
            }else{
                copy($new_save_path, $v['file_path']);
                $sign_cmd = $this->get_sign_cmd($v['sign_path'], $v['sign_pwd'], $this->sign_method[$v['sign_method']], $v['file_path']);
                $error_info = system($sign_cmd, $ret);
                $this->log(sprintf("签名执行的命令为%s,返回值为%s",$sign_cmd, $ret),  'info');
                if($ret === 0){
                    //记录使用过的签名
                    $sign_used = trim($v['sign_used'] . ',' . $sign_arr[$sign_key]['id'], ',');
                    $data = array(
                        'sign_used' => $sign_used,
                        'sign_method' => $this->sign_method[$v['sign_method']],
                    );

                    if(!empty($v['is_jump'])){
                        $status = STATUS_SIGN_VIRUS_JUMP;
                        unset($list[$k]);
                        $data['status'] = $status;
                        $this->log(sprintf("修改签名后的表状态,id=%s,status=%s", $v['id'], $status),  'info');
                    }

                    M($this->table_list)->where('id='.$v['id'])->data($data)->save();

                }else{
                    unset($list[$k]);
                    $this->log(sprintf("签名失败,签名执行的命令为%s,返回值为%s,error:%s",$sign_cmd, $ret, $error_info),  'error');
                }
            }
        }

        //统一发邮件
        foreach($this->email_list as $k => $email){
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                unset($this->email_list[$k]);
            }
        }

        $this->log(sprintf("邮件列表为%s", json_encode($this->email_list)),  'info');
        $email_id_str = trim($email_id_str, ',');

        if(!empty($email_id_str)){
            $last_send_email_time =  (int)M('send_email_history')->order('id DESC')->limit(1)->getField('addtime');
            $now = time();
            $this->log(sprintf("当前时间:%s,上次邮件发送时间:%s,最小间隔%s", $now, $last_send_email_time, self::SEND_EMAIL_TIME_RANGE),  'info');
            if($now - $last_send_email_time >= self::SEND_EMAIL_TIME_RANGE){
                $this->log(sprintf("发邮件，email:%s,签名池通知,内容为:", $this->email_list, $this->sign_email_body),  'info');
                $this->send_email("签名池通知", $this->sign_email_body, $this->email_list);
                M($this->table_list)->where('id IN ('.$email_id_str . ' )')->data(array(
                    'email_status' => 1,
                ))->save();

                //记录邮件发送历史
                M('send_email_history')->data(array(
                    'content' => $this->sign_email_body,
                    'email_list' => json_encode($this->email_list),
                    'addtime' => time(),
                ))->add();
            }
        }

        return $list;
    }

    protected function up_cdn($list){
        require_once(ROOT_PATH .'Lib/cdn.php');
        $cdn = new \CDN();
        foreach($list as $v){
            $v['file_path'] = $this->get_file_path($v['file_path']);
            $cdn->put_cdn_file($v['file_path']);
            $data = array(
                'status' => STATUS_CDN_UPLOADED,
                'is_jump' => 0,
            );
            $is_up_cdn = (int)M($this->table_list)->where('id=' . $v['id'])->getField('is_up_cdn');
            if(empty($is_up_cdn)){
                $data['is_up_cdn'] = 1;
            }
            M($this->table_list)->where('id=' . $v['id'])->data($data)->save();

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
            curl_setopt($conn[$i], CURLINFO_CONTENT_LENGTH_UPLOAD, strlen($post_data));
            curl_multi_add_handle($mh, $conn[$i]);
            $this->log(sprintf("url为%s,CURL POST数据为%s:", $post_url, json_encode($post_data)),  'info');
        }

        do{
            curl_multi_exec($mh,$active);
        }while($active);

        foreach ($post_arr as $i => $v) {
            $res[$i] = curl_multi_getcontent($conn[$i]);
            $this->log(sprintf("CURL POST返回结果为:%s,error为:%s", $res[$i], curl_error($conn[$i])),  'info');
            curl_close($conn[$i]);
        }

        return $res;
    }

    protected function downloadUSigned($file_list){
        $this->download($file_list);
    }

    protected function CheckUnSign($file_list){
        foreach($file_list as $k => $v){
            $v['save_path'] = $this->get_file_path($v['save_path']);
            if(!is_file($v['save_path']) || filesize($v['save_path']) == 0){
                $this->log(sprintf("这个文件没有下载成功，要删掉。save_path=%s,id=%s,mains_id=%s", $v['save_path'], $v['id']), $v['mains_id'],  'info');
                M('list_new')->where('id='.$v['id'])->delete();
                $connection = sprintf("mysql://%s:%s@%s:%s/%s", C('DB_INS_USER'), C('DB_INS_PWD'), C('DB_INS_HOST'), C('DB_INS_PORT'), C('DB_INS_NAME'));
                $this->log(sprintf("DB_INS_HOST=%s,DB_INS_NAME=%s", C('DB_INS_HOST'), C('DB_INS_NAME')),  'info');

                M('mains', NULL, $connection)->where("id=".$v['mains_id'])->data(array(
                    "sign_status" => MAINS_STATUS_INIT,
                ))->save();
                unset($file_list[$k]);
            }
        }
        return $file_list;
    }

    protected function downloadUnSign($file_list){
        $ggg_domain_url = C('GGG_DOMAIN_URL');
        foreach($file_list as &$v){
            $v['download_url'] = $ggg_domain_url . $v['path'];
        }
        $this->download($file_list);
        return $file_list;
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
            $v['save_path'] = $this->get_file_path($v['save_path']);
            if (!is_dir(dirname($v['save_path']))) {
                mkdir(dirname($v['save_path']), 0755, true);
            }

            $res[$i] = curl_multi_getcontent($conn[$i]);
            $fp = fopen($v['save_path'], 'w');
            fwrite($fp, $res[$i]);
            fclose($fp);
            unset($res[$i]);
            $this->log("下载文件{$i} error:" . curl_error($conn[$i]));
            curl_close($conn[$i]);
        }
        $this->log("下载文件结束",  'info');
    }

    protected function log($log, $level = 'info'){
        $this->log_to_table($log, $level, $this->log_prefix);
    }

    private function get_sign_cmd($sign_path, $sign_pwd, $sign_method, $file_path) {
        $sign_cmd = '';
        if($sign_method == 'signature_normal') {
            $sign_cmd = sprintf("%s sign /f %s /p %s /t %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_URL, $file_path);
        } elseif($sign_method == 'signature_normal_sha256') {
            $sign_cmd = sprintf("%s sign /f %s /fd sha256 /p %s /t %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_URL, $file_path);
        } elseif($sign_method == 'signature_normal_sha384') {
            $sign_cmd = sprintf("%s sign /f %s /fd sha384 /p %s /t %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_URL, $file_path);
        } elseif($sign_method == 'signature_normal_sha512') {
            $sign_cmd = sprintf("%s sign /f %s /fd sha512 /p %s /t %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_URL, $file_path);
        } elseif($sign_method == 'signature_tr') {
            $sign_cmd = sprintf("%s sign /f %s /p %s /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        } elseif($sign_method == 'signature_tr_td_sha256') {
            $sign_cmd = sprintf("%s sign /f %s /fd sha256 /p %s /td sha256 /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        } elseif($sign_method == 'signature_tr_td_sha384') {
            $sign_cmd = sprintf("%s sign /f %s /fd sha384 /p %s /td sha384 /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        } elseif($sign_method == 'signature_tr_td_sha512') {
            $sign_cmd = sprintf("%s sign /f %s /fd sha512 /p %s /td sha512 /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        } elseif($sign_method == 'signature_append_sha256') {
            $sign_cmd = sprintf("%s sign /as /f %s /fd sha512 /p %s /td sha256 /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        } elseif($sign_method == 'signature_append_sha384') {
            $sign_cmd = sprintf("%s sign /as /f %s /fd sha384 /p %s /td sha384 /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        }elseif($sign_method == 'signature_append_sha512') {
            $sign_cmd = sprintf("%s sign /as /f %s /fd sha512 /p %s /td sha512 /tr %s, %s", BASE_SIGN_URL, $sign_path, $sign_pwd, TIMESTAMP_TR_URL, $file_path);
        }
        return $sign_cmd;
    }

    /**
     * 用于发送程序执结果的邮件。
     * @param  [type] $subject   [description]
     * @param  [type] $body      [description]
     * @param  [type] $addresses [description]
     * @return [type]            [description]
     */
    protected function send_email($subject, $body, $addresses, $path = "") {

        if (empty($addresses)) {
            return false;
        }
        require_once(VENDOR_PATH . 'PHPMailer_v5_1/class.phpmailer.php');
        $mail = new \PHPMailer(true);
        $mail->ContentType = 'text/html';
        // 设置PHPMailer使用SMTP服务器发送Email          是否处理2：未处理 1：已处理 ；默认0
        $mail->IsSMTP();
        // 设置邮件的字符编码，若不指定，则为'UTF-8'
        $mail->CharSet = 'UTF-8';
        // 添加收件人地址，可以多次使用来添加多个收件人
        foreach ($addresses as $address) {
            $mail->AddAddress($address);
        }
        if (!empty($path)) {
            if (is_array($path)) {
                foreach ($path as $v) {
                    $mail->AddAttachment($v);
                }
            } else {
                $mail->AddAttachment($path);
            }
        }
        // 设置邮件正文
        $mail->Subject = $subject;
        $mail->Body = $body;
        // 设置SMTP服务器。这里使用网易的SMTP服务器。
        $mail->Host = 'smtp.163.com';
        // 设置为“需要验证”
        $mail->SMTPAuth = true;
        // 设置用户名和密码，即网易邮件的用户名和密码。
        $mail->Username = 'elexauto@163.com';
        $mail->Password = 'elextech%2012';
        $mail->SetFrom('elexauto@163.com', '系统通知');
        return $mail->Send();
    }
}