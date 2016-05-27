<?php
namespace Home\Model;
use Think\Model;

class ActionModel extends Model{
	protected $tableName = 'action_log';
	protected  $table;

	function __construct() {
		parent::__construct();
		$this->table = M($this->tableName);
	}

	function findAll($pageCount, $begin_time, $end_time){
		$begin_time = strtotime($begin_time);
		$end_time = strtotime($end_time);
		$where = '1=1 ';

		if(!empty($begin_time)){
			$where .= " AND  addtime >= '{$begin_time}'";
		}
		if(!empty($end_time)){
			$where .= " AND  addtime <= '{$end_time}'";
		}

		import('ORG.Util.Page');
		$count = $this->table->where($where)->count();
		if(!empty($count)){
			$page = new \Org\Util\Page($count, $pageCount);
			$list = $this->table->where($where)->order('id DESC')->limit($page->firstRow . ',' . $page->listRows)->select();
		}

		if(!empty($list)){
			$admin_arr = array();
			foreach($list as &$v){
				$v['ip'] = long2ip($v['ip']);
				$v['addtime'] = date('Y-m-d H:i:s', $v['addtime']);
				if(isset($admin_arr[$v['admin_id']])){
					$v['admin'] = $admin_arr[$v['admin_id']];
				}else{
					$v['admin'] = M('admin')->where("id={$v['admin_id']}")->getField('name');
					if(empty($v['admin'])){
						$v['admin'] = '未知';
					}
					$admin_arr[$v['admin_id']] = $v['admin'];
				}
				if($v['post'] == '[]'){
					$v['post'] = '';
				}
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