<?php
namespace Home\Controller;
use Think\Controller;

//获取新上传的文件 每1分钟执行一次
class CronVirusController extends CronCommonController {
    protected $table_detail = 'detail_new';
    protected $table_list = 'list_new';
    protected $log_prefix = 'cronnew';

    public function run(){
        $this->init();

        //从mains数据库获取要扫毒的文件
        $this->log("从mains数据库获取新上传的文件",  'info');
        $file_list = $this->save_list();

        $this->log("开始下载文件",  'info');
        $file_list = $this->downloadUnSign($file_list);
        $this->log("下载文件结束",  'info');

        $this->log("开始检查下载文件是否正常",  'info');
        $file_list = $this->CheckUnSign($file_list);
        $this->log("检查下载文件是否正常结束",  'info');

        $this->log("复制未签名的文件",  'info');
        $this->CopyUnSign($file_list);
        $this->log("复制未签名文件结束",  'info');

        //进行未签名的扫毒
        $this->log("进行未签名文件的扫毒",  'info');
        $list = $this->get_list(STATUS_INIT);
        $list = $this->init_list($list);
        $this->scan_virus($list, true);

        //签名,然后扫毒
        $this->log("签名,然后扫毒",  'info');
        $list = $this->get_list(STATUS_PROGRAM_NO_VIRUS);
        $list_jump = $this->get_list(STATUS_PROGRAM_VIRUS_JUMP);
        $list = array_merge($list, $list_jump);
        $list = $this->scan_sign($list);
        $this->scan_virus($list);

        //用微软的程序验证签名本身是否有毒
        $list = $this->get_list(STATUS_SIGN_STILL_VIRUS_NO_CHECK);
        $this->check_scan($list);

        //确认签名有毒,需要更换签名再次扫描的
        $this->log("确认签名有毒,需要更换签名再次扫描的",  'info');
        $list = $this->get_list(STATUS_SIGN_STILL_VIRUS_CHECKED);
        $list = $this->scan_sign($list);
        $this->scan_virus($list);

        //签名之后没有问题，上传CDN
        $this->log("签名之后没有问题的文件，上传CDN",  'info');
        $list =  $this->get_list(STATUS_SIGN_NO_VIRUS);
        $list_jump = $this->get_list(STATUS_SIGN_VIRUS_JUMP);
        $list = array_merge($list, $list_jump);
        //再检查一下，是否签名了
        $list = $this->check_sign($list);
        $this->up_cdn($list);

        //清除ggg平台删掉的文件
        $this->log("清除ggg平台删掉的文件",  'info');
        $this->sync_ggg_del();

        $this->log("脚本结束运行",  'info');
    }

    private function save_list(){
        $connection = sprintf("mysql://%s:%s@%s:%s/%s", C('DB_INS_USER'), C('DB_INS_PWD'), C('DB_INS_HOST'), C('DB_INS_PORT'), C('DB_INS_NAME'));
        $this->log(sprintf("DB_INS_HOST=%s,DB_INS_NAME=%s", C('DB_INS_HOST'), C('DB_INS_NAME')),  'info');

        $file_list = M('mains', NULL, $connection)->where('status=1 AND signed=0 AND sign_status=0')->field('id,path,ver,description')->select();
        M('mains', NULL, $connection)->where('status=1 AND signed=0 AND sign_status=0')->data(array(
            "sign_status" => MAINS_STATUS_DEAL,
        ))->save();
        $this->log("从mains数据表获取到的新上传文件:" . json_encode($file_list),  'info');

        $time = time();
        if(!empty($file_list))    foreach($file_list as &$v){
            $v['path'] = str_replace('/var/app/ins_upload', '', $v['path']);
            if(strpos($v['path'], '/') !== 0){
                $v['path'] = '/' . $v['path'];
            }

            $v['save_path'] = DOWNLOAD_MAIN_SIGN_URL . $v['path'];
            $v['save_path'] = str_replace('//', '/', $v['save_path']);
            $v['save_path'] = str_replace('\\', '/', $v['save_path']);
            M('list_new')->data(array(
                'mains_id' => $v['id'],
                'file_path' =>  $v['save_path'],
                'file_name' =>  basename($v['save_path']),
                'ver' =>  $v['ver'],
                'description' =>  $v['description'],
                'status' => 0,
                'scan_time' => $time,
                'email_status' => 0,
            ))->add();
        }
        return $file_list;
    }

    private function CopyUnSign($file_list){
        foreach($file_list as $v){
            $v['save_path'] = $this->get_file_path($v['save_path']);
            $new_save_path = str_replace('Sign', 'Unsign', $v['save_path']);
            if(!is_dir(dirname($new_save_path))){
                mkdir(dirname($new_save_path), 0755, true);
            }
            $ret = copy($v['save_path'], $new_save_path);
            if(!$ret || !is_file($new_save_path)){
                $this->log(sprintf("文件复制失败,原路径=%s,新路径=%s", $v['save_path'], $new_save_path),  'error');
            }
        }
    }

    private function sync_ggg_del(){
        $connection = sprintf("mysql://%s:%s@%s:%s/%s", C('DB_INS_USER'), C('DB_INS_PWD'), C('DB_INS_HOST'), C('DB_INS_PORT'), C('DB_INS_NAME'));
        $this->log(sprintf("DB_INS_HOST=%s,DB_INS_NAME=%s", C('DB_INS_HOST'), C('DB_INS_NAME')),  'info');

        $file_list = M('mains', NULL, $connection)->where('status=0 AND signed=0')->field('id,path')->select();
        $id_arr = array();
        foreach($file_list as $v){
            $id_arr[] = $v['id'];
        }
        $id_str = trim(implode(',', $id_arr));
        if(!empty($id_str)){
            M('list_new')->where("mains_id IN ($id_str)")->delete();
            $this->log("删掉list_new表中的记录ID为:{$id_str}",  'info');
        }
    }

    private function check_sign($list){
        foreach($list as $k => $v){
            $v['file_path'] = $this->get_file_path($v['file_path']);
            $new_save_path = str_replace('Sign', 'Unsign', $v['file_path']);
            $unsign_filesize = filesize($new_save_path);
            $sign_filesize = filesize($v['file_path']);
            $unsign_filemtime = filemtime($new_save_path);
            $sign_filemtime = filemtime($v['file_path']);
            if($unsign_filesize == $sign_filesize || $unsign_filemtime == $sign_filemtime){
                unset($list[$k]);
                $this->log(sprintf("list_new表中的记录ID为:%s发生异常,重新处理,unsign_filesize=%s,sign_filesize=%s,unsign_filemtime=%s,sign_filemtime=%s", $v['id'], $unsign_filesize, $sign_filesize, $unsign_filemtime, $sign_filemtime),  'error');
                M('list_new')->data(array(
                    'status' => STATUS_INIT,
                    'sign_used' => '',
                    'sign_method' => '',
                ))->where("id={$v['id']}")->save();
            }
        }
        return $list;
    }
}