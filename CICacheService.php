<?php

/**
*	CI 数据库缓存类 
*/

class CICacheService
{
	//单例对象
	private static $_instance;

	//总类集合
	protected $_classP;
	
	//缓存对象
	protected $_cache;

	//CI类
	protected $CI;

	//数据库
	protected $_db;

	//禁止克隆
	private function __clone()
	{
		
	}

	/**
	 * 构造函数
	*/
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->driver('cache');
		$this->_db = $this->CI->db;
	}


	/**
	 * 切换db连接
	*/
	public function changeDB($db)
	{
		$this->_db = $db;
	}

	/**
	 * 赋值需要缓存的model (表) 类
	 * @param  string $name model (表)
	 * @return void
	 */

	private function getService( $name )
	{
		if(!isset($this->_classP[$name]))
		{
			$this->_classP[$name] = $this->initServiceClass($name);
		}
	}

	/**
	 * 初始化缓存model (表) 类
	 * @param  string $name model (表)
	 * @return array
	 */
	protected function initServiceClass( $name )
	{
		$serviceArray = array();
		//查询语句
		$serviceArray['cacheName'] = $name;
		//克隆模型
		$this->CI->load->model($name."_model", $name);
		$serviceArray['model'] = $this->CI->$name;
		//查询语句
		return $serviceArray;
	}

	/**
	 * 单调缓存数据
	 * @param  [string] $name 	[model名]
	 * @param  [int] $id   		[主键值]
	 * @param  [string] $field  [字段名]
	 * @return [array]getCacheById
	 */
	public function getCacheById( $name, $id, $field = null)
	{
		//获取服务
		$this->getService($name);

		//主键
		$pk = $this->_classP[$name]['model']->primary_key;

		//获取缓存
		$modelArray = $this->CI->cache->memcached->get($name."_".$pk."_".$id);

		//判断是否有缓存
		if(empty($modelArray))
		{
			//查询字段
			if(!empty($field))
			{
				$this->_db->select($field);
			}

			//数据库查询
			$_d = $this->_db->get_where($name, array($pk => $id));
			$_data = $_d->row_array();

			//存储缓存
			$this->CI->cache->memcached->save($name."_".$pk."_".$id, json_encode($_data), 7200);
		}
		else
		{
			$_data = json_decode($modelArray, true);
		}
		
		return $_data;
	}

	/**
	 * 批量取值
	 * @param  [string] $name 	[model名]
	 * @param  [int] $id   		[主键值]
	 * @param  [string] $field  [字段名]
	 * @return [array]
	 */
	public function getCacheByList( $name, $list, $field = null)
	{
		$list = is_array($list) ? $list : array_map("trim", explode(",", $list));
        foreach ($list as $id) {
            $ret[] = $this->getCacheById($name, $id, $field);
        }
        return $ret;
	}


	/**
	 * 根据主键删除缓存
	 * @param  [string] $name 	[model名]
	 * @param  [int] $id   		[主键值]
	 * @param  [boolean] $deleteDB   		[是否删除数据库数据]
	 */
	public function delCacheById( $name, $id, $deleteDB = false )
	{
		//获取服务
		$this->getService($name);
		
		//取得主键
		$pk = $this->_classP[$name]['model']->primary_key;

		//获取缓存
		$modelArray = $this->CI->cache->memcached->get($name."_".$pk."_".$id);

		//判断是否有缓存
		if( ! empty($modelArray))
		{
			$this->CI->cache->memcached->delete($name."_".$pk."_".$id);
		}
		//判断是否删除数据库数据
		if( $deleteDB == true)
		{
			$this->_db->delete($name, array($pk=>$id));
		}
		return ;
	}

	/**
	 * 保存修改
	 * 
	 * @param  string $name  [model名]
	 * @param  array $map     [查询条件]
	 * @param  string $data    [设置数组]
	 * @param  boolean  $refreshModel [是否要刷新model]
	 * @return array          [结果数组]
	 */
	public function saveWithCache($name, $map, $data = array())
	{
		//获取服务
		$this->getService($name);
		
		//取得主键
		$pk = $this->_classP[$name]['model']->primary_key;
		
		//数据库保存
		$this->_db->where($map);
		$this->_db->update($name, $data);
		$_d = $this->_db->get_where($name, $map)->row_array();
		
		if(!empty($_d))
		{
			//删除原有缓存
			$this->delCacheById($name, $_d[$pk]);
		}
		//返回数据
		return $_d;
	}

}

?>