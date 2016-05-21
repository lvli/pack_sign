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
        //去掉正在处理的数据
        foreach($list as $k => $v){
            $id =  M($this->table_list)->where("mains_id={$v['mains_id']} AND status = 0")->getField('id');
            if(!empty($id)){
                unset($list[$k]);
            }
        }
        $this->log(sprintf("从%s表去掉正在处理的数据以后，剩下的数据%s", $this->table_list, json_encode($list)), 'info');

        $time = time();
        foreach($list as &$v) {
            $v['save_path'] = str_replace('Unsign', 'Sign', $v['file_path']);
            $v['download_url'] =  sprintf("http://%s/%s/%s", C('CDN_DOWANLOAD_URL'), C('PUT_CDN_DIR'), basename($v['file_path']));
            $cron_id = M($this->table_list)->data(array('mains_id' => $v['mains_id'], 'file_path' => $v['save_path'], 'status' => 0, 'scan_time' => $time, 'email_status' => 0,))->add();
            $this->log(sprintf("save_path=%s,download_url=%s", $v['save_path'],  $v['download_url']),  'info');
            $v['cron_id'] = $cron_id;
        }
        $this->downloadUSigned($list);

        return $list;
    }
}