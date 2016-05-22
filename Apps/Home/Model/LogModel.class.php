<?php
namespace Home\Model;
use Think\Model;

class LogModel extends Model{
	protected $tableName = 'log';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function findAll($pageCount, $type, $level, $begin_time, $end_time){
		$begin_time = strtotime($begin_time);
		$end_time = strtotime($end_time);
		$where = '1=1 ';
		if(!empty($type)){
			$where .= " AND  type = '{$type}'";
		}
		if(!empty($level)){
			$where .= " AND  level = '{$level}'";
		}
		if(!empty($begin_time)){
			$where .= " AND  addtime >= '{$begin_time}'";
		}
		if(!empty($end_time)){
			$where .= " AND  addtime <= '{$end_time}'";
		}

		import('ORG.Util.Page');
		$count = $this->table->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->where($where)->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}
		if(!empty($list)){
			foreach($list as &$v){
				$v['ip'] = long2ip($v['ip']);
				$v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
				$v['content'] = htmlspecialchars($v['content']);
			}
			return array(
				"list" => $list,
				"pagination" => $page->show(),
			);
		}else{
			return array(
				"list" => array(),
				"pagination" => '',
			);
		}
	}

}