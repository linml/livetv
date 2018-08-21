<?php
session_start();
class Model_Home extends Model_Common {

	/* 轮播 */
	public function getSlide(){

		$rs=DI()->notorm->slide
			->select("slide_pic,slide_url")
			->where("slide_status='1' and slide_cid='0' ")
			->order("listorder asc")
			->fetchAll();
		foreach($rs as $k=>$v){
			$rs[$k]['slide_pic']=$this->get_upload_path($v['slide_pic']);
		}				

		return $rs;				
	}

	/* 热门 */
    public function getHot($p) {

		$pnum=50;
		$start=($p-1)*$pnum;
		$where=" l.islive= '1' and u.ishot='1'";

		/* if($p!=1){
			$endtime=$_SESSION['new_starttime'];
			$where.=" and starttime < {$endtime}";
		} */
		$prefix= DI()->config->get('dbs.tables.__default__.prefix');
		
		$result=DI()->notorm->users_live
					->queryAll("select l.uid,l.avatar,l.avatar_thumb,l.user_nicename,l.title,l.city,l.stream,l.pull,l.thumb,l.isvideo,l.type,l.type_val,l.game_action,l.goodnum,l.anyway,u.votestotal from {$prefix}users_live l left join {$prefix}users u on l.uid=u.id where {$where} order by u.isrecommend desc,l.starttime desc limit {$start},{$pnum}");

		foreach($result as $k=>$v){
			$nums=DI()->redis->hlen('userlist_'.$v['stream']);

			$result[$k]['nums']=(string)$nums;
			
			$result[$k]['level_anchor']=$this->getLevelAnchor($v['votestotal']);
			
			$result[$k]['game']=$this->game_action[$v['game_action']];
			
			if(!$v['thumb']){
				$result[$k]['thumb']=$v['avatar'];
			}
			if($v['isvideo']==0){
				$result[$k]['pull']=$this->PrivateKeyA('rtmp',$v['stream'],0);
			}
			
			if($v['type']==1){
				$result[$k]['type_val']='';
			}
			
		}	
		/* if($result){
			$last=array_slice($result,-1,1);
			$_SESSION['new_starttime']=$last['starttime'];
		} */
		
		return $result;
    }
	
		/* 关注列表 */
    public function getFollow($uid,$p) {
		$result=array();
		$pnum=50;
		$start=($p-1)*$pnum;
		
		$touid=DI()->notorm->users_attention
				->select("touid")
				->where('uid=?',$uid)
				->fetchAll();
		$where=" islive='1' ";					
		if($p!=1){
			$endtime=$_SESSION['follow_starttime'];
			$where.=" and starttime < {$endtime}";
		}					
		if($touid){
			$touids=array_column($touid,"touid");
			$touidss=implode(",",$touids);
			$where.=" and uid in ({$touidss})";
			$result=DI()->notorm->users_live
					->select("uid,avatar,avatar_thumb,user_nicename,title,city,stream,pull,thumb,isvideo,type,type_val,game_action,goodnum,anyway")
					->where($where)
					->order("starttime desc")
					->limit($start,$pnum)
					->fetchAll();
		}	
		foreach($result as $k=>$v){
			$nums=DI()->redis->hlen('userlist_'.$v['stream']);
			$result[$k]['nums']=(string)$nums;
			
			$userinfo=$this->getUserInfo($v['uid']);
			$result[$k]['level_anchor']=$userinfo['level_anchor'];
			
			$result[$k]['game']=$this->game_action[$v['game_action']];
			
			if(!$v['thumb']){
				$result[$k]['thumb']=$v['avatar'];
			}
			if($v['isvideo']==0){
				$result[$k]['pull']=$this->PrivateKeyA('rtmp',$v['stream'],0);
			}
			if($v['type']==1){
				$result[$k]['type_val']='';
			}
		}	

		if($result){
			$last=array_slice($result,-1,1);
			$_SESSION['follow_starttime']=$last['starttime'];
		}

		return $result;					
    }
		
		/* 最新 */
    public function getNew($lng,$lat,$p) {
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=" islive='1' ";

		if($p!=1){
			$endtime=$_SESSION['new_starttime'];
			$where.=" and starttime < {$endtime}";
		}
		
		$result=DI()->notorm->users_live
				->select("uid,avatar,avatar_thumb,user_nicename,title,city,stream,lng,lat,pull,thumb,isvideo,type,type_val,game_action,goodnum,anyway")
				->where($where)
				->order("starttime desc")
				->limit($start,$pnum)
				->fetchAll();	
		foreach($result as $k=>$v){
			$nums=DI()->redis->hlen('userlist_'.$v['stream']);
			$result[$k]['nums']=(string)$nums;
			
			$userinfo=$this->getUserInfo($v['uid']);
			$result[$k]['level_anchor']=$userinfo['level_anchor'];
			
			$result[$k]['game']=$this->game_action[$v['game_action']];
			
			if(!$v['thumb']){
				$result[$k]['thumb']=$v['avatar'];
			}
			if($v['isvideo']==0){
				$result[$k]['pull']=$this->PrivateKeyA('rtmp',$v['stream'],0);
			}
			
			if($v['type']==1){
				$result[$k]['type_val']='';
			}
			
			$distance='好像在火星';
			if($lng!='' && $lat!='' && $v['lat']!='' && $v['lng']!=''){
				$distance=$this->getDistance($lat,$lng,$v['lat'],$v['lng']);
			}else if($v['city']){
				$distance=$v['city'];	
			}
			
			$result[$k]['distance']=$distance;
			unset($result[$k]['lng']);
			unset($result[$k]['lat']);
			
		}		
		if($result){
			$last=array_slice($result,-1,1);
			$_SESSION['new_starttime']=$last['starttime'];
		}

		return $result;
    }
		
		/* 搜索 */
    public function search($uid,$key,$p) {
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=' user_type="2" and ( id=? or user_nicename like ?  or goodnum like ? ) and id!=?';
		if($p!=1){
			$id=$_SESSION['search'];
			$where.=" and id < {$id}";
		}
		
		$result=DI()->notorm->users
				->select("id,user_nicename,avatar,sex,signature,consumption,votestotal")
				->where($where,$key,'%'.$key.'%','%'.$key.'%',$uid)
				->order("id desc")
				->limit($start,$pnum)
				->fetchAll();
		foreach($result as $k=>$v){
			$result[$k]['level']=(string)$this->getLevel($v['consumption']);
			$result[$k]['level_anchor']=(string)$this->getLevelAnchor($v['votestotal']);
			$result[$k]['isattention']=(string)$this->isAttention($uid,$v['id']);
			$result[$k]['avatar']=$this->get_upload_path($v['avatar']);
			unset($result[$k]['consumption']);
		}				
		
		if($result){
			$last=array_slice($result,-1,1);
			$_SESSION['search']=$last['id'];
		}
		
		return $result;
    }
	
	/* 附近 */
    public function getNearby($lng,$lat,$p) {
		$pnum=50;
		$start=($p-1)*$pnum;
		$where=" islive='1' and lng!='' and lat!='' ";
		
		$result=DI()->notorm->users_live
				->select("uid,avatar,avatar_thumb,user_nicename,title,province,city,stream,lng,lat,pull,isvideo,thumb,islive,type,type_val,game_action,goodnum,anyway")
				->where($where)
				->fetchAll();	
		foreach($result as $k=>$v){
			$nums=DI()->redis->hlen('userlist_'.$v['stream']);
			$result[$k]['nums']=(string)$nums;
			
			$userinfo=$this->getUserInfo($v['uid']);
			$result[$k]['level_anchor']=$userinfo['level_anchor'];
			
			$result[$k]['game']=$this->game_action[$v['game_action']];
		
			if(!$v['thumb']){
				$result[$k]['thumb']=$v['avatar'];
			}
			if($v['isvideo']==0){
				$result[$k]['pull']=$this->PrivateKeyA('rtmp',$v['stream'],0);
			}
			
			if($v['type']==1){
				$result[$k]['type_val']='';
			}
			
			$distance=$this->getDistance($lat,$lng,$v['lat'],$v['lng']);

			$result[$k]['distance']=$distance;
			$order1[$k]=(float)$distance;
			unset($result[$k]['lng']);
			unset($result[$k]['lat']);
			
		}		
		array_multisort($order1, SORT_ASC, $result); //推荐倒序 点亮倒序 开播时间倒序
		
		return $result;
    }


	/* 推荐 */
	public function getRecommend(){

		$result=DI()->notorm->users
				->select("id,user_nicename,avatar,avatar_thumb")
				->where("isrecommend='1'")
				->order("votestotal desc")
				->limit(0,12)
				->fetchAll();
		foreach($result as $k=>$v){
			$result[$k]['avatar']=$this->get_upload_path($v['avatar']);
			$result[$k]['avatar_thumb']=$this->get_upload_path($v['avatar_thumb']);
			$fans=$this->getFans($v['id']);
			$result[$k]['fans']='粉丝 · '.$fans;
		}
		return  $result;
	}
	/* 关注推荐 */
	public function attentRecommend($uid,$touids){
		//$users=$this->getRecommend();
		$users=explode(',',$touids);
		foreach($users as $k=>$v){
			$touid=$v;
			if($touid && !$this->isAttention($uid,$touid)){
				DI()->notorm->users_black
					->where('uid=? and touid=?',$uid,$touid)
					->delete();
				DI()->notorm->users_attention
					->insert(array("uid"=>$uid,"touid"=>$touid));
			}
			
		}
		return 1;
	}

	/*获取收益排行榜*/

	public function profitList($uid,$type,$p){

		$pnum=50;
		$start=($p-1)*$pnum;
		$configPub=$this->getConfigPub();

		switch ($type) {
			case 'day':
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" r.addtime >={$dayStart} and r.addtime<={$dayEnd} and ";

			break;

			case 'week':
				//获取本周开始结束时间
				$weekStart=mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));  
    			$weekEnd= mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));
				$where=" r.addtime >={$weekStart} and r.addtime<={$weekEnd} and ";

			break;

			case 'month':
				$monthStart=strtotime(date('Y-m-01 00:00:00'));//本月第一天，格式成时间戳
				$monthEnd=strtotime(date('Y-m-d H:i:s',mktime(23,59,59,date('n'),date('t'),date('Y'))));//本月最后一天，格式成时间戳

				$where=" r.addtime >={$monthStart} and r.addtime<={$monthEnd} and ";

			break;

			case 'total':
				$where=" ";
			break;
			
			default:
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" r.addtime >={$dayStart} and r.addtime<={$dayEnd} and ";
			break;
		}




		$where.=" r.type='expend' and r.action in ('sendgift','sendbarrage')";

		$prefix= DI()->config->get('dbs.tables.__default__.prefix');
		
		$result=DI()->notorm->users_coinrecord

			->queryAll("select sum(r.totalcoin) as totalcoin,r.touid as uid,u.votestotal,u.user_nicename,u.avatar_thumb from {$prefix}users_coinrecord r left join {$prefix}users u on r.touid=u.id where {$where}  group by r.touid order by totalcoin desc limit {$start},{$pnum}");
		foreach ($result as $k => $v) {
			$result[$k]['levelAnchor']=$this->getLevelAnchor($v['votestotal']); //主播等级
			$result[$k]['isAttention']=$this->isAttention($uid,$v['uid']);//判断当前用户是否关注了该主播
			$result[$k]['avatar_thumb']=$this->get_upload_path($v['avatar_thumb']);
			$result[$k]['totalcoin']=$v['totalcoin'].$configPub['name_coin'];
			unset($result[$k]['votestotal']);
		}


		return $result;
	}



	/*获取消费排行榜*/

	public function consumeList($uid,$type,$p){

		$pnum=50;
		$start=($p-1)*$pnum;
		$configPub=$this->getConfigPub();

		switch ($type) {
			case 'day':
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" r.addtime >={$dayStart} and r.addtime<={$dayEnd} and ";

			break;

			case 'week':
				//获取本周开始结束时间
				$weekStart=mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));  
    			$weekEnd= mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));
				$where=" r.addtime >={$weekStart} and r.addtime<={$weekEnd} and ";

			break;

			case 'month':
				$monthStart=strtotime(date('Y-m-01 00:00:00'));//本月第一天，格式成时间戳
				$monthEnd=strtotime(date('Y-m-d H:i:s',mktime(23,59,59,date('n'),date('t'),date('Y'))));//本月最后一天，格式成时间戳

				$where=" r.addtime >={$monthStart} and r.addtime<={$monthEnd} and ";

			break;

			case 'total':
				$where=" ";
			break;
			
			default:
				//获取今天开始结束时间
				$dayStart=strtotime(date("Y-m-d"));
				$dayEnd=strtotime(date("Y-m-d 23:59:59"));
				$where=" r.addtime >={$dayStart} and r.addtime<={$dayEnd} and ";
			break;
		}




		$where.=" r.type='expend' and r.action in ('sendgift','sendbarrage')";

		$prefix= DI()->config->get('dbs.tables.__default__.prefix');
		
		$result=DI()->notorm->users_coinrecord

			->queryAll("select sum(r.totalcoin) as totalcoin,r.uid as uid,u.consumption,u.user_nicename,u.avatar_thumb from {$prefix}users_coinrecord r left join {$prefix}users u on r.uid=u.id where {$where}  group by r.uid order by totalcoin desc limit {$start},{$pnum}");
		foreach ($result as $k => $v) {
			$result[$k]['level']=$this->getLevel($v['consumption']); //用户等级
			$result[$k]['isAttention']=$this->isAttention($uid,$v['uid']);//判断当前用户是否关注了该用户
			$result[$k]['avatar_thumb']=$this->get_upload_path($v['avatar_thumb']);
			$result[$k]['totalcoin']=$v['totalcoin'].$configPub['name_coin'];
			unset($result[$k]['consumption']);

		}


		return $result;
	}

}
