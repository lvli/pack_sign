<?php
namespace Home\Controller;
use Think\Controller;

//定时发送扫毒邮件
class CronSendAlarmController extends CronCommonController {
    protected $table_detail = 'detail_new';
    protected $table_list = 'list_new';
    protected $log_prefix = 'send';

    public function run(){
        $this->init();

        //程序报毒
        $this->log("获取程序报毒的文件",  'info');
        $list = $this->get_list(STATUS_PROGRAM_VIRUS);
        $program_content = $this->get_virus_result($list);

        //签名报毒
        $this->log("获取签名报毒的文件",  'info');
        $list = $this->get_list(STATUS_SIGN_VIRUS);
        $sign_content = $this->get_virus_result($list);

        //发邮件
        $this->email($program_content, $sign_content);

        $this->log("脚本结束运行",  'info');
    }

    private function get_virus_result($list){
        if(empty($list)){
            return '';
        }

        $result = '';
        foreach($list as $v){
            $virus_result = M('detail_new')->where("list_id={$v['id']} AND virus_result<>''")->order('id DESC')->limit(1)->getField('virus_result');
            if(!empty($virus_result)){
                $virus_result = json_decode($virus_result, true);
                $virus_engine = array();
                foreach($virus_result['av_status'] as $k => $u){
                    if( $u['status'] != 0){
                        $virus_engine[] = $k;
                    }
                }
                if(!empty($virus_engine)){
                    //ID_名称  报毒杀软
                    $result[] = array(
                        'id' => $v['id'],
                        'name' => basename($v['file_path']),
                        'virus_engine' => $virus_engine,
                    );
                }
            }
        }
        $result = $this->format_virus_result($result);
        return $result;
    }

    private function format_virus_result($list){
        if(empty($list)){
            return '';
        }

        $content = '<table border="1">';
        $content .= "<tr><th>ID</th><th>名称</th><th>报毒杀软</th></tr>";
        foreach($list as $k => $v){
            $content .= sprintf("<tr><td>%s</td><td>%s</td><td>%s</td></tr>", $v['id'], $v['name'], implode(',', $v['virus_engine']));
        }
        $content .= '</table>';
        return $content;
    }

    private function email($program_content, $sign_content){
        $email_body = '';
        if(!empty($program_content)){
            $email_body = '程序报毒的文件:<br/>';
            $email_body .= $program_content;
        }
        if(!empty($sign_content)){
            $email_body .= '<br/><hr>签名报毒的文件:<br/>';
            $email_body .= $sign_content;
        }
        if(!empty($email_body)){
            foreach($this->email_list as $k => $v){
                if(!filter_var($v, FILTER_VALIDATE_EMAIL)){
                    unset($this->email_list[$k]);
                }
            }
            $this->log(sprintf("发邮件，email:%s,自动扫毒通知,内容为:", $this->email_list, $email_body),  'info');
            if(!empty($this->email_list)){
                $this->send_email("自动扫毒通知", $email_body, $this->email_list);
            }
        }
    }
}