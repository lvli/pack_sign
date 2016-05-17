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
        $list = $this->get_list(STATUS_SIGN_STILL_VIRUS);
        $this->scan_sign($list);
        $this->scan_virus($list);

        //签名之后没有问题，上传CDN
        $this->log("签名之后没有问题的文件，上传CDN",  'info');
        $list =  $this->get_list(STATUS_SIGN_NO_VIRUS);
        $this->up_cdn($list);

        $this->log("脚本结束运行",  'info');
    }

    private function save_list(){
        $file_list = array();//TODO
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

}