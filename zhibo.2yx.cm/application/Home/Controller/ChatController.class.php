<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Home\Controller;
use Common\Controller\HomebaseController; 
/**
 * 直播页面
 */
class ChatController extends HomebaseController {
   //首页
	public function index() 
	{
		$uid=$_GET["uid"];
		$token=$_GET["token"];
		$config=$this->config;
		$stream= $_GET["stream"];
		$anchorid=(int)$_GET["roomid"];
		if($config['maintain_switch']==1){
			$this->assign('jumpUrl',__APP__);
			$this->error(nl2br($config['maintain_tips']));
		}
		$getConfigPri=getConfigPri();
		
		$this->assign("configj",json_encode($config));
		$this->assign("getConfigPri",$getConfigPri);
		$User=M('users');
		$Gift=M('gift');
		$Live=M('users_live');
		$Car=M('car');
		$Coinrecord=M('users_coinrecord');
		$nowtime=time();		
		/* 主播信息 */
		
		$anchorinfo=getUserInfo($anchorid);
		if(!$anchorinfo){
			$this->error('主播不存在');
		}
		$anchorinfo['level']=getLevel($anchorinfo['consumption']);
		$anchorinfo['follows']=getFollownums($anchorinfo['id']);
		$anchorinfo['fans']=getFansnums($anchorinfo['id']);
		 
		$anchorinfo['stream']=$stream;
		
		$anchorinfo['token']=$token;
		$this->assign("anchorinfo",$anchorinfo);
		$this->assign("anchorinfoj",json_encode($anchorinfo) );	
		$liveinfo=$Live->where("uid='{$anchorinfo['id']}' and islive=1")->order("islive desc")->limit(1)->find();
		if($liveinfo['isvideo']==0){
			$liveinfo['pull']=PrivateKeyA('rtmp',$liveinfo['stream'],0);
		}
		$this->assign("liveinfo",$liveinfo);
		$this->assign("liveinfoj",json_encode($liveinfo));
		if($uid>0)
		{
			/*是否踢出房间*/
			$redis = connectionRedis();
			$iskick=$redis  -> hGet($anchorinfo['id'].'kick',$uid);
			$nowtime=time();
			if($iskick>$nowtime)
			{
				$surplus=$iskick-$nowtime;
				$this->assign('jumpUrl',__APP__);
				$this->error('您已被踢出房间，剩余'.$surplus.'秒');
			}else
			{
				$redis  -> hdel($anchorinfo['id'].'kick',$uid);
			}
			/*身份判断*/
			$getisadmin=getIsAdmin($uid,$anchorinfo['id']);
			/*该主播是否被禁用*/
			$isBan=isBan($anchorinfo['id']);
			if($isBan==0)
			{
				$this->assign('jumpUrl',__APP__);
				$this->error('该主播已经被禁止直播');
			}
			$isBan=isBan($uid);
			if($isBan==0)
			{
				$this->assign('jumpUrl',__APP__);
				$this->error('你的账号已经被禁用');
			}
			/*进入房间设置redis*/
			$userinfo=$User->where("id=".$uid)->field("id,issuper")->find();
			if($userinfo['issuper']==1){
				$redis  -> hset('super',$userinfo['id'],'1');
			}else{
				$redis  -> hDel('super',$userinfo['id']);
			}
			$redis -> close();
		}
		else
		{
			$getisadmin=10;
		}
		$this->assign('identity',$getisadmin);
		/* 是否关注 */
		$isattention=isAttention($uid,$anchorinfo['id']);
		$this->assign("isattention",$isattention);
		$attention_type = $isattention ? "已关注" : "+关注" ;
		$this->assign("attention_type",$attention_type);
		$this->assign("anchorid",$anchorid);
		/* 礼物信息 */
		$giftinfo=$Gift->field("*")->order("orderno asc")->select();
		$this->assign("giftinfo",$giftinfo);
		$giftinfoj=array();
		foreach($giftinfo as $k=>$v)
		{
			$giftinfoj[$v['id']]=$v;
		}
		$this->assign("giftinfoj",json_encode($giftinfoj));
		$this->a='aaaaa';
		$configpri=M("config_private")->where("id=1")->find();
		/* 判断 播流还是推流 */
		$isplay=0;
		if($uid==$anchorinfo['id'])
		{ 
			$checkToken=checkToken($uid,$token);
			if($checkToken==700){
				$this->assign('jumpUrl',__APP__);
				$this->error('登陆过期，请重新登陆');
			} 
			if($configpri['auth_islimit']==1)
			{
				$auth=M("users_auth")->field("status")->where("uid='{$uid}'")->find();
				if(!$auth || $auth['status']!=1)
				{
					$this->assign('jumpUrl',__APP__);
					$this->error("请先进行身份认证");
				}
			}	
			if($configpri['level_islimit']==1)
			{
				if($anchorinfo['level']<$configpri['level_limit'])
				{
					$this->assign('jumpUrl',__APP__);
					$this->error('等级小于'.$configpri['level_limit'].'级，不能直播');
				}						
			}
			$token=getUserToken($uid);
			$this->assign('token',$token);
			/* 流地址 */	
			$push=PrivateKeyA('rtmp',$stream,1);
			
			/* if($uid=8290){
				$push=array(
					'stream'=>'stream',
					'cdn'=>'rtmp://5761.lsspublish.aodianyun.com/1198',
				);
			} */
			$this->assign('push',$push);
			//$this->display('player');
			$isplay=1;
		}
		/* else
		{ 
			$this->display();
		} */
		$this->assign('isplay',$isplay);
		$this->display();
  }
  
  public function setNodeInfo() {
		/* 当前用户信息 */
		$uid=I("uid");
		$showid=I('showid');
		$token=I("token");
		$stream=I('stream');
		if($uid>0){
			$info=getUserInfo($uid);	
			$info['liveuid']=$showid;
			$info['sign'] = md5($showid.'_'.$info['id']);
			$info['token']=$token;

			$carinfo=getUserCar($uid);
			$info['car']=$carinfo;

			if($uid==$showid)
			{
				$info['userType']=50;
			}
			else
			{
				$info['userType']=40;
			}
		}else{
			/* 游客 */
			$sign= mt_rand(1000,9999);
			$info['id'] = '-'.$sign;
			$info['user_nicename'] = '游客'.$sign;
			$info['avatar'] = '';
			$info['avatar_thumb'] = '';
			$info['sex'] = '0';
			$info['signature'] = '0';
			$info['consumption'] = '0';
			$info['votestotal'] = '0';
			$info['province'] = '';
			$info['city'] = '';
			$info['level'] = '0';
			$info['sign'] = md5($showid.'_'.$sign);
			$info['token']=$info['sign'];
			$info['liveuid']=$showid;
			$info['userType']=0;
			$info['vip']=array('type'=>'0');
			$info['car']=array(
							'id'=>'0',
							'swf'=>'',
							'swftime'=>'0',
							'words'=>'',
						);
			$token =$info['sign'];
		}	
		$info['roomnum']=$showid;
		//判断该房间是否在直播
		$live=M("users_live")->where("uid=".$showid." and islive=1")->find();
		if($live)
		{
			$info['stream']=$live['stream'];
		}
		else
		{
			if($uid==$showid)
			{
				$info['stream']=$stream;
			}
			else
			{
				$info['stream']=$showid."_".$showid;
			}
		}
		$redis = connectionRedis();
		$redis  -> set($token,json_encode($info));
		$redis -> close();	
		/*判断改房间是否开启僵尸粉*/
		$iszombie=isZombie($showid);
		$data=array(
			'error'=>0,
			'userinfo'=>$info,
			'iszombie'=>$iszombie,
		);
		echo  json_encode($data);	
		exit;
    }
	//开播设置
	 
  //帮助
  public function help()  
  {

	 $this->display();
  }  
}



