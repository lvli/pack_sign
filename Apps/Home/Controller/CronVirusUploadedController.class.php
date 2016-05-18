<?php
namespace Home\Controller;
use Think\Controller;

//获取已经在CDN上的文件 每6个小时执行一次
class CronVirusUploadedController extends CronCommonController {
    protected $table_detail = 'detail_cron';
    protected $table_list = 'list_cron';
    protected $log_prefix = 'cron_uploaded_';

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
        $list = M('list_new')->where('status=' . MAINS_STATUS_UPLOADED_CDN)->select();
        $time = time();
        foreach($list as $v) {
            M($this->table_list)->data(array('mains_id' => $v['id'], 'file_path' => DOWNLOAD_MAIN_URL . $v['path'], 'status' => 0, 'scan_time' => $time, 'email_status' => 0,))->add();
        }
        $this->downloadUSigned($list);

        return $list;
    }
}