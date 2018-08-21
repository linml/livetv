<?php

class Model_Live extends Model_Common {
	/* 创建房间 */
	public function createRoom($uid,$data) {
		$isexist=DI()->notorm->users_live
					->select("uid")
					->where('uid=?',$uid)
					->fetchOne();
		if($isexist){
			/* 更新 */
			$rs=DI()->notorm->users_live->where('uid = ?', $uid)->update($data);
		}else{
			/* 加入 */
			$rs=DI()->notorm->users_live->insert($data);
		}
		if(!$rs){
			return $rs;
		}
		return 1;
	}
	
	/* 主播粉丝 */
    public function getFansIds($touid) {
		$fansids=DI()->notorm->users_attention
					->select("uid")
					->where('touid=?',$touid)
					->fetchAll();
        return $fansids;
    }	
	
	/* 修改直播状态 */
	public function changeLive($uid,$stream,$status){

		if($status==1){
            $info=DI()->notorm->users_live
                    ->select("*")
					->where('uid=? and stream=?',$uid,$stream)
                    ->fetchOne();
            if($info){
                DI()->notorm->users_live
					->where('uid=? and stream=?',$uid,$stream)
					->update(array("islive"=>1));
            }
			return $info;
		}else{
			$this->stopRoom($uid,$stream);
			return 1;
		}
	}
	
	/* 修改直播状态 */
	public function changeLiveType($uid,$stream,$data){
		return DI()->notorm->users_live
				->where('uid=? and stream=?',$uid,$stream)
				->update( $data );
	}
	
	/* 关播 */
	public function stopRoom($uid,$stream) {

		$info=DI()->notorm->users_live
				->select("uid,showid,starttime,title,province,city,stream,lng,lat,type,type_val")
				->where('uid=? and stream=? and islive="1"',$uid,$stream)
				->fetchOne();
		if($info){
			DI()->notorm->users_live
				->where('uid=?',$uid)
				->delete();
			$nowtime=time();
			$info['endtime']=$nowtime;
			$info['time']=date("Y-m-d",$info['showid']);
			$votes=DI()->notorm->users_coinrecord
				->where('touid=? and showid=?',$uid,$info['showid'])
				->sum('totalcoin');
			$info['votes']=0;
			if($votes){
				$info['votes']=$votes;
			}
			$nums=DI()->redis->hlen('userlist_'.$stream);			
			DI()->redis->hDel("livelist",$uid);
			DI()->redis->delete($uid.'_zombie');
			DI()->redis->delete($uid.'_zombie_uid');
			DI()->redis->delete('attention_'.$uid);
			DI()->redis->delete('userlist_'.$stream);
			$game=DI()->notorm->game
				->select("*")
				->where('stream=? and liveuid=? and state=?',$stream,$uid,"0")
				->fetchOne();
			$total=array();
			if($game)
			{
				$total=DI()->notorm->users_gamerecord
					->select("uid,sum(coin_1 + coin_2 + coin_3 + coin_4 + coin_5 + coin_6) as total")
					->where('gameid=?',$game['id'])
					->group('uid')
					->fetchAll();
				foreach($total as $k=>$v){
					DI()->notorm->users
						->where('id = ?', $v['uid'])
						->update(array('coin' => new NotORM_Literal("coin + {$v['total']}")));
					$this->delcache('userinfo_'.$v['uid']);
					
					$insert=array("type"=>'income',"action"=>'game_return',"uid"=>$v['uid'],"touid"=>$v['uid'],"giftid"=>$giftid,"giftcount"=>1,"totalcoin"=>$v['total'],"showid"=>0,"addtime"=>$addtime );
					DI()->notorm->users_coinrecord->insert($insert);
				}

				DI()->notorm->game
					->where('id = ?', $game['id'])
					->update(array('state' =>'3','endtime' => time() ) );
				$brandToken=$stream."_".$game["action"]."_".$game['starttime']."_Game";
				DI()->redis->delete($brandToken);
			}
			$info['nums']=$nums;			
			$result=DI()->notorm->users_liverecord->insert($info);	
		}

		/* 处理竞拍 */
		$this->stopAuction($uid);
		return 1;
	}
	/* 关播信息 */
	public function stopInfo($stream){
		
		$rs=array(
			'nums'=>0,
			'length'=>0,
			'votes'=>0,
		);
		
		$stream2=explode('_',$stream);
		$liveuid=$stream2[0];
		$starttime=$stream2[1];
		$liveinfo=DI()->notorm->users_liverecord
					->select("starttime,endtime,nums,votes")
					->where('uid=? and starttime=?',$liveuid,$starttime)
					->fetchOne();
		if($liveinfo){
			$rs['length']=$this->getSeconds($liveinfo['endtime'] - $liveinfo['starttime']);
			$rs['nums']=$liveinfo['nums'];
		}
		if($liveinfo['votes']){
			$rs['votes']=$liveinfo['votes'];
		}
		return $rs;
	}
	
	/* 直播状态 */
	public function checkLive($uid,$liveuid,$stream){
		$islive=DI()->notorm->users_live
					->select("islive,type,type_val,starttime")
					->where('uid=? and stream=?',$liveuid,$stream)
					->fetchOne();
					
		if(!$islive || $islive['islive']==0){
			return 1005;
		}
		$rs['type']=$islive['type'];
		$rs['type_val']='0';
		$rs['type_msg']='';
		
			$userinfo=DI()->notorm->users
					->select("issuper")
					->where('id=?',$uid)
					->fetchOne();
			if($userinfo && $userinfo['issuper']==1){
                
                if($islive['type']==6){
                    
                    return 1007;
                }
				$rs['type']='0';
				$rs['type_val']='0';
				$rs['type_msg']='';
				
				return $rs;
			}
		
		if($islive['type']==1){
			$rs['type_msg']=md5($islive['type_val']);
		}else if($islive['type']==2){
			$rs['type_msg']='本房间为收费房间，需支付'.$islive['type_val'].'钻石';
			$rs['type_val']=$islive['type_val'];
			$isexist=DI()->notorm->users_coinrecord
						->select('id')
						->where('uid=? and touid=? and showid=? and action="roomcharge" and type="expend"',$uid,$liveuid,$islive['starttime'])
						->fetchOne();
			if($isexist){
				$rs['type']='0';
				$rs['type_val']='0';
				$rs['type_msg']='';
			}
		}else if($islive['type']==3){
			$rs['type_val']=$islive['type_val'];
			$rs['type_msg']='本房间为计时房间，每分钟需支付'.$islive['type_val'].'钻石';
		}
		
		return $rs;
		
	}
	
	/* 用户余额 */
	public function getUserCoin($uid){
		$userinfo=DI()->notorm->users
					->select("coin")
					->where('id=?',$uid)
					->fetchOne();
		return $userinfo;
	}
	
	/* 房间扣费 */
	public function roomCharge($uid,$token,$liveuid,$stream){
		$islive=DI()->notorm->users_live
					->select("islive,type,type_val,starttime")
					->where('uid=? and stream=?',$liveuid,$stream)
					->fetchOne();
		if(!$islive || $islive['islive']==0){
			return 1005;
		}
		
		if($islive['type']==0 || $islive['type']==1 ){
			return 1006;
		}
		
		$userinfo=DI()->notorm->users
					->select("token,expiretime,coin")
					->where('id=?',$uid)
					->fetchOne();
		if($userinfo['token']!=$token || $userinfo['expiretime']<time()){
			return 700;				
		}
		
		$total=$islive['type_val'];
		if($total<=0){
			return 1007;
		}
		if($userinfo['coin'] < $total){
			return 1008;
		}
		$action='roomcharge';
		if($islive['type']==3){
			$action='timecharge';
		}
		
		$giftid=0;
		$giftcount=0;
		$showid=$islive['starttime'];
		$addtime=time();
		/* 更新用户余额 消费 */
		DI()->notorm->users
				->where('id = ?', $uid)
				->update(array('coin' => new NotORM_Literal("coin - {$total}"),'consumption' => new NotORM_Literal("consumption + {$total}")) );
                
        /* 分销 */	
		$this->setAgentProfit($uid,$total);
		/* 分销 */	

		/* 更新直播 映票 累计映票 */
		DI()->notorm->users
				->where('id = ?', $liveuid)
				->update( array('votes' => new NotORM_Literal("votes + {$total}"),'votestotal' => new NotORM_Literal("votestotal + {$total}") ));

		/* 更新直播 映票 累计映票 */
		DI()->notorm->users_coinrecord
				->insert(array("type"=>'expend',"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$giftid,"giftcount"=>$giftcount,"totalcoin"=>$total,"showid"=>$showid,"addtime"=>$addtime ));	
				
		$userinfo2=DI()->notorm->users
					->select('coin')
					->where('id = ?', $uid)
					->fetchOne();	
		$rs['coin']=$userinfo2['coin'];
		return $rs;
		
	}
	
	/* 判断是否僵尸粉 */
	public function isZombie($uid) {
        $userinfo=DI()->notorm->users
					->select("iszombie")
					->where("id='{$uid}'")
					->fetchOne();
		
		return $userinfo['iszombie'];				
    }
	
	/* 僵尸粉 */
    public function getZombie($stream,$where) {
		$ids= DI()->notorm->users_zombie
            ->select('uid')
            ->where("uid not in ({$where})")
			->limit(0,10)
            ->fetchAll();	

		$info=array();

		if($ids){
			$ids2=$this->array_column2($ids,'uid');
			$ids=implode(",",$ids2);
			
			$stream2=explode('_',$stream);
			$showid=$stream2[1];
			
			$info= DI()->notorm->users
				->select('id,user_nicename,avatar,avatar_thumb,sex,consumption,city')
				->where("id in ({$ids}) ")
				->fetchAll();	
			foreach( $info as $k=>$v){
				$v['avatar']=$this->get_upload_path($v['avatar']);
				$v['avatar_thumb']=$this->get_upload_path($v['avatar_thumb']);
				$info[$k]['avatar']=$v['avatar'];
				$info[$k]['avatar_thumb']=$v['avatar_thumb'];
				$level=$this->getLevel($v['consumption']);	
				$v['level']=$level;						
				$info[$k]['level']=$level;						
				$sign = md5($showid.'_'.$v['id']);		
				DI()->redis -> hSet('userlist_'.$stream,$sign,json_encode($v));					
			}		
				 
			$num=count($info);
		}				
		return 	$info;		
    }		

	
	/* 弹窗 */
	public function getPop($touid){
		$info=$this->getUserInfo($touid);
		if(!$info){
			return $info;
		}
		$info['follows']=$this->getFollows($touid);
		$info['fans']=$this->getFans($touid);
		
		$info['consumption']=$this->NumberFormat($info['consumption']);
		$info['votestotal']=$this->NumberFormat($info['votestotal']);
		$info['follows']=$this->NumberFormat($info['follows']);
		$info['fans']=$this->NumberFormat($info['fans']);
		unset($info['province']);
		unset($info['birthday']);
		unset($info['issuper']);
		return $info;
	}
	
	/* 礼物列表 */
	public function getGiftList(){

		$rs=DI()->notorm->gift
			->select("id,type,giftname,needcoin,gifticon")
			->order("orderno asc,addtime desc")
			->fetchAll();
		foreach($rs as $k=>$v){
			$rs[$k]['gifticon']=$this->get_upload_path($v['gifticon']);
		}	

		return $rs;
	}
	
	/* 赠送礼物 */
	public function sendGift($uid,$liveuid,$stream,$giftid,$giftcount) {

		$userinfo=DI()->notorm->users
					->select('coin')
					->where('id = ?', $uid)
					->fetchOne();	

			/* 礼物信息 */
		$giftinfo=DI()->notorm->gift
					->select("giftname,gifticon,needcoin")
					->where('id=?',$giftid)
					->fetchOne();
		if(!$giftinfo){
			/* 礼物信息不存在 */
			return 1002;
		}							 
				
		$total= $giftinfo['needcoin']*$giftcount;
		 
		$addtime=time();
		$type='expend';
		$action='sendgift';
		if($userinfo['coin'] < $total){
			/* 余额不足 */
			return 1001;
		}		
		
		
		/* 更新用户余额 消费 */
		$isuid =DI()->notorm->users
				->where('id = ?', $uid)
				->update(array('coin' => new NotORM_Literal("coin - {$total}"),'consumption' => new NotORM_Literal("consumption + {$total}") ) );
				
		/* 分销 */	
		$this->setAgentProfit($uid,$total);
		/* 分销 */		
		$configpri=$this->getConfigPri();
	
		$anthor_total=$total;
		/* 家族 */
		if($configpri['family_switch']==1){
			$users_family=DI()->notorm->users_family
							->select("familyid")
							->where('uid=? and state=2',$liveuid)
							->fetchOne();

			if($users_family){
				$familyinfo=DI()->notorm->family
							->select("uid,divide_family")
							->where('id=?',$users_family['familyid'])
							->fetchOne();

				$divide_family=$familyinfo['divide_family'];

				/* 主播 */
				$liveuserinfo=DI()->notorm->users
							->select('divide_family')
							->where('id = ?', $liveuid)
							->fetchOne();	

				if($liveuserinfo['divide_family']>=0){
					$divide_family=$liveuserinfo['divide_family'];
					
				}
				$family_total=$total * $divide_family * 0.01;
				
					$anthor_total=$total - $family_total;
					$time=date('Y-m-d',time());
					DI()->notorm->family_profit
						   ->insert(array("uid"=>$liveuid,"time"=>$time,"addtime"=>$addtime,"profit"=>$family_total,"profit_anthor"=>$anthor_total,"total"=>$total,"familyid"=>$users_family['familyid']));

				if($family_total){
					
					DI()->notorm->users
							->where('id = ?', $familyinfo['uid'])
							->update( array( 'votes' => new NotORM_Literal("votes + {$family_total}")  ));
				}
			}	
		}
		

		/* 更新直播 魅力值 累计魅力值 */
		$istouid =DI()->notorm->users
					->where('id = ?', $liveuid)
					->update( array('votes' => new NotORM_Literal("votes + {$anthor_total}"),'votestotal' => new NotORM_Literal("votestotal + {$total}") ));
		
		$stream2=explode('_',$stream);
		$showid=$stream2[1];

		/* 写入记录 或更新 */
		/* $unique=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$giftid,"showid"=>$showid);
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$giftid,"giftcount"=>$giftcount,"totalcoin"=>$total,"showid"=>$showid,"addtime"=>$addtime );
		$update= array('giftcount' => new NotORM_Literal("giftcount + {$giftcount}"),'totalcoin' => new NotORM_Literal("totalcoin + {$total}"));

		$isexit=DI()->notorm->users_coinrecord
				->select("id")
				->where($unique)
				->fetchOne();
		if($isexit){
			$isup=DI()->notorm->users_coinrecord->where('id=?',$isexit['id'])->update($update);
		}else{
			$isup=DI()->notorm->users_coinrecord->insert($insert);
		}	 */		
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$giftid,"giftcount"=>$giftcount,"totalcoin"=>$total,"showid"=>$showid,"addtime"=>$addtime );
		$isup=DI()->notorm->users_coinrecord->insert($insert);

		$userinfo2 =DI()->notorm->users
				->select('consumption,coin')
				->where('id = ?', $uid)
				->fetchOne();	
			 
		$level=$this->getLevel($userinfo2['consumption']);			
		
		/* 清除缓存 */
		$this->delCache("userinfo_".$uid); 
		$this->delCache("userinfo_".$liveuid); 
	
		$votestotal=$this->getVotes($liveuid);
		
		$gifttoken=md5(md5($action.$uid.$liveuid.$giftid.$giftcount.$total.$showid.$addtime.rand(100,999)));
		
		$result=array("uid"=>$uid,"giftid"=>$giftid,"giftcount"=>$giftcount,"totalcoin"=>$total,"giftname"=>$giftinfo['giftname'],"gifticon"=>$this->get_upload_path($giftinfo['gifticon']),"level"=>$level,"coin"=>$userinfo2['coin'],"votestotal"=>$votestotal,"gifttoken"=>$gifttoken);
					
		return $result;
	}		
	
	/* 发送弹幕 */
	public function sendBarrage($uid,$liveuid,$stream,$giftid,$giftcount,$content) {

		$userinfo=DI()->notorm->users
					->select('coin')
					->where('id = ?', $uid)
					->fetchOne();	
		$configpri=$this->getConfigPri();
					 
		$giftinfo=array(
			"giftname"=>'弹幕',
			"gifticon"=>'',
			"needcoin"=>$configpri['barrage_fee'],
		);		
		
		$total= $giftinfo['needcoin']*$giftcount;
		 
		$addtime=time();
		$type='expend';
		$action='sendbarrage';
		if($userinfo['coin'] < $total){
			/* 余额不足 */
			return 1001;
		}		

		/* 更新用户余额 消费 */
		$isuid =DI()->notorm->users
				->where('id = ?', $uid)
				->update(array('coin' => new NotORM_Literal("coin - {$total}"),'consumption' => new NotORM_Literal("consumption + {$total}") ) );

		/* 更新直播 魅力值 累计魅力值 */
		$istouid =DI()->notorm->users
				->where('id = ?', $liveuid)
				->update( array('votes' => new NotORM_Literal("votes + {$total}"),'votestotal' => new NotORM_Literal("votestotal + {$total}") ));
				
		$stream2=explode('_',$stream);
		$showid=$stream2[1];

		/* 写入记录 或更新 */
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$giftid,"giftcount"=>$giftcount,"totalcoin"=>$total,"showid"=>$showid,"addtime"=>$addtime );
		$isup=DI()->notorm->users_coinrecord->insert($insert);
					 
		$userinfo2 =DI()->notorm->users
				->select('consumption,coin')
				->where('id = ?', $uid)
				->fetchOne();	
			 
		$level=$this->getLevel($userinfo2['consumption']);			
		
		/* 清除缓存 */
		$this->delCache("userinfo_".$uid); 
		$this->delCache("userinfo_".$liveuid); 
		
		$votestotal=$this->getVotes($liveuid);
		
		$barragetoken=md5(md5($action.$uid.$liveuid.$giftid.$giftcount.$total.$showid.$addtime.rand(100,999)));
		 
		$result=array("uid"=>$uid,"content"=>$content,"giftid"=>$giftid,"giftcount"=>$giftcount,"totalcoin"=>$total,"giftname"=>$giftinfo['giftname'],"gifticon"=>$giftinfo['gifticon'],"level"=>$level,"coin"=>$userinfo2['coin'],"votestotal"=>$votestotal,"barragetoken"=>$barragetoken);
		
		return $result;
	}			
	
	/* 设置/取消 管理员 */
	public function setAdmin($liveuid,$touid){
					
		$isexist=DI()->notorm->users_livemanager
					->select("*")
					->where('uid=? and  liveuid=?',$touid,$liveuid)
					->fetchOne();			
		if(!$isexist){
			$count =DI()->notorm->users_livemanager
						->where('liveuid=?',$liveuid)
						->count();	
			if($count>=5){
				return 1004;
			}		
			$rs=DI()->notorm->users_livemanager
					->insert(array("uid"=>$touid,"liveuid"=>$liveuid) );	
			if($rs!==false){
				return 1;
			}else{
				return 1003;
			}				
			
		}else{
			$rs=DI()->notorm->users_livemanager
				->where('uid=? and  liveuid=?',$touid,$liveuid)
				->delete();		
			if($rs!==false){
				return 0;
			}else{
				return 1003;
			}						
		}
	}
	
	/* 管理员列表 */
	public function getAdminList($liveuid){
		$rs=DI()->notorm->users_livemanager
						->select("uid")
						->where('liveuid=?',$liveuid)
						->fetchAll();	
		foreach($rs as $k=>$v){
			$rs[$k]=$this->getUserInfo($v['uid']);
		}				
		return $rs;
	}
	
	/* 举报 */
	public function setReport($uid,$touid,$content){
		return  DI()->notorm->users_report
				->insert(array("uid"=>$uid,"touid"=>$touid,'content'=>$content,'addtime'=>time() ) );	
	}
	
	/* 主播总映票 */
	public function getVotes($liveuid){
		$userinfo=DI()->notorm->users
					->select("votestotal")
					->where('id=?',$liveuid)
					->fetchOne();	
		return $userinfo['votestotal'];					
	}
	
	/* 超管关闭直播间 */
	public function superStopRoom($uid,$token,$liveuid,$type){
		
		$userinfo=DI()->notorm->users
					->select("token,expiretime,issuper")
					->where('id=? ',$uid)
					->fetchOne();
		if($userinfo['token']!=$token || $userinfo['expiretime']<time()){
			return 700;				
		} 	
		
		if($userinfo['issuper']==0){
			return 1001;
		}
		
		if($type==1){
			/* 关闭并禁用 */
			DI()->notorm->users->where('id=? ',$liveuid)->update(array('user_status'=>0));
		}
		
	
		$info=DI()->notorm->users_live
				->select("uid,showid,starttime,title,province,city,stream,lng,lat,type,type_val")
				->where('uid=? and islive="1"',$liveuid)
				->fetchOne();
		if($info){
			$nowtime=time();
			$stream=$info['stream'];
			$info['endtime']=$nowtime;
			
			$nums=DI()->redis->hlen('userlist_'.$stream);
			DI()->redis->hDel("livelist",$liveuid);
			DI()->redis->delete($liveuid.'_zombie');
			DI()->redis->delete($liveuid.'_zombie_uid');
			DI()->redis->delete('attention_'.$liveuid);
			DI()->redis->delete('userlist_'.$stream);

			$info['nums']=$nums;			
			$result=DI()->notorm->users_liverecord->insert($info);	
		}
		DI()->notorm->users_live->where('uid=?',$liveuid)->delete();
		
		return 0;
		
	}
	
	/* 拍品信息 */
	public function getAuction($id){
		$info=DI()->notorm->auction
				->select("*")
				->where('id=?',$id)
				->fetchOne();
		if($info){
			$info['thumb']=$this->get_upload_path($info['thumb']);
		}
		return $info;
	}
	
	/* 竞拍 */
	public function setAuction($uid,$auctionid){
		$rs=array(
			'code'=>0,
			'msg'=>0,
			'info'=>array(),
		);
		$auctioninfo=DI()->notorm->auction
				->select("*")
				->where('id=?',$auctionid)
				->fetchOne();
		if(!$auctioninfo){
			$rs['code']=1001;
			$rs['msg']='竞拍信息不存在';
			return $rs;
		}
		
		if($auctioninfo['status']==1){
			$rs['code']=1002;
			$rs['msg']='竞拍已结束';
			return $rs;
		}
		
		$isexist=DI()->notorm->users_coinrecord
					->select("id")
					->where('action="price_bond" and uid=? and giftid=? ',$uid,$auctionid)
					->fetchOne();
		if(!$isexist){
			$rs['code']=1003;
			$rs['msg']='请先缴纳保证金';
			return $rs;
		}
		
		$nowtime=time();
		$insert=array(
			'uid'=>$uid,
			'auctionid'=>$auctionid,
			'price_fare'=>$auctioninfo['price_fare'],
			'addtime'=>$nowtime,
		);
		
		DI()->notorm->auction_record
				->insert($insert);
				
		DI()->notorm->auction
				->where('id=?',$auctionid)
				->update( array( 'bid_price' => new NotORM_Literal("bid_price + {$auctioninfo['price_fare']}"),'bid_uid'=>$uid ) );
		$bid_price=$auctioninfo['bid_price'] + $auctioninfo['price_fare'];
		$rs['info']=array(
			'bid_price'=> $bid_price,
		);		
		return $rs;
		
	}
	
	/* 缴纳保证金 */
	public function setBond($uid,$auctionid){
		
		$auctioninfo=DI()->notorm->auction
				->select("*")
				->where('id=?',$auctionid)
				->fetchOne();
		if(!$auctioninfo){
			return 1001;
		}
		
		if($auctioninfo['status']!=0){
			return 1002;
		}
		
		$userinfo=DI()->notorm->users
					->select('coin')
					->where('id = ?', $uid)
					->fetchOne();	
					
		$total= $auctioninfo['price_bond'];
		 
		$addtime=time();
		$type='expend';
		$action='price_bond';
		
		if($userinfo['coin'] < $total){
			/* 余额不足 */
			return 1003;
		}		

		/* 更新用户余额 消费 */
		$isuid =DI()->notorm->users
				->where('id = ?', $uid)
				->update(array('coin' => new NotORM_Literal("coin - {$total}") ) );
				
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$uid,"giftid"=>$auctioninfo['id'],"giftcount"=>1,"totalcoin"=>$total,"showid"=>0,"addtime"=>$addtime );
		$isup=DI()->notorm->users_coinrecord->insert($insert);
		
		
		$userinfo2 =DI()->notorm->users
				->select('consumption,coin')
				->where('id = ?', $uid)
				->fetchOne();		
		
		/* 清除缓存 */
		$this->delCache("userinfo_".$uid); 
		
		$result=array("coin"=>$userinfo2['coin']);
		
					
		return $result;
	}
	
	/* 竞拍结束 */
	public function auctionEnd($uid,$auctionid){
		$auctioninfo=DI()->notorm->auction
				->select("*")
				->where('id=? and uid=?',$auctionid,$uid)
				->fetchOne();
		if(!$auctioninfo){
			return 1001;
		}
		
		if($auctioninfo['status']!=0){
			return 1002;
		}
		$addtime=time();
		/* 退还保证金 */
		$total=$auctioninfo['price_bond'];
		$list=DI()->notorm->users_coinrecord
					->select("uid")
					->where('action="price_bond" and giftid=? ',$auctioninfo['id'])
					->fetchAll();
					
		foreach($list as $k=>$v){
			if($v['uid'] != $auctioninfo['bid_uid']){
				DI()->notorm->users
					->where('id = ?', $v['uid'])
					->update(array('coin' => new NotORM_Literal("coin + {$total}") ) );
					
				$insert=array("type"=>'income',"action"=>'price_bond_return',"uid"=>$uid,"touid"=>$uid,"giftid"=>$auctioninfo['id'],"giftcount"=>1,"totalcoin"=>$total,"showid"=>0,"addtime"=>$addtime );
				DI()->notorm->users_coinrecord->insert($insert);
				
			}
		}
		
		DI()->notorm->auction
				->where('id=?',$auctioninfo['id'])
				->update( array( 'status' => 1,'bid_time' => $addtime ) );
				
		$rs=array(
			'bid_price'=>$auctioninfo['bid_price'],
			'bid_uid'=>'0',
			'user_nicename'=>'',
			'avatar'=>'',
		);
		if($auctioninfo['bid_uid']){
			$userinfo=$this->getUserInfo($auctioninfo['bid_uid']);
		
			$rs=array(
				'bid_price'=>$auctioninfo['bid_price'],
				'bid_uid'=>$auctioninfo['bid_uid'],
				'user_nicename'=>$userinfo['user_nicename'],
				'avatar'=>$userinfo['avatar'],
			);
			
		}
		
		
		return $rs;
	}
	
	/* 支付竞拍费用 */
	public function setBidPrice($uid,$auctionid){
		
		$auctioninfo=DI()->notorm->auction
				->select("*")
				->where('id=?',$auctionid)
				->fetchOne();
		if(!$auctioninfo){
			return 1001;
		}
		
		if($auctioninfo['status']==0){
			return 1002;
		}
		
		if($auctioninfo['bid_uid'] !=$uid){
			return 1005;
		}
		
		if($auctioninfo['status']==2){
			return 1004;
		}
		
		$userinfo=DI()->notorm->users
					->select('coin')
					->where('id = ?', $uid)
					->fetchOne();	
					
		$total= $auctioninfo['bid_price'];
		 
		$addtime=time();
		$type='expend';
		$action='bid_price';
		$liveuid=$auctioninfo['uid'];
		
		$stream2=explode('_',$auctioninfo['stream']);
		$showid=$stream2[1];
		
		if($userinfo['coin'] < $total){
			/* 余额不足 */
			return 1003;
		}		

		/* 更新用户余额 消费 */
		$isuid =DI()->notorm->users
				->where('id = ?', $uid)
				->update(array('coin' => new NotORM_Literal("coin - {$total}"),'consumption' => new NotORM_Literal("consumption + {$total}") ) );
		
		/* 更新主播映票 */
		$isuid =DI()->notorm->users
				->where('id = ?', $liveuid)
				->update(array('votes' => new NotORM_Literal("votes + {$total}"),'votestotal' => new NotORM_Literal("votestotal + {$total}") ) );
				
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$auctioninfo['id'],"giftcount"=>1,"totalcoin"=>$total,"showid"=>$showid,"addtime"=>$addtime );
		$isup=DI()->notorm->users_coinrecord->insert($insert);
		
		DI()->notorm->auction
				->where('id=?',$auctioninfo['id'])
				->update( array(  'status' => 2,'bid_paytime' => $addtime ) );
		

		// 退还保证金
		$isexist=DI()->notorm->users_coinrecord
					->select("uid")
					->where('action="price_bond" and giftid=? and uid=?',$auctioninfo['id'],$uid)
					->fetchOne();
		if($isexist){
			DI()->notorm->users
				->where('id = ?', $uid)
				->update(array('coin' => new NotORM_Literal("coin + {$auctioninfo['price_bond']}") ) );
				
			$insert=array("type"=>'income',"action"=>'price_bond_return',"uid"=>$uid,"touid"=>$uid,"giftid"=>$auctioninfo['id'],"giftcount"=>1,"totalcoin"=>$auctioninfo['price_bond'],"showid"=>0,"addtime"=>$addtime );
			DI()->notorm->users_coinrecord->insert($insert);

		}
		
		
		$userinfo2 =DI()->notorm->users
				->select('consumption,coin')
				->where('id = ?', $uid)
				->fetchOne();	
			 
		$level=$this->getLevel($userinfo2['consumption']);		
		
		/* 清除缓存 */
		$this->delCache("userinfo_".$uid); 
		
		$result=array(
			"coin"=>$userinfo2['coin'],
			"level"=>$level,
		);
		
					
		return $result;		
		
	}
	/* 检测竞拍信息 */
	public function checkAuction($liveuid){
		$rs=array(
			'isauction'=>'0',
			'id'=>'0',
			'title'=>'',
			'thumb'=>'',
			'price_start'=>'0',
			'price_bond'=>'0',
			'price_fare'=>'0',
			'bid_price'=>'0',
			'bid_uid'=>'0',
			'user_nicename'=>'',
			'avatar'=>'',
			'long'=>'0',
			'pay_long'=>'0',
		);
		
		$auctioninfo=DI()->notorm->auction
				->select("*")
				->where(' status=0 and uid=?',$liveuid)
				->fetchOne();
		if(!$auctioninfo){
			return $rs;
		}
		
		
		
		$nowtime=time();

		$cha=$nowtime - $auctioninfo['addtime'];
		
		if($cha < $auctioninfo['long']){
			/* 竞拍中 */
			$rs['long']=$auctioninfo['long']-$cha;
		}

		
		$rs['isauction']='1';
		$rs['id']=$auctioninfo['id'];
		$rs['title']=$auctioninfo['title'];
		$rs['thumb']=$this->get_upload_path($auctioninfo['thumb']);
		$rs['price_start']=$auctioninfo['price_start'];
		$rs['price_bond']=$auctioninfo['price_bond'];
		$rs['price_fare']=$auctioninfo['price_fare'];
		$rs['bid_price']=$auctioninfo['bid_price'];
		$rs['bid_uid']=$auctioninfo['bid_uid'];
		$rs['pay_long']=$auctioninfo['pay_long'];
		if($auctioninfo['bid_uid']){
			$userinfo=$this->getUserInfo($auctioninfo['bid_uid']);
			$rs['user_nicename']=$userinfo['user_nicename'];
			$rs['avatar']=$userinfo['avatar'];
		}
		
		return $rs;
	}
	
	
	/* 竞拍异常结束 返还 */
	public function stopAuction($uid){
		$auctionlist=DI()->notorm->auction
				->select("*")
				->where(' status=0 and uid=?',$uid)
				->fetchAll();
		$addtime=time();
		foreach($auctionlist as $k=>$v){
			DI()->notorm->auction
				->where(' id=?',$v['id'])
				->update( array('status'=>-2) );
			$total=$v['price_bond'];
			$list=DI()->notorm->users_coinrecord
					->select("uid")
					->where('action="price_bond" and giftid=? ',$v['id'])
					->fetchAll();
					
			foreach($list as $k2=>$v2){
				DI()->notorm->users
					->where('id = ?', $v2['uid'])
					->update(array('coin' => new NotORM_Literal("coin + {$total}") ) );
					
				$insert=array("type"=>'income',"action"=>'price_bond_return',"uid"=>$v2['uid'],"touid"=>$v2['uid'],"giftid"=>$v['id'],"giftcount"=>1,"totalcoin"=>$total,"showid"=>0,"addtime"=>$addtime );
				DI()->notorm->users_coinrecord->insert($insert);
			}
		}	
	}
	
}
