<?php
define('UPLOAD_DIR', ROOT_PATH . 'Upload/');
define('DOWNLOAD_URL', ROOT_PATH . 'Download/Cdn');
define('DOWNLOAD_MAIN_URL', ROOT_PATH . 'Download/Unsign/');

//任务运行状态
const TASK_STATUS_INIT = 0;
const TASK_STATUS_PROCESS = 1;
const TASK_STATUS_SUCCESS = 2;
const TASK_STATUS_FAIL = 3;
const TASK_STATUS_JUMP = 4;

//是否签名(1=是,0=否) 是否扫毒(1=是,0=否)
const IS_STATUS_NO = 0;
const IS_STATUS_YES = 1;

//状态
const STATUS_INIT = 0;//尚未开始
const STATUS_PROGRAM_NO_VIRUS = 1;//程序无毒
const STATUS_SIGN = 2;//签名
const STATUS_SIGN_NO_VIRUS =3;//签名无毒
const STATUS_PROGRAM_VIRUS = 4;//程序有毒
const STATUS_SIGN_VIRUS = 5;//签名有毒
const STATUS_SIGN_STILL_VIRUS_NO_CHECK = 6;//签名后依然有毒,需要用微软程序验证签名是否有毒
const STATUS_SIGN_STILL_VIRUS_CHECKED = 7;//确认签名有毒,需要更换签名再次扫描的
