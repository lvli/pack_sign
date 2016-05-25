<?php
define('UPLOAD_DIR', ROOT_PATH . 'Upload/');
define('DOWNLOAD_URL', ROOT_PATH . 'Download/Cdn');
define('DOWNLOAD_MAIN_URL', ROOT_PATH . 'Download/Unsign/');
define('DOWNLOAD_MAIN_SIGN_URL', ROOT_PATH . 'Download/Sign/');
define('CHECK_SIGN_URL', ROOT_PATH . 'tool/explorer.exe');
define('CHECK_SIGN_URL_MICROSOFT', ROOT_PATH . 'Download/Check/explorer.exe');
define('BASE_SIGN_URL', ROOT_PATH . 'tool/signtool.exe');
define('TIMESTAMP_URL',  'http://timestamp.verisign.com/scripts/timstamp.dll');
define('TIMESTAMP_TR_URL',  'http://timestamp.comodoca.com/rfc3161');
define('POST_VIRUS_URL',  'http://scanallfiles.com');

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
const STATUS_CDN_UPLOADED = 8;//新文件已上传CDN
const STATUS_CRON_DEAL = 9;//定时任务处理中
const STATUS_PROGRAM_VIRUS_JUMP = 10;//跳过程序扫毒步骤开始签名
const STATUS_SIGN_VIRUS_JUMP = 11;//跳过签名扫毒步骤开始上传CDN

//mains表状态
const MAINS_STATUS_INIT = 0;//初始化;
const MAINS_STATUS_DEAL = 1;//处理中
const MAINS_STATUS_PROGRAM_VIRUS = 2;//程序有毒
const MAINS_STATUS_SIGN_VIRUS = 3;//签名有毒
const MAINS_STATUS_UPLOADED_CDN = 4;//已上传CDN

