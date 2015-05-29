<?php

class CommonAction extends Action{
	public function _initialize(){
		//初始化的时候检查用户权限
// 		if(!isset($_SESSION['USER_AUTH_KEY']) || $_SESSION['USER_AUTH_KEY']!=C('USER_AUTH_KEY')){
// 			$this->redirect('Home/Login/sign');
// 		}
	}
	
	//判断活动是否结束
	public function isGameEnd(){
		$cur_datetime= date('Y-m-d H:i:s');
		$Config = M('Config');
		$cfg_game_star_time = $Config->where("varname='cfg_game_star_time'")->getField('value');
		$cfg_game_end_time = $Config->where("varname='cfg_game_end_time'")->getField('value');
		
		if(strtotime($cur_datetime)>strtotime($cfg_game_star_time) && strtotime($cur_datetime)<strtotime($cfg_game_end_time)){
			
			return false;//还没结束
		}else{
			
			return true;
		}
	}
	
	
	function randomkeys($length){
		$key="";
		$pattern='1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
	
		for($i=0;$i<$length;$i++)
		{
		$key .= $pattern{mt_rand(0,62)};    //生成php随机数
		}
		return $key;
	}
	
	//生成SN码
	public function generalSNKey(){
		//return 'DKS' . '-' . $this->randomkeys(4). '-' . $this->randomkeys(4). '-' . $this->randomkeys(8);
		return $this->randomkeys(8);
	}
	
}