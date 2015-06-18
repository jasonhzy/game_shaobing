<?php

define("IN_SYS", true);

class IndexAction extends CommonAction {
	
	
	public function voice() {
		$Config = M('Config');
		$cfg_appid = $Config->where("varname='cfg_appid'")->getField('value');
		$cfg_screct = $Config->where("varname='cfg_screct'")->getField('value');
		
		// 获取分享的签名 开始
		vendor("WeixinShare.jssdk");
		$jssdk = new JSSDK($cfg_appid, $cfg_screct);
		$signPackage = $jssdk->GetSignPackage();
		$this->assign('signPackage',$signPackage);
		// 获取分享的签名 结束
		
		$this->display();
	}
	
	public function doLottery() {
		if (!defined('IN_SYS')) {
			echo json_encode(array('status'=>'ng','msg'=>'非法操作！'));
			exit;
		}
		$score = $_POST['score'];
		
		$Lottery = M('Lottery');
		$is_get_prize = true; // 是否有中奖
		
		// 处理可能需要初始化的礼品抽奖数
		$Lottery->execute("UPDATE tp_gift AS a SET a.prize_num=0,a.prize_date=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE a.prize_date IS NULL OR a.prize_date != DATE_FORMAT(NOW(),'%Y-%m-%d')");
	
		// 判断用户当日抽奖次数是否用完
		$currDate = date("Y-m-d");
		// 20150616  wayhu danis
		//$prizeCount = $Lottery->where("uid=".$_SESSION['uid']." AND lottery_date >= '".$currDate." 00:00:00' AND lottery_date <= '".$currDate." 23:59:59'")->count();
		$prizeCount = $Lottery->where("uid=".$_SESSION['uid'])->count();
		
		$Config = M('Config');
		$prizeNum = $Config->where("varname='cfg_personal_prize_num'")->getField('value');
		
		//$cfg_personal_prize_out = $Config->where("varname='cfg_personal_prize_out'")->getField('value');
		
		if (intval($prizeCount) >= intval($prizeNum)) {
			echo json_encode(array('status'=>'noprize'));
			exit;
		}
		
		/* if (intval($prizeCount) >= intval($prizeNum)) {
			
			echo json_encode(array('status'=>'prizeNumOut','msg'=>$cfg_personal_prize_out));
			exit;
		} */
		// 20150616  wayhu danis end
		
		
		//抽奖逻辑，一个用户最多只能抽中一个奖品
		/*
		 * 奖项数组
		* 是一个二维数组，记录了所有本次抽奖的奖项信息，
		* 其中id表示中奖等级，prize表示奖品，v表示中奖概率。
		* 注意其中的v必须为整数，你可以将对应的 奖项的v设置成0，即意味着该奖项抽中的几率是0，
		* 数组中v的总和（基数），基数越大越能体现概率的准确性。
		* 本例中v的总和为100，那么平板电脑对应的 中奖概率就是1%，
		* 如果v的总和是10000，那中奖概率就是万分之一了。
		*
		*/
	
		// 查找用户可以抢的机型
		$sqlStr = "SELECT a.id,a.gift_name,a.gift_num,a.chance_value,(a.gift_num-IFNULL(b.usednum,0)) AS leastnum FROM tp_gift a
				LEFT JOIN (SELECT gid,COUNT(*) AS usednum FROM tp_lottery GROUP BY gid) b
				ON a.id=b.gid WHERE (a.gift_num-IFNULL(b.usednum,0)) > 0 AND a.prize_num < a.day_num ".
		"AND a.`score_low` <= ".$score." AND a.`score_high` >= ".$score;
		$gift_data = $Lottery->query($sqlStr);
			
		if($gift_data!=null){
			$i =0;
			$prize_arr=array();
			while($row = $gift_data[$i]){
				$prize_arr[$row['id']]=array('id'=>$row['id'],
						'gift_name'=>$row['gift_name'],
						'gift_num'=>$row['gift_num'],
						'chance_value'=>$row['chance_value'],
				);
	
				$i++;
	
			}
		} else {
				
			echo json_encode(array('status'=>'gameover'));
			exit;
		}
			
			
		/*
		 * 每次前端页面的请求，PHP循环奖项设置数组，
		* 通过概率计算函数get_rand获取抽中的奖项id。
		* 将中奖奖品保存在数组$res['yes']中，
		* 而剩下的未中奖的信息保存在$res['no']中，
		* 最后输出json个数数据给前端页面。
		*/
		foreach ($prize_arr as $key => $val) {
			$arr[$val['id']] = $val['chance_value'];
		}
		$rid = $this->get_rand($arr); //根据概率获取奖项id
			
		$prize = $prize_arr[$rid]; //中奖项
	
// 		echo json_encode(array('status'=>'ok','id'=>$prize['id'],'gift_name'=>$prize['gift_name']));
// 		exit;
	
	
		// 判断是否有中奖哦
		if ($prize['gift_name'] == '无中奖') {
			
			echo json_encode(array('status'=>'noprize'));
			exit;
		}
		
		
	
		//开始事务处理	需要考虑锁表机制
		$model = new Model();
		$model->startTrans();
			
		$flag = false;
		if($is_get_prize){
		$sn_code = $this->generalSNKey();
		}else{
		$sn_code="none";
		}
	
		$lottery_arr['uid'] =intval($_SESSION['uid']);
		$lottery_arr['gid']=$prize['id'];
		$lottery_arr['sn_code']= $sn_code;
		$lottery_arr['lottery_date']=date("Y-m-d H:i:s");
		$lottery_add_result = $model->table(C('DB_PREFIX').'lottery')->add($lottery_arr);
			
		if($lottery_add_result){
	
		$gift_status_update = $model->table(C('DB_PREFIX').'gift')->where('id='.$prize['id'])->setInc('prize_num'); // prize_num加1
	
		if($gift_status_update){
		$model->commit();
			
		$flag=true;
		}
	
	
		}
			
		if(!$flag){
		$model->rollback();//回滚
		echo json_encode(array('status'=>'fail'));
		exit;
		}
		
		
		
		echo json_encode(array('status'=>'ok','id'=>$prize['id'],'gift_name'=>$prize['gift_name'],'sn_code'=>$sn_code));
		exit;
	
	
	}
	
	
	
	/*
	 * 经典的概率算法，
	* $proArr是一个预先设置的数组，
	* 假设数组为：array(100,200,300，400)，
	* 开始是从1,1000 这个概率范围内筛选第一个数是否在他的出现概率范围之内，
	* 如果不在，则将概率空间，也就是k的值减去刚刚的那个数字的概率空间，
	* 在本例当中就是减去100，也就是说第二个数是在1，900这个范围内筛选的。
	* 这样 筛选到最终，总会有一个数满足要求。
	* 就相当于去一个箱子里摸东西，
	* 第一个不是，第二个不是，第三个还不是，那最后一个一定是。
	* 这个算法简单，而且效率非常 高，
	* 关键是这个算法已在我们以前的项目中有应用，尤其是大数据量的项目中效率非常棒。
	*/
	public function get_rand($proArr) {
		$result = '';
		//概率数组的总概率精度
		$proSum = array_sum($proArr);
		//概率数组循环
		foreach ($proArr as $key => $proCur) {
			$randNum = mt_rand(1, $proSum);
			if ($randNum <= $proCur) {
				$result = $key;
				break;
			} else {
				$proSum -= $proCur;
			}
		}
		unset ($proArr);
		return $result;
	}
	
	
	public function doDuijiang() {
		$Score = M('Score');
		$giftname = urldecode($_POST['giftname']);
		$shopname = urldecode($_POST['selshop']);
		
		$updCount = $Score->execute("update tp_score as a set a.giftname='".$giftname."', a.shopname='".$shopname."' where uid=".$_SESSION['uid']);
		if ($updCount) {
			$Score->execute("update tp_user as a set a.is_exchange=1 where id=".$_SESSION['uid']);
			echo json_encode(array('status'=>'ok','message'=>'恭喜，申请兑奖成功！'));
		} else {
			echo json_encode(array('status'=>'ng','message'=>'抱歉，申请兑奖失败！'));
		}
		
		exit;
	}
	
	public function loadMyGift() {
		$sql = "SELECT g.gift_name,l.sn_code,SUBSTRING(l.lottery_date,1,16) AS lottery_date,l.stat FROM tp_lottery l,tp_user u,tp_gift g
				WHERE l.uid=u.id AND l.gid=g.id AND l.uid=".$_SESSION['uid'].
				" ORDER BY l.lottery_date DESC";
		$Lottery = M('Lottery');
		$dataList = $Lottery->query($sql);
		if ($dataList && count($dataList) > 0) {
			$dataJson = json_encode($dataList);
			echo json_encode(array('status'=>'ok','data'=>$dataJson));
			exit;
		}
		
		echo json_encode(array('status'=>'ng','msg'=>'您暂无奖品哦！'));
		exit;
	}
	
	public function topN() {
		// 调试用开始
		
		
		// 调试用结束
		$isGameEnd = $this->isGameEnd();
		//$this->assign('isGameEnd', $isGameEnd);
		
		$Config = M('Config');
		$cfg_topn_num = $Config->where("varname='cfg_topn_num'")->getField('value');
		if (!$cfg_topn_num || empty($cfg_topn_num)) {
			$cfg_topn_num = 50;
		}
		
		$sql = "select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,
				s.id as id,s.score as score,s.joindate as joindate,u.headimgurl as headimgurl,u.wxid as wxid 
				from tp_user u join tp_score s on u.id=s.uid order by score desc";
		$result = $Config->query($sql);
		
		$hasTopn = 0;
		$mypos = 0;
		if ($result && count($result) > 0) {
			$hasTopn = 1;
			$n = 0;
			foreach ($result as $row) {
				if (!empty($row['userphone']) && $row['userphone'] != 'none') {
					$row['userphone'] = substr_replace($row['userphone'],'****',3,4);
				} else {
					$row['userphone'] = '未填写';
				}
				
				$data[] = $row;
				
				$n++;
				if (intval($n) >= intval($cfg_topn_num)) {
					break;
				}
				
				if ($row['uid'] == $_SESSION['uid']) {
					$mypos = $n;
				}
				
			}
			$dataJson = json_encode($data);
			
			echo json_encode(array('status'=>'ok','data'=>$dataJson,'mypos'=>$mypos,'hasTopn'=>$hasTopn));
			exit;
		}
		
		echo json_encode(array('status'=>'ng','msg'=>'暂无排名信息！','hasTopn'=>$hasTopn));
		exit;
		
	}
	
	private function setCookieVal($cookieStr) {
		$cookieName = "xiaomifengCookie";
		// setcookie("wxidCookie4", $cookieStr, time() + 60*60*24*100);
		// setcookie("wxidCookie4", $cookieStr, time() + 2*60);
		cookie($cookieName,$cookieStr,array('expire'=>3600 * 24 * 3000));// 3600秒
	}
	
	public function setCookieValExpire() {
		$cookieName = "xiaomifengCookie";
		cookie($cookieName,null);
	}
	
	public function index(){
		$Config = M('Config');
		
		//获取微信分享图片，分享标题，分享描述，分享URL
		$cfg_wx_share_title = $Config->where("varname='cfg_wx_share_title'")->getField('value');
		$this->assign('cfg_wx_share_title',$cfg_wx_share_title);
		
		$cfg_wx_share_desc = $Config->where("varname='cfg_wx_share_desc'")->getField('value');
		$this->assign('cfg_wx_share_desc',$cfg_wx_share_desc);
		
		$cfg_wx_share_pic = $Config->where("varname='cfg_wx_share_pic'")->getField('value');
		$this->assign('cfg_wx_share_pic',$cfg_wx_share_pic);
		
		$cfg_wx_share_url = $Config->where("varname='cfg_wx_share_url'")->getField('value');
		$this->assign('cfg_wx_share_url',$cfg_wx_share_url);
		
		$cfg_wx_share_redirect = $Config->where("varname='cfg_wx_share_redirect'")->getField('value');
		$this->assign('cfg_wx_share_redirect',$cfg_wx_share_redirect);
		
		$cfg_appid = $Config->where("varname='cfg_appid'")->getField('value');
		$cfg_screct = $Config->where("varname='cfg_screct'")->getField('value');
		
		//判断活动是否结束
		if($this->isGameEnd()){
			
			$cfg_game_finish=$Config->where("varname='cfg_game_finish'")->getField('value');
			
			
			$this->assign('cfg_game_finish',$cfg_game_finish);
		
			$this->display('gameFinish');
			
		} else {
			
		//判断是否为微信授权模式，如果是，跳转微信授权页面
		
		$cfg_isoauth_open = $Config->where("varname='cfg_isoauth_open'")->getField('value');
		
		$need_login = 1; //默认需要弹填写信息框
		//$cfg_isoauth_open = 0;
		
		if($cfg_isoauth_open=='1') {
			$cfg_oauth_cb_url = $Config->where("varname='cfg_oauth_cb_url'")->getField('value');
			
			
			// 
			/* $cookieName = "xiaomifengCookie";
			$xmfCookie = $_COOKIE[$cookieName];
			if ($xmfCookie != "") { // 用户玩过该游戏才可能存在cookie，非首次体验
				$cookieInfo = json_decode($xmfCookie, true);
				$openid = $cookieInfo["openid"];
				if (!empty($openid)) {
					
					
				}
				
			} */
			
			
			//授权判断
			if (empty($openid)){
				if (empty($_REQUEST["code"])) {
					$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$cfg_appid."&redirect_uri=".urlencode($cfg_oauth_cb_url)."&response_type=code&scope=snsapi_userinfo&state=blinq#wechat_redirect";

					redirect($url);
			
				}else{
					$code = $_REQUEST['code'];

					$accessTokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $cfg_appid . "&secret=" . $cfg_screct . "&code=$code&grant_type=authorization_code";
					$ch = curl_init($accessTokenUrl);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20100101 Firefox/21.0');
					curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20100101 Firefox/21.0');
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					$info = curl_exec($ch);
					$dataJson = json_decode($info, true);
					$openid = $dataJson['openid'];
					$access_token = $dataJson['access_token'];
					session("wxid", $openid);
					
					//
					$cookieStr = '{"openid":"'.$openid.'","date":"20111212"}';
					$this->setCookieVal($cookieStr);
					
					// 拉取用户信息
					$userInfoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
					$ch = curl_init($userInfoUrl);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					//curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20100101 Firefox/21.0');
					curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:21.0) Gecko/20100101 Firefox/21.0');
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					$info = curl_exec($ch);
					$dataJson = json_decode($info, true);
					
					$headimgurl = $dataJson['headimgurl'];
					$nickname = $dataJson['nickname'];
					
					
					// 判断wxid是否已经配置过，如果存在并且填写过信息，则不需要显示填写信息框
					$User = M('User');
					$condition['wxid'] = $openid;
					$userList = $User->where($condition)->find();
					
					$this->assign("headimgurl",$info);
					
					if($userList && count($userList) > 0){
						$userphone = $userList['userphone'];
						if (!empty($userphone) && $userphone != 'none') {
							$need_login = 0; //表示填写过信息，不需要显示填写信息框
						}
						
						//获取用户ID
						$uid = $userList['id'];
						session("uid",$uid);
						
						//
						if (!empty($nickname)) {
							$userList['username'] = $nickname;
							$userList['headimgurl'] = $headimgurl;
							$User->save($userList);
						}
						
					} else {
						$userdata['username'] = $nickname;
						$userdata['userphone'] = 'none';
						$userdata['is_exchange'] = '0';
						$userdata['wxid'] = $openid;
						$userdata['headimgurl'] = $headimgurl;
						$userdata['regtime'] = date("Y-m-d H:i:s");
						$uid = $User->add($userdata);
						session("uid",$uid);
						
					}
					
					
				}
			}
		}
		
		$this->assign('need_login',$need_login); 
		
		//获取首页的标题
		$cfg_homepage_title = $Config->where("varname='cfg_homepage_title'")->getField('value');
		$this->assign('cfg_homepage_title',$cfg_homepage_title);
		
		
		//获取统计代码
		$cfg_tongji_code = $Config->where("varname='cfg_tongji_code'")->getField('value');
		$this->assign('cfg_tongji_code',$cfg_tongji_code);
		
		//获取banner路径
		$cfg_top_banner = $Config->where("varname='cfg_top_banner'")->getField('value');
		$this->assign('cfg_top_banner',$cfg_top_banner);
		
		//获取活动时间，活动规则，活动奖品，活动说明等信息
		$cfg_game_star_time = $Config->where("varname='cfg_game_star_time'")->getField('value');
		$this->assign('cfg_game_star_time',$cfg_game_star_time);
		$cfg_game_end_time = $Config->where("varname='cfg_game_end_time'")->getField('value');
		$this->assign('cfg_game_end_time',$cfg_game_end_time);
		
		
		
		
		$cfg_game_rule = $Config->where("varname='cfg_game_rule'")->getField('value');
		$this->assign('cfg_game_rule',$cfg_game_rule);
		
		$cfg_game_end_say = $Config->where("varname='cfg_game_end_say'")->getField('value');
		$this->assign('cfg_game_end_say',$cfg_game_end_say);
		
		$cfg_wx_share_desc1 = $Config->where("varname='cfg_wx_share_desc1'")->getField('value');
		$this->assign('cfg_wx_share_desc1',$cfg_wx_share_desc1);
		
		$cfg_wx_share_desc2 = $Config->where("varname='cfg_wx_share_desc2'")->getField('value');
		$this->assign('cfg_wx_share_desc2',$cfg_wx_share_desc2);
		
		$cfg_game_prize_say = $Config->where("varname='cfg_game_prize_say'")->getField('value');
		$this->assign('cfg_game_prize_say',$cfg_game_prize_say);
		
		// 处理当前用户的抽奖次数
		//$Config->execute("UPDATE tp_user AS a SET a.prize_num=0,a.prize_date=DATE_FORMAT(NOW(),'%Y-%m-%d') WHERE a.prize_date IS NULL OR a.prize_date != DATE_FORMAT(NOW(),'%Y-%m-%d')");
		
		
		// 获取分享的签名 开始
		vendor("WeixinShare.jssdk");
		$jssdk = new JSSDK($cfg_appid, $cfg_screct);
		$signPackage = $jssdk->GetSignPackage();
		$this->assign('signPackage',$signPackage);
		// 获取分享的签名 结束
		
		
		$this->display();
		
		}
		
		
    }
    
    
    public function doSavePhone() {
    	$User = M("User"); // 实例化User对象
    	$userphone =$_POST['userphone'];
    	if (!empty($userphone)) {
    		$stat = $User->execute("update tp_user as a set a.userphone='".$userphone."' where a.id=".$_SESSION['uid']);
    		if(!$stat){
    			echo json_encode(array('status'=>'ng','message'=>'抱歉，提交信息失败！'));
    			exit;
    		} 
    	}
    	
    	echo json_encode(array('status'=>'ok','message'=>'提交信息成功！'));
    	exit;
    	
    }
    
    public function doReg(){
    	
    	$Config = M('Config');
    	$cfg_isoauth_open = $Config->where("varname='cfg_isoauth_open'")->getField('value');
    	
    	//如果是微信授权的，没注册的，直接添加一笔记录
    	if($cfg_isoauth_open=='1'){
    		$user_array['username']= $_POST['username'];
    		$user_array['userphone'] = $_POST['userphone'];
    		$user_array['wxid']= $_SESSION['wxid'];
    		$user_array['regtime']= date("Y-m-d H:i:s");
    		$User = M("User"); // 实例化User对象
    		$lastId = $User->add($user_array);
    		if(!$lastId){
    			echo json_encode(array('status'=>'ng','message'=>'新增用户失败！'));
    			exit;
    		}
    		 
    		session("uid",$lastId);//获取最新用户ID
    	}else{
    		//判断用户的手机是否存在，如果手机存在，判断是否用户名输错，如果都不存在，注册新的一笔记录
    		/* 判断用户是否存在 */
    		$User = M("User"); // 实例化User对象
    		$user_array['userphone'] =$_POST['userphone'];
    		$user_result=$User->where($user_array)->find();
    		
    		if($user_result != null){
    			$user_array['username']=$_POST['username'];
    			$user_array['wxid']='none';
    			$user_result=$User->where($user_array)->find();
    				
    			if($user_result==null){//用户名错误了
    				echo json_encode(array('status'=>'ng','message'=>'用户名错误！'));
    				exit;
    			}else{
    				
    				session("uid",$user_result['id']);//获取最新用户ID
    				session("username",$user_result['username']);
    				session("userphone",$user_result['userphone']);
    			}
    		}else{
    			$user_array['username']= $_POST['username'];
    			$user_array['userphone'] = $_POST['userphone'];
    			$user_array['wxid']= 'none';//没有微信ID
    			$user_array['regtime']= date("Y-m-d H:i:s");
    			$User = M("User"); // 实例化User对象
    			$lastId = $User->add($user_array);
    			if(!$lastId){
    				echo json_encode(array('status'=>'ng','message'=>'新增用户失败！'));
    				exit;
    			}
    			 
    			session("uid",$lastId);//获取最新用户ID
    			session("username",$_POST['username']);
    			session("userphone",$_POST['userphone']);
    		}
    	}
    	echo json_encode(array('status'=>'ok'));
    	exit;
    }
    
    public function doScoreUpdate() {
    	if (!defined('IN_SYS')) {
    		echo json_encode(array('status'=>'ng','msg'=>'非法操作！'));
    		exit;
    	}
    	$score = $_POST['score'];
    	if (!empty($score)) {
    		$score = (integer)$score;
    	} else {
    		$score = 0;
    	}
    	if ($score <= 0) {
    		echo json_encode(array('status'=>'-1'));
    		exit;
    	}
    	
    	$score_array['uid']=$_SESSION['uid'];
    	$score_array['score']=$score;
    	$score_array['joindate']=date("Y-m-d H:i:s");
    	
    	$u = M("User"); // 实例化User对象
    	$m = M("Score"); // 实例化Score对象
    	// 查找是否已经有存在过
    	$uobj = $u->where("wxid='".$_SESSION['wxid']."'")->find();
    	if ($uobj && count($uobj) > 0) {
    		$uid = $uobj['id'];
    		$result = $m->where("uid=".$uid)->find();
    		if ($result && count($result) > 0) {
    			$score_array['id'] = $result['id'];
    			if ($score > (integer)$result['score']) {
    				$lastId = $m->save($score_array);
    			} else {
    				$lastId = 1;
    			}
    		} else {
    			$lastId = $m->add($score_array);
    		}
    	}
    	if(!$lastId){
    		echo json_encode(array('status'=>'ng'));
    		exit;
    	}
    	
    	// 判断是否有礼盒
    	$Gift = M('Gift');
    	$count = $Gift->where("score_low <=".$score." and score_high >=".$score)->count();
    	if (intval($count) > 0) {
    		echo json_encode(array('status'=>'1'));
    	} else {
    		echo json_encode(array('status'=>'0'));
    	}
    	exit;
    }
    
    public function cut_str($string, $sublen, $start = 0, $code = 'UTF-8')
    {
    	if($code == 'UTF-8')
    	{
    		$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
    		preg_match_all($pa, $string, $t_string);
    
    		if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen));
    		return join('', array_slice($t_string[0], $start, $sublen));
    	}
    	else
    	{
    		$start = $start*2;
    		$sublen = $sublen*2;
    		$strlen = strlen($string);
    		$tmpstr = '';
    
    		for($i=0; $i< $strlen; $i++)
    		{
    		if($i>=$start && $i< ($start+$sublen))
    		{
    		if(ord(substr($string, $i, 1))>129)
    			{
    			$tmpstr.= substr($string, $i, 2);
    		}
    		else
    		{
    		$tmpstr.= substr($string, $i, 1);
    		}
    		}
    		if(ord(substr($string, $i, 1))>129) $i++;
    		}
    			//if(strlen($tmpstr)< $strlen ) $tmpstr.= "...";
    			return $tmpstr;
    	}
    }
    	
    public function rank() {
		// 调试用开始
		// 调试用结束
		$isGameEnd = $this->isGameEnd();
		$this->assign('isGameEnd', $isGameEnd);
		
		$Config = M('Config');
		$cfg_topn_num = $Config->where("varname='cfg_topn_num'")->getField('value');
		if (!$cfg_topn_num || empty($cfg_topn_num)) {
			$cfg_topn_num = 50;
		}
		
		$sql = "select u.id as uid,u.username as username,u.userphone as userphone,u.is_exchange as is_exchange,
				s.id as id,s.score as score,s.joindate as joindate,u.headimgurl as headimgurl,u.wxid as wxid 
				from tp_user u join tp_score s on u.id=s.uid order by score desc limit ".$cfg_topn_num;
				
		
		$result = $Config->query($sql);
		
		$hasTopn = 0;
		if ($result && count($result) > 0) {
			$hasTopn = 1;
			
			foreach ($result as $row) {
				if (!empty($row['userphone']) && $row['userphone'] != 'none') {
					$row['userphone'] = substr_replace($row['userphone'],'****',3,4);
				} else {
					$row['userphone'] = '未填写';
				}
				
				$data[] = $row;
			}
			$this->assign("data", $data);
			
			$cookieName = "xiaomifengCookie";
			$xmfCookie = $_COOKIE[$cookieName];
			
			if ($xmfCookie != "") { // 用户玩过该游戏才可能存在cookie，非首次体验
				$xmfCookie = stripslashes($xmfCookie);
				//echo $xmfCookie;
				//exit;
				$cookieInfo = json_decode($xmfCookie, true);
				//echo var_dump($cookieInfo);
				//exit;
				$openid = $cookieInfo["openid"];
				
				if (!empty($openid)) {
					$pos = 0;
					foreach($data as $row) {
						$pos++;
						if ($row['wxid'] == $openid) {
							
							$this->assign("mypos", $pos);
						}	
							
					}
					
					$this->assign("openid", $openid);
					
				}
				
			}
			
		}
		
		
		//获取微信分享图片，分享标题，分享描述，分享URL
		$cfg_wx_share_title = $Config->where("varname='cfg_wx_share_title'")->getField('value');
		$this->assign('cfg_wx_share_title',$cfg_wx_share_title);
		
		$cfg_wx_share_desc = $Config->where("varname='cfg_wx_share_desc'")->getField('value');
		$this->assign('cfg_wx_share_desc',$cfg_wx_share_desc);
		
		$cfg_wx_share_pic = $Config->where("varname='cfg_wx_share_pic'")->getField('value');
		$this->assign('cfg_wx_share_pic',$cfg_wx_share_pic);
		
		$cfg_wx_share_url = $Config->where("varname='cfg_wx_share_url'")->getField('value');
		$this->assign('cfg_wx_share_url',$cfg_wx_share_url);
		
		$cfg_wx_share_redirect = $Config->where("varname='cfg_wx_share_redirect'")->getField('value');
		$this->assign('cfg_wx_share_redirect',$cfg_wx_share_redirect);
		
		
		// 获取分享的签名 开始
		$cfg_appid = $Config->where("varname='cfg_appid'")->getField('value');
		$cfg_screct = $Config->where("varname='cfg_screct'")->getField('value');
		
		vendor("WeixinShare.jssdk");
		$jssdk = new JSSDK($cfg_appid, $cfg_screct);
		$signPackage = $jssdk->GetSignPackage();
		$this->assign('signPackage',$signPackage);
		// 获取分享的签名 结束
		
		
		
		$this->display();
	}
	
}