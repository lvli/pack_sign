<?php
//任务运行状态
const TASK_STATUS_INIT = 0;
const TASK_STATUS_PROCESS = 1;
const TASK_STATUS_SUCCESS = 2;
const TASK_STATUS_FAIL = 3;
const TASK_STATUS_JUMP = 4;

//是否签名(1=是,0=否) 是否扫毒(1=是,0=否)
const IS_STATUS_NO = 0;
const IS_STATUS_YES = 1;
