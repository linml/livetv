<?php
/**
 * 分销
 */
namespace Appapi\Controller;
use Common\Controller\HomebaseController;
class AgentController extends HomebaseController {
	
	function index(){       
		$uid=I("uid");
		$token=I("token");
		
		if(checkToken($uid,$token)==700){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		} 
		  
		$nowtime=time();
		$id=1;
		$User=M("users");
		$userinfo=$User->field("id,user_nicename,avatar,code")->where("id={$uid}")->find();
		
		if($userinfo['code']==''){
			$code=createCode();
			$userinfo['code']=$code;
			$User->where("id={$uid}")->save(array("code"=>$code));
		}

		$code_a=str_split($userinfo['code']);

		$this->assign("code_a",$code_a);
		$agentinfo=array();
		
		$users_agent=M("users_agent")->where("uid={$uid}")->find();
		if($users_agent){
			$agentinfo=$User->field("id,user_nicename,avatar")->where("id={$users_agent['one_uid']}")->find();
		}
		
		$Agent_profit=M("users_agent_profit");
		
		$agentprofit=$Agent_profit->where("uid={$uid}")->find();
		
		$one_profit=$agentprofit['one_profit'];
		$two_profit=$agentprofit['two_profit'];
		if(!$one_profit){
			$one_profit=0;
		}
		if(!$two_profit){
			$two_profit=0;
		}

		$agnet_profit=array(
			'one_profit'=>NumberFormat2($one_profit),
			'two_profit'=>NumberFormat2($two_profit),
			'three_profit'=>0,
		);

		$this->assign("uid",$uid);
		$this->assign("token",$token);
		$this->assign("userinfo",$userinfo);
		$this->assign("agentinfo",$agentinfo);
		$this->assign("agnet_profit",$agnet_profit);

		$this->display();
	    
	}
	
	function agent(){
		$uid=I("uid");
		$token=I("token");
		
		if(checkToken($uid,$token)==700){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		} 
		  
		$nowtime=time();
		$id=1;
		$User=M("users");

		$agentinfo=array();
		
		$users_agent=M("users_agent")->where("uid={$uid}")->find();
		if($users_agent){
			$agentinfo=$User->field("id,user_nicename,avatar,code")->where("id={$users_agent['one_uid']}")->find();
			$code_a=str_split($agentinfo['code']);

			$this->assign("code_a",$code_a);
		}
	
		
		$this->assign("uid",$uid);
		$this->assign("token",$token);

		$this->assign("agentinfo",$agentinfo);

		$this->display();
	}
	
	function setAgent(){
		$uid=I("uid");
		$token=I("token");
		
		$rs=array('code'=>0,'info'=>array(),'msg'=>'设置成功');
		
		if(checkToken($uid,$token)==700){
			$rs['code']=700;
			$rs['msg']='您的登陆状态失效，请重新登陆！';
			echo json_encode($rs);
			exit;
		} 
		
		$code=I("code");

		if($code==""){
			$rs['code']=1001;
			$rs['msg']='邀请码不能为空';
			echo json_encode($rs);
			exit;
		}
		
		$User=M('users');
		$Users_agent=M("users_agent");
		
		$isexist=$Users_agent->where("uid={$uid}")->find();
		if($isexist){
			$rs['code']=1001;
			$rs['msg']='已设置';
			echo json_encode($rs);
			exit;
		}
		
		$oneinfo=$User->field("id")->where("code='{$code}'")->find();
		if(!$oneinfo){
			$rs['code']=1002;
			$rs['msg']='邀请码错误';
			echo json_encode($rs);
			exit;
		}
		
		if($oneinfo['id']==$uid){
			$rs['code']=1003;
			$rs['msg']='不能填写自己的邀请码';
			echo json_encode($rs);
			exit;
		}
		
		$one_agent=$Users_agent->where("uid={$oneinfo['id']}")->find();
		if(!$one_agent){
			$one_agent=array(
				'uid'=>$oneinfo['id'],
				'one_uid'=>0,
				'two_uid'=>0,
			);
		}else{

			if($one_agent['one_uid']==$uid||$one_agent['two_uid']==$uid){
				$rs['code']=1004;
				$rs['msg']='您已经是该用户的上级';
				echo json_encode($rs);
				exit;
			}
		}
		
		$data=array(
			'uid'=>$uid,
			'one_uid'=>$one_agent['uid'],
			'two_uid'=>$one_agent['one_uid'],
			'three_uid'=>$one_agent['two_uid'],
			'addtime'=>time(),
		);
		$Users_agent->add($data);

		echo json_encode($rs);
		exit;
	}

	function quit(){
		$uid=I("uid");
		$token=I("token");
		
		$rs=array('code'=>0,'info'=>array(),'msg'=>'退出成功');
		
		if(checkToken($uid,$token)==700){
			$rs['code']=700;
			$rs['msg']='您的登陆状态失效，请重新登陆！';
			echo json_encode($rs);
			exit;
		} 

		$Users_agent=M("users_agent");
		
		$isexist=$Users_agent->where("uid={$uid}")->delete();

		echo json_encode($rs);
		exit;
	}
	
	function one(){
		$uid=I("uid");
		$token=I("token");
		
		if(checkToken($uid,$token)==700){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		} 
		  
		$Agent_profit=M("users_agent_profit_recode");
		
		$list=$Agent_profit->field("uid,sum(one_profit) as total")->where("one_uid={$uid}")->group("uid")->order("addtime desc")->limit(0,50)->select();
		foreach($list as $k=>$v){
			$list[$k]['userinfo']=getUserInfo($v['uid']);
			$list[$k]['total']=NumberFormat($v['total']);
		}
		$this->assign("uid",$uid);
		$this->assign("token",$token);
		$this->assign("list",$list);
		$this->display();
	}

	function one_more(){
		$uid=I("uid");
		$token=I("token");
		
		$result=array(
			'data'=>array(),
			'nums'=>0,
			'isscroll'=>0,
		);
		
		if(checkToken($uid,$token)==700){
			echo json_encode($result);
			exit;
		} 
		
		
		$p=I('page');
		$pnums=50;
		$start=($p-1)*$pnums;

		$Agent_profit=M("users_agent_profit_recode");
		
		$list=$Agent_profit->field("uid,sum(one_profit) as total")->where("one_uid={$uid}")->group("uid")->order("addtime desc")->limit($start,$pnums)->select();
		foreach($list as $k=>$v){
			$list[$k]['userinfo']=getUserInfo($v['uid']);
			$list[$k]['total']=NumberFormat($v['total']);
		}
		
		$nums=count($list);
		if($nums<$pnums){
			$isscroll=0;
		}else{
			$isscroll=1;
		}
		
		$result=array(
			'data'=>$list,
			'nums'=>$nums,
			'isscroll'=>$isscroll,
		);

		echo json_encode($result);
		exit;
	}

	function two(){
		$uid=I("uid");
		$token=I("token");
		
		if(checkToken($uid,$token)==700){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		} 
		
		$Agent_profit=M("users_agent_profit_recode");
		
		$list=$Agent_profit->field("uid,sum(two_profit) as total")->where("two_uid={$uid}")->group("uid")->order("addtime desc")->limit(0,50)->select();
		foreach($list as $k=>$v){
			$list[$k]['userinfo']=getUserInfo($v['uid']);
			$list[$k]['total']=NumberFormat($v['total']);
		}
		$this->assign("uid",$uid);
		$this->assign("token",$token);
		$this->assign("list",$list);
		$this->display();
	}

	function two_more(){
		$uid=I("uid");
		$token=I("token");
		
		$result=array(
			'data'=>array(),
			'nums'=>0,
			'isscroll'=>0,
		);
		
		if(checkToken($uid,$token)==700){
			echo json_encode($result);
			exit;
		} 
		
		
		$p=I('page');
		$pnums=50;
		$start=($p-1)*$pnums;

		$Agent_profit=M("users_agent_profit_recode");
		
		$list=$Agent_profit->field("uid,sum(two_profit) as total")->where("two_uid={$uid}")->group("uid")->order("addtime desc")->limit($start,$pnums)->select();
		foreach($list as $k=>$v){
			$list[$k]['userinfo']=getUserInfo($v['uid']);
			$list[$k]['total']=NumberFormat($v['total']);
		}
		
		$nums=count($list);
		if($nums<$pnums){
			$isscroll=0;
		}else{
			$isscroll=1;
		}
		
		$result=array(
			'data'=>$list,
			'nums'=>$nums,
			'isscroll'=>$isscroll,
		);

		echo json_encode($result);
		exit;
	}
}