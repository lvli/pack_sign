<?php
namespace Home\Controller;
use Think\Controller;

//获取已经在CDN上的文件 每6个小时执行一次
class CronVirusUploadedController extends CronCommonController {
    protected $table_detail = 'detail_cron';
    protected $table_list = 'list_cron';
    protected $log_prefix = 'cron';

    public function run(){
        $this->init();

        //从CDN上下载要扫毒的文件
        $this->log("从CDN上下载要扫毒的文件",  'info');
        $list = $this->save_list();

        //进行CDN上文件的扫毒
        $this->log("进行CDN上文件的扫毒",  'info');
        $this->scan_signed_cdn($list);

        $this->log("脚本结束运行",  'info');
    }

    private function save_list(){
        $list = M('list_new')->where('status=' . STATUS_CDN_UPLOADED)->select();
        $this->log("从list_new表上获取到的数据为:".json_encode($list),  'info');
        $time = time();
        foreach($list as &$v) {
            $v['save_path'] = str_replace(DOWNLOAD_MAIN_URL, DOWNLOAD_MAIN_SIGN_URL, $v['file_path']);
            $v['download_url'] =  sprintf("https://%s/%s/%s", C('CDN_DOWANLOAD_URL'), C('PUT_CDN_DIR'), basename($v['file_path']));
            M($this->table_list)->data(array('mains_id' => $v['id'], 'file_path' => $v['save_path'], 'status' => 0, 'scan_time' => $time, 'email_status' => 0,))->add();
        }
        $this->downloadUSigned($list);

        return $list;
    }
}