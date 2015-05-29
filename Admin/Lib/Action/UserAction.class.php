<?php
	ini_set("memory_limit","256M");
	class UserAction extends CommonAction{
		
		public function doExchangeAjax(){
			$snkey = $_POST['snkey'];
			$Lottery = M('Lottery');
			$s = $Lottery->where("sn_code='".$snkey."'")->find();
			$uid = $_SESSION['id'];
			
			$User = M('Admin');
			$udata = $User->find($uid);
				
			$currTime = date("Y-m-d H:i:s");
			$s['operadmin'] = $udata['username'];
			$s['exchangedate'] = $currTime;
			$s['exchangeshop'] = $udata['shopname'];
			$s['stat'] = 1;
				
			$lastId = $Lottery->save($s);
				
			/* $Exchange=M('Exchange');
			$data['exchangenote'] = "由门店执行".$_SESSION['username']."兑奖";
			$data['operationadmin'] = $_SESSION['username'];
			$data['exchangedatetime'] = $currTime;
			$data['uid'] = $uid;
		
			$lastId=$Exchange->add($data); */
		
			if($lastId){
				echo json_encode(array('status'=>'ok','message'=>'恭喜，兑换操作成功！'));
				exit;
	
			} else {
		
				echo json_encode(array('status'=>'ng','message'=>'抱歉，兑换操作失败！'));
				exit;
		
			}
		}
		
		public function checkSnkey() {
			$Lottery = M('Lottery');
			$result = $Lottery->where("sn_code is not null and sn_code='".$_POST['snkey']."'")->find();
			if ($result && count($result) > 0) {
				if (!empty($result['operadmin']) || $result['operadmin'] != '') {
					echo json_encode(array('status'=>'yjdh','message'=>'该sn码于'.$result['exchangedate'].'由门店'.$result['exchangeshop'].'兑换！'));
					exit;
				} else {
						
					// 查询奖品
					$Gift = M('Gift');
					$g = $Gift->find($result['gid']);
					if ($g && count($g) > 0) {
						$giftname = $g['gift_name'];
					}
						
					echo json_encode(array('status'=>'ok','message'=>'该sn码有效，可以兑换'.$giftname.'！'));
					exit;
						
				}
		
			} else {
				echo json_encode(array('status'=>'ng','message'=>'该sn码不存在，请重新输入！'));
				exit;
			}
				
		}
		
		
		public function userInfo(){
			
			$User = M('User'); // 实例化User对象
			import('ORG.Util.Page');// 导入分页类
			$count      = $User->count();// 查询满足要求的总记录数
			$Page       = new Page($count,C('USER_PAGE_COUNT'));// 实例化分页类 传入总记录数和每页显示的记录数
			$show       = $Page->show();// 分页显示输出
			// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
			$list = $User->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			$this->assign('data',$list);// 赋值数据集
			$this->assign('page',$show);// 赋值分页输出
			$this->display(); // 输出模板
		}
		
		public function searchUser(){
			$map=array();
			if(!empty($_GET['username'])){
				$map['username'] = array('like','%'.$_GET['username'].'%');
			}
			if(!empty($_GET['userphone'])){
				$map['userphone'] = array('like','%'.$_GET['userphone'].'%');
			}

			$parameter = 'username='.urlencode($_GET['username']).'&userphone='.urlencode($_GET['userphone']);
			
			$User = M('User'); // 实例化User对象
			import('ORG.Util.Page');// 导入分页类
			$count      = $User->where($map)->count();// 查询满足要求的总记录数
			$Page       = new Page($count,C('USER_PAGE_COUNT'),$parameter);// 实例化分页类 传入总记录数和每页显示的记录数
			$show       = $Page->show();// 分页显示输出
			// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
			$list = $User->where($map)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
			$this->assign('data',$list);// 赋值数据集
			$this->assign('page',$show);// 赋值分页输出
			$this->display('userInfo'); // 输出模板
			
		}
		
		/**
		 *
		 * 导出 USER Excel
		 */
		function exportUserData(){//导出Excel

			$xlsName  = "用户数据表";
			$xlsCell  = array(
					array('index','序号'),
					array('username','用户名称'),
					array('userphone','用户手机'),
					array('regtime','注册时间')
		
			);
			$xlsModel = M('User');
			
			$map=array();
			if($_GET['username']!='blank'){
				$map['username'] = array('like','%'.$_GET['username'].'%');
			}
			if($_GET['userphone']!='blank'){
				$map['userphone'] = array('like','%'.$_GET['userphone'].'%');
			}
			
			$xlsData  =$xlsModel->where($map)->Field('username,userphone,regtime')->order('id desc')->select();
			
			$i=1;
			foreach ($xlsData as $k => $v)
			{	
				$xlsData[$k]['index']=$i;
				$i++;
			}
			
			$this->exportExcel($xlsName,$xlsCell,$xlsData);
		
		}
		
		public function topNInfo(){
				
			$queryStr ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.score as score,s.id as id,s.joindate as joindate,u.headimgurl as headimgurl from tp_user u join tp_score s on u.id=s.uid";
				
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
			$queryResult = $Model->query($queryStr);
				
			if($queryResult!=null){
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,60);// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$queryStr1 ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.score as score,s.id as id,s.joindate as joindate,u.headimgurl as headimgurl from tp_user u join tp_score s on u.id=s.uid order by s.score desc limit ".$Page->firstRow.",".$Page->listRows;
		
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
		
			}
				
			unset($queryResult);
			$this->display();
				
		}
		
		public function searchTopN(){
				
			$where=" where 1=1";
		
			if(!empty($_GET['startdate'])){
					
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
		
			if(!empty($_GET['enddate'])){
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
				
			if(!empty($_GET['username'])){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
				
			if(!empty($_GET['userphone'])){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
				
			$queryStr ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.id as id,s.score as score,s.joindate as joindate,u.headimgurl as headimgurl from tp_user u join tp_score s on u.id=s.uid".$where;
				
				
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
				
			$queryResult = $Model->query($queryStr);
				
				
			if($queryResult!=null){
		
				//带入搜索参数
				$parameter = 'userphone='.urlencode($_GET['userphone']).'&startdate='.urlencode($_GET['startdate']).'&enddate='.urlencode($_GET['enddate']).'&username='.urlencode($_GET['username']).'&userphone='.urlencode($_GET['userphone']);
		
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,60,$parameter);// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$where.=" order by s.score desc limit ".$Page->firstRow.",".$Page->listRows;
				$queryStr1 ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.id as id,s.score as score,s.joindate as joindate,u.headimgurl as headimgurl from tp_user u join tp_score s on u.id=s.uid".$where;
		
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
		
			}
				
			unset($queryResult);
			$this->display('topNInfo'); // 输出模板
		}
		
		/**
		 *
		 * 导出 SCORE Excel
		 */
		function exportTopNData(){//导出Excel
		
			$xlsName  = "用户排名统计表";
			$xlsCell  = array(
					array('joindate','游戏时间'),
					array('username','用户名称'),
					array('userphone','用户手机'),
					array('score','用户成绩')
		
			);
			$xlsModel = new Model();
		
			$where=" where 1=1";
		
			if($_GET['startdate']!='blank'){
					
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
		
			if($_GET['enddate']!='blank'){
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
				
			if($_GET['username']!='blank'){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
				
			if($_GET['userphone']!='blank'){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
		
			$where.=" order by s.score desc";
			$queryStr1 ="select u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.score as score,s.joindate as joindate from tp_user u join tp_score s on u.id=s.uid".$where;
		
			$xlsData = $xlsModel->query($queryStr1);
		
			$this->exportExcel($xlsName,$xlsCell,$xlsData);
		
		}
		
		
		
		public function scoreInfo(){
			
			$queryStr ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.score as score,s.id as id,s.joindate as joindate from tp_user u join tp_score s on u.id=s.uid";
			
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
			$queryResult = $Model->query($queryStr);
			
			if($queryResult!=null){
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,C('SCORE_PAGE_COUNT'));// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$queryStr1 ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.score as score,s.id as id,s.joindate as joindate,u.headimgurl from tp_user u join tp_score s on u.id=s.uid order by s.joindate desc limit ".$Page->firstRow.",".$Page->listRows;
				
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
						
			}
			
			unset($queryResult);
			$this->display();
			
		}
		
		public function searchScore(){
			
			$where=" where 1=1";

			if(!empty($_GET['startdate'])){
			
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
				
			if(!empty($_GET['enddate'])){
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
			
			if(!empty($_GET['username'])){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
			
			if(!empty($_GET['userphone'])){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
			
			$queryStr ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.id as id,s.score as score,s.joindate as joindate from tp_user u join tp_score s on u.id=s.uid".$where;
			
			
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
			
			$queryResult = $Model->query($queryStr);
			
			
			if($queryResult!=null){
				
				//带入搜索参数
				$parameter = 'userphone='.urlencode($_GET['userphone']).'&startdate='.urlencode($_GET['startdate']).'&enddate='.urlencode($_GET['enddate']).'&username='.urlencode($_GET['username']).'&userphone='.urlencode($_GET['userphone']);
				
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,C('SCORE_PAGE_COUNT'),$parameter);// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$where.=" order by s.joindate desc limit ".$Page->firstRow.",".$Page->listRows;
				$queryStr1 ="select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.id as id,s.score as score,s.joindate as joindate,u.headimgurl from tp_user u join tp_score s on u.id=s.uid".$where;
				
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
				
			}
			
			unset($queryResult);
			$this->display('scoreInfo'); // 输出模板
		}
		
		/**
		 *
		 * 导出 SCORE Excel
		 */
		function exportScoreData(){//导出Excel

			$xlsName  = "用户游戏成绩表";
			$xlsCell  = array(
					array('joindate','游戏时间'),
					array('username','用户昵称'),
					array('userphone','用户手机'),
					array('score','用户成绩')
		
			);
			$xlsModel = new Model();
				
			$where=" where 1=1";

			if($_GET['startdate']!='blank'){
			
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
				
			if($_GET['enddate']!='blank'){
				$where.=" and DATE_FORMAT( s.joindate,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
			
			if($_GET['username']!='blank'){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
			
			if($_GET['userphone']!='blank'){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
				
			$where.=" order by s.joindate desc";
			$queryStr1 ="select u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,s.score as score,s.joindate as joindate from tp_user u join tp_score s on u.id=s.uid".$where;

			$xlsData = $xlsModel->query($queryStr1);

			$this->exportExcel($xlsName,$xlsCell,$xlsData);
		
		}
		
		public function exchangeGift(){
			$uid = $_GET['uid'];
			$User = M('User');
			$username = $User->where('id='.$uid)->getField('username');
			
			$Score = M('Score');
			$sdata = $Score->find($_GET['gid']);
			$this->assign('sdata',$sdata);

			$this->assign('exchange_user_name',$username);
			$this->assign('exchange_user_id',$uid);
			$this->display();
		}
		
		public function doExchange(){
			$Exchange=M('Exchange');
			
			$Exchange->create();
			
			$Exchange->operationadmin=$_SESSION['username'];
			$Exchange->exchangedatetime=date("Y-m-d H:i:s");
			$lastId=$Exchange->add();
			
			if($lastId){
				//更新
				$User = M("User"); // 实例化User对象
				// 更改用户的兑换状态值
				$User-> where('id='.$_POST['uid'])->setField('is_exchange', 2);
				
				$this->success('兑换操作成功','exchangeInfo');
			}else{
				$this->error('兑换操作失败');
			}
		}
		
		
		
		public function zjlbInfo(){
				
			$queryStr ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id
					ORDER BY l.lottery_date DESC";
				
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
			$queryResult = $Model->query($queryStr);
				
			if($queryResult!=null){
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,C('SCORE_PAGE_COUNT'));// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$queryStr1 ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id
					ORDER BY l.lottery_date DESC limit ".$Page->firstRow.",".$Page->listRows;
		
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
		
			}
				
			unset($queryResult);
			$this->display();
				
		}
		
		public function searchZjlb(){
				
			$where="";
		
			if(!empty($_GET['startdate'])){
					
				$where.=" and DATE_FORMAT( l.lottery_date,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
		
			if(!empty($_GET['enddate'])){
				$where.=" and DATE_FORMAT( l.lottery_date,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
				
			if(!empty($_GET['username'])){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
				
			if(!empty($_GET['userphone'])){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
				
			$queryStr ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id".$where;
				
				
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
				
			$queryResult = $Model->query($queryStr);
				
				
			if($queryResult!=null){
		
				//带入搜索参数
				$parameter = 'userphone='.urlencode($_GET['userphone']).'&startdate='.urlencode($_GET['startdate']).'&enddate='.urlencode($_GET['enddate']).'&username='.urlencode($_GET['username']).'&userphone='.urlencode($_GET['userphone']);
		
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,C('SCORE_PAGE_COUNT'),$parameter);// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$where.=" order by l.lottery_date desc limit ".$Page->firstRow.",".$Page->listRows;
				$queryStr1 ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id".$where;
		
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
		
			}
				
			unset($queryResult);
			$this->display('zjlbInfo'); // 输出模板
		}
		
		
		/**
		 *
		 * 导出 中奖列表 Excel
		 */
		function exportZjlbData(){//导出Excel
		
			$xlsName  = "用户中奖列表";
			$xlsCell  = array(
					array('index','序号')	,
					array('username','用户昵称'),
					array('userphone','用户手机'),
					array('gift_name','奖品名称'),
					array('lottery_date','中奖日期'),
					array('stat','兑奖状态'),
					array('exchangeshop','兑奖门店'),
					array('exchangedate','兑奖日期'),
			);
			
			$xlsModel = new Model();
			
			$where="";
			
			if($_GET['startdate']!='blank'){
					
				$where.=" and DATE_FORMAT( l.lottery_date,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
			
			if($_GET['enddate']!='blank'){
				$where.=" and DATE_FORMAT( l.lottery_date,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
				
			if($_GET['username']!='blank'){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
				
			if($_GET['userphone']!='blank'){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
			
			$where.=" order by l.lottery_date desc";
			$queryStr1 ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
						l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
						WHERE l.uid = u.id AND l.gid = g.id".$where;
			
			$xlsData = $xlsModel->query($queryStr1);
			
			
			$i=1;
			foreach ($xlsData as $k => $v)
			{
				if ($xlsData[$k]['stat'] == '0') {
					$xlsData[$k]['stat'] = '未兑奖';
				} else {
					$xlsData[$k]['stat'] = '已兑奖';
				}
				
				$xlsData[$k]['index']=$i;
				$i++;
			}
				
			$this->exportExcel($xlsName,$xlsCell,$xlsData);
		
		}
		
		
		
		
		public function yhdjInfo(){
		
			$roleCode = 'level_2_admin';
			$roleWhere = '';
			if ($_SESSION['role'] == $roleCode) {
				$roleWhere = " and l.operadmin='".$_SESSION['username']."'";
			}
			
			$queryStr ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id".$roleWhere;
		
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
			$queryResult = $Model->query($queryStr);
		
			if($queryResult!=null){
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,C('SCORE_PAGE_COUNT'));// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$queryStr1 ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id".$roleWhere
					." ORDER BY l.lottery_date DESC limit ".$Page->firstRow.",".$Page->listRows;
		
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
		
			}
		
			unset($queryResult);
			$this->display();
		
		}
		
		public function searchYhdj(){
		
			$roleCode = 'level_2_admin';
			$roleWhere = '';
			if ($_SESSION['role'] == $roleCode) {
				$roleWhere = " and l.operadmin='".$_SESSION['username']."'";
			}
			
			$where="";
		
			if(!empty($_GET['startdate'])){
					
				$where.=" and DATE_FORMAT( l.lottery_date,  '%Y-%m-%d' ) >='".date("Y-m-d",strtotime($_GET['startdate']))."'";
			}
		
			if(!empty($_GET['enddate'])){
				$where.=" and DATE_FORMAT( l.lottery_date,  '%Y-%m-%d' ) <='".date("Y-m-d",strtotime($_GET['enddate']))."'";
			}
		
			if(!empty($_GET['username'])){
				$where.=" and u.username like '%".$_GET['username']."%'";
			}
		
			if(!empty($_GET['userphone'])){
				$where.=" and u.userphone like '%".$_GET['userphone']."%'";
			}
		
			$queryStr ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id".$roleWhere.$where;
		
		
			$Model = new Model(); // 实例化一个model对象 没有对应任何数据表
		
			$queryResult = $Model->query($queryStr);
		
		
			if($queryResult!=null){
		
				//带入搜索参数
				$parameter = 'userphone='.urlencode($_GET['userphone']).'&startdate='.urlencode($_GET['startdate']).'&enddate='.urlencode($_GET['enddate']).'&username='.urlencode($_GET['username']).'&userphone='.urlencode($_GET['userphone']);
		
				import('ORG.Util.Page');// 导入分页类
				$count      = count($queryResult);// 查询满足要求的总记录数
				$Page       = new Page($count,C('SCORE_PAGE_COUNT'),$parameter);// 实例化分页类 传入总记录数和每页显示的记录数
				$show       = $Page->show();// 分页显示输出
				// 进行分页数据查询 注意limit方法的参数要使用Page类的属性
				$where.=" order by l.lottery_date desc limit ".$Page->firstRow.",".$Page->listRows;
				$queryStr1 ="SELECT l.id,u.username,u.headimgurl,u.userphone,g.gift_name,l.lottery_date,l.stat,l.operadmin,l.opernote,l.exchangedate,
					l.exchangeshop FROM tp_lottery l,tp_user u,tp_gift g
					WHERE l.uid = u.id AND l.gid = g.id".$roleWhere.$where;
		
				$list = $Model->query($queryStr1);
				$this->assign('data',$list);// 赋值数据集
				$this->assign('page',$show);// 赋值分页输出
		
			}
		
			unset($queryResult);
			$this->display('yhdjInfo'); // 输出模板
		}
		
		
		
	}
?>
