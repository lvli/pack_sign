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

        //用微软的程序验证签名本身是否有毒
        $list = $this->get_list(STATUS_SIGN_STILL_VIRUS_NO_CHECK);
        $this->check_scan($list);

        //确认签名有毒,需要更换签名再次扫描的
        $this->log("签名后有毒的文件，需要更换签名再次扫描的",  'info');
        $list = $this->get_list(STATUS_SIGN_STILL_VIRUS_CHECKED);
        $this->scan_sign($list);
        $this->scan_virus($list);

        //签名之后没有问题，上传CDN
        $this->log("签名之后没有问题的文件，上传CDN",  'info');
        $list =  $this->get_list(STATUS_SIGN_NO_VIRUS);
        $this->up_cdn($list);

        $this->log("脚本结束运行",  'info');
    }

    private function save_list(){
        $CDN = new Main();
        $file_list = $CDN->mlist();//TODO
        $this->log("从CDN上获取到的文件列表为:" . json_encode($file_list),  'info');

        $time = time();
        $download_url = sprintf("%s/%s/%s/%s/", DOWNLOAD_URL, date('Y'), date('m'), date('d'), $time);
        $this->download($download_url, $file_list);

        if(!empty($file_list))    foreach($file_list as $v){
            M('list_cron')->data(array(
                'url' => '',//TODO
                'file_path' => $download_url .basename($v['name']),//TODO
                'status' => 0,
                'scan_time' => $time,
                'is_sign' => '',//TODO
                'email_status' => 0,
            ))->add();
        }
        return $file_list;
    }
}