<?php

/* 竞拍 */
namespace Appapi\Controller;

use Common\Controller\HomebaseController;

class AuctionController extends HomebaseController {

	public function index() {
		$uid=checkNull(I("uid"));
		$token=checkNull(I("token"));
		$addr=checkNull(I("addr"));
		$stream=checkNull(I("stream"));
		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		}
		
		$this->assign('uid',$uid);
		$this->assign('token',$token);
		$this->assign('addr',$addr);
		$this->assign('stream',$stream);
		
		$this->display();
	}
	
	function add_post(){
		$rs=array('code'=>0,'msg'=>'','info'=>array());
		$uid=checkNull(I("uid"));
		$token=checkNull(I("token"));
		$title=checkNull(I("title"));
		$thumb=checkNull(I("thumb"));
		$time=checkNull(I("time"));
		$addr=checkNull(I("addr"));
		$stream=checkNull(I("stream"));
		$contacts=checkNull(I("contacts"));
		$contacts_mobile=checkNull(I("contacts_mobile"));
		$price_start=checkNull(I("price_start"));
		$price_bond=checkNull(I("price_bond"));
		$price_fare=checkNull(I("price_fare"));
		$longtime=checkNull(I("longtime"));
		$delayed_time=checkNull(I("delayed_time"));
		$delayed_nums=checkNull(I("delayed_nums"));
		$des=checkNull(I("des"));
		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$rs['code']=700;
			$rs['msg']='您的登陆状态失效，请重新登陆！';
			echo json_encode($rs);
			exit;
		}
		$Auction=M("auction");
		$isexist=$Auction->where("uid={$uid} and status=0")->find();
		if($isexist){
			$rs['code']=1002;
			$rs['msg']='同一时间，只能发布一个';
			echo json_encode($rs);
			exit;
		}
		
		if($thumb==''){
			$rs['code']=1001;
			$rs['msg']='请上传图片';
			echo json_encode($rs);
			exit;
		}
		
		if($title==''){
			$rs['code']=1001;
			$rs['msg']='请输入拍品名称';
			echo json_encode($rs);
			exit;
		}
		
		if($time==''){
			$rs['code']=1001;
			$rs['msg']='请选择约会时间';
			echo json_encode($rs);
			exit;
		}
		
		if($addr==''){
			$rs['code']=1001;
			$rs['msg']='请选择地点';
			echo json_encode($rs);
			exit;
		}
		
		if($contacts==''){
			$rs['code']=1001;
			$rs['msg']='请填写联系人';
			echo json_encode($rs);
			exit;
		}
		
		if($contacts_mobile==''){
			$rs['code']=1001;
			$rs['msg']='请填写联系电话';
			echo json_encode($rs);
			exit;
		}
		
		if(!preg_match("/^1[3|4|5|7|8]\d{9}$/",$contacts_mobile)){
			$rs['code']=1001;
			$rs['msg']='请填写正确手机号码';
			echo json_encode($rs);
			exit;
		}
		if($price_start==''){
			$rs['code']=1001;
			$rs['msg']='请填写起拍价';
			echo json_encode($rs);
			exit;
		}
		if($price_bond==''){
			$rs['code']=1001;
			$rs['msg']='请填写保证金';
			echo json_encode($rs);
			exit;
		}
		if($price_fare==''){
			$rs['code']=1001;
			$rs['msg']='请填写加价幅度';
			echo json_encode($rs);
			exit;
		}
		if($longtime==''){

			$rs['code']=1001;
			$rs['msg']='请选择竞拍时间';
			echo json_encode($rs);
			exit;
		}
		/* if($delayed_time==''){
			$rs['code']=1001;
			$rs['msg']='请选择延时值';
			echo json_encode($rs);
			exit;
		} */
		/* if($delayed_nums==''){
			$rs['code']=1001;
			$rs['msg']='请选择最大延时';
			echo json_encode($rs);
			exit;
		} */
		
		/* if($des==''){
			$rs['code']=1001;
			$rs['msg']='请填写描述';
			echo json_encode($rs);
			exit;
		} */
		
		$nowtime=time();
		
		$long=$longtime * 60 * 60;
		//$long=60;
		$pay_long=60*15;
		
		
		$data=array(
			'uid'=>$uid,
			'title'=>$title,
			'thumb'=>$thumb,
			'time'=>$time,
			'addr'=>$addr,
			'stream'=>$stream,
			'contacts'=>$contacts,
			'contacts_mobile'=>$contacts_mobile,
			'price_start'=>$price_start,
			'price_bond'=>$price_bond,
			'price_fare'=>$price_fare,
			'bid_price'=>$price_start,
			'longtime'=>$longtime,
			'long'=>$long,
			'delayed_time'=>$delayed_time,
			'delayed_nums'=>$delayed_nums,
			'des'=>$des,
			'addtime'=>$nowtime,
			'pay_long'=>$pay_long,
		);
		
		
		
		$result=$Auction->add($data);
		
		if(!$result){
			$rs['code']=1002;
			$rs['msg']='发布失败';
			echo json_encode($rs);
			exit;
		}
		
		$rs['info']=$result;
		echo json_encode($rs);
		exit;
	}
	
	/* 图片上传 */
	public function upload(){
    	$config=array(
			    'replace' => true,
    			'rootPath' => './'.C("UPLOADPATH"),
    			'savePath' => 'auction/',
    			'maxSize' => 0,//500K
    			'saveName'   =>    array('uniqid',''),
    			'exts'       =>    array('jpg', 'png', 'jpeg'),
    			'autoSub'    =>    false,
    	);

    	$upload = new \Think\Upload($config);//
    	$info=$upload->upload();

    	//开始上传
    	if ($info) {
			//上传成功
			$oriName = $_FILES['file']['name'];
			//写入附件数据库信息
			$first=array_shift($info);
			if(!empty($first['url'])){
				$url=$first['url'];				
			}else{
				$url=C("TMPL_PARSE_STRING.__UPLOAD__").$config['savePath'].$first['savename'];
				$configpub=getConfigPub();
				
				$url=$configpub['site'].$url;
			}
    		echo json_encode(array("ret"=>200,'data'=>array("url"=>$url),'msg'=>''));
    	} else {
    		//上传失败，返回错误
			echo json_encode(array("ret"=>0,'file'=>'','msg'=>$upload->getError()));

    	}	
		exit;
	}	
	
	/* 详情页 */
	function detail(){
		$id=I("id");
		$uid=I("uid");
		$token=I("token");
		
		$Auction=M("auction");
		
		$info=$Auction->where("id={$id}")->find();
		$info['surplus']=60;
		$nowtime=time();
		if($info['status']==0){
			$cha=$nowtime - $info['addtime'];
			
			if($cha < $info['long']){
				/* 竞拍中 */
				$info['surplus']=$info['long']-$cha;
			}else{
				$info['status']=1;
			}
		}
		$info['bond_status']=0;
		if($info['uid']!==$uid){
			$isexist=M("users_coinrecord")->field("id")->where("action='price_bond' and uid={$uid} and giftid={$id} ")->find();

			if($isexist){
				$info['bond_status']=1;
			}
		}
		
		
		$this->assign('uid',$uid);
		$this->assign('token',$token);
		$this->assign('info',$info);
		$this->assign('infoj',json_encode($info));
		

		
		$this->display();
		
	}
	
	/* 保证金 */
	function bond(){
		$id=I("id");
		$uid=I("uid");
		$token=I("token");
		$auctionid=I("auctionid");
		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		}
		$configpub=getConfigPub();
		$User=M("users");
		$Auction=M("auction");
		$info=$Auction->where("id={$auctionid}")->find();
		
		$userinfo=$User->field("contacts_name,contacts_mobile")->where("id={$uid}")->find();
		
		
		$this->assign('uid',$uid);
		$this->assign('token',$token);
		$this->assign('auctionid',$auctionid);
		$this->assign('userinfo',$userinfo);
		$this->assign('info',$info);
		$this->assign('configpub',$configpub);

		$this->display();
	}
	
	/* 缴纳保证金 */
	function setBond(){
		$auctionid=I("auctionid");
		$uid=I("uid");
		$token=I("token");
		$contacts_name=I("contacts_name");
		$contacts_mobile=I("contacts_mobile");
		$rs=array('code'=>0,'msg'=>'缴纳成功','info'=>array());
		
		if($contacts_name==''){
			$rs['code']=1001;
			$rs['msg']='请填写联系人';
			echo json_encode($rs);
			exit;
		}
		
		if($contacts_mobile==''){
			$rs['code']=1001;
			$rs['msg']='请填写联系人电话';
			echo json_encode($rs);
			exit;
		}
		
		if(!preg_match("/^1[3|4|5|7|8]\d{9}$/",$contacts_mobile)){
			$rs['code']=1001;
			$rs['msg']='请填写正确手机号码';
			echo json_encode($rs);
			exit;
		}
		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$rs['code']=700;
			$rs['msg']='您的登陆状态失效，请重新登陆！';
			echo json_encode($rs);
			exit;
		}
		
		$Auction=M('auction');
		$auctioninfo=$Auction->where("id={$auctionid}")->find();

		if(!$auctioninfo){
			$rs['code']=1001;
			$rs['msg']='竞拍信息不存在';
			echo json_encode($rs);
			exit;
		}
		
		if($auctioninfo['status']==1){
			$rs['code']=1002;
			$rs['msg']='竞拍已结束';
			echo json_encode($rs);
			exit;
		}
		$Coinrecord=M("users_coinrecord");
		$isexist=$Coinrecord->field("id")->where("action='price_bond' and uid={$uid} and giftid={$auctioninfo['id']} ")->find();

		if($isexist){
			$rs['code']=1003;
			$rs['msg']='已缴纳保证金';
			echo json_encode($rs);
			exit;
		}
		
		$Users=M("users");
		
		$userinfo=$Users->field('coin')->where("id={$uid}")->find();
					
		$total= $auctioninfo['price_bond'];
		 
		$addtime=time();
		$type='expend';
		$action='price_bond';
		
		if($userinfo['coin'] < $total){
			/* 余额不足 */
			$rs['code']=1004;
			$rs['msg']='余额不足';
			echo json_encode($rs);
			exit;
		}		

		/* 更新用户余额 消费 */
		$Users->execute("update __USERS__ set coin=coin - {$total},contacts_name='{$contacts_name}',contacts_mobile='{$contacts_mobile}' where id={$uid}");

				
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$uid,"giftid"=>$auctioninfo['id'],"giftcount"=>1,"totalcoin"=>$total,"showid"=>0,"addtime"=>$addtime );
		$Coinrecord->add($insert);
		
					
		echo json_encode($rs);
		exit;		
	}
	
	/* 我的竞拍 */
	function myauction(){
		$uid=checkNull(I("uid"));
		$token=checkNull(I("token"));

		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		}
		$this->assign("uid",$uid);
		$this->assign("token",$token);

		$Auc_record=M("auction_record");
		$Auction=M("auction");
		$list=$Auc_record->where("uid={$uid}")->group("auctionid")->order("addtime desc")->select();
		foreach($list as $k=>$v){
			$auction=$Auction->where("id={$v['auctionid']}")->find();
			if($auction['status']==-2){
				$auction['tip']='竞拍已关闭';
			}else if($auction['status']==0){
				$auction['tip']='竞拍进行中';
			}else if($auction['bid_uid']!=$uid){
				$auction['tip']='竞拍失败';
				$auction['status']=-1;
			}else if($auction['status']==1){
				$auction['tip']='竞拍成功，等待支付';
			}else if($auction['status']==2){
				$auction['tip']='已付款';
			}else if($auction['status']==3){
				$auction['tip']='交易已完成';
			}else{
				$auction['tip']='交易进行中';
			}
			$list[$k]['auction']=$auction;

		}
		$this->assign("list",$list);
		$this->display();
	}
	
	/* 支付竞拍费用 */
	function setBidPrice(){
		
		$auctionid=I("auctionid");
		$uid=I("uid");
		$token=I("token");
		
		$rs=array('code'=>0,'msg'=>'支付成功','info'=>array());
		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$rs['code']=700;
			$rs['msg']='您的登陆状态失效，请重新登陆！';
			echo json_encode($rs);
			exit;
		}
		
		$Auction=M('auction');
		$auctioninfo=$Auction->where("id={$auctionid}")->find();

		if(!$auctioninfo){
			$rs['code']=1001;
			$rs['msg']='竞拍信息不存在';
			echo json_encode($rs);
			exit;
		}
		
		if($auctioninfo['status']==0){
			$rs['code']=1002;
			$rs['msg']='竞拍未结束';
			echo json_encode($rs);
			exit;
		}
		
		if($auctioninfo['bid_uid'] !=$uid){
			$rs['code']=1005;
			$rs['msg']='你未竞拍成功';
			echo json_encode($rs);
			exit;
		}
		
		if($auctioninfo['status']==2){
			$rs['code']=1004;
			$rs['msg']='已支付';
			echo json_encode($rs);
			exit;
		}
		$User=M("users");
		$userinfo=$User->field("coin")->where("id={$uid}")->find();
					
		$total= $auctioninfo['bid_price'];
		 
		$addtime=time();
		$type='expend';
		$action='bid_price';
		$liveuid=$auctioninfo['uid'];
		
		$stream2=explode('_',$auctioninfo['stream']);
		$showid=$stream2[1];
		
		if($userinfo['coin'] < $total){
			/* 余额不足 */
			$rs['code']=1003;
			$rs['msg']='余额不足';
			echo json_encode($rs);
			exit;
		}		

		/* 更新用户余额 消费 */
		M()->execute("update __PREFIX__users set coin=coin-{$total},consumption=consumption+{$total} where id='{$uid}'");
		
		/* 更新主播映票 */
		M()->execute("update __PREFIX__users set votes=votes+{$total},votestotal=votestotal+{$total} where id='{$liveuid}'");
				
		$insert=array("type"=>$type,"action"=>$action,"uid"=>$uid,"touid"=>$liveuid,"giftid"=>$auctioninfo['id'],"giftcount"=>1,"totalcoin"=>$total,"showid"=>$showid,"addtime"=>$addtime );
		M("users_coinrecord")->add($insert);
		
		$Auction->where("id={$auctioninfo['id']}")->save( array( 'status' => 2,'bid_paytime' => $addtime ) );
		
		/* 清除缓存 */
		delCache("userinfo_".$uid); 
		delCache("userinfo_".$liveuid); 
		
					
		echo json_encode($rs);
		exit;	
		
	}
	
	/* 管理竞拍 */
	function setauction(){
		$uid=checkNull(I("uid"));
		$token=checkNull(I("token"));
		
		$checkToken=checkToken($uid,$token);
		if($checkToken){
			$this->assign("reason",'您的登陆状态失效，请重新登陆！');
			$this->display(':error');
			exit;
		}
		
		$Auc_record=M("auction_record");
		$Auction=M("auction");
		$User=M("users");
		
		$list=$Auction->where("uid={$uid}")->order("addtime desc")->select();
		foreach($list as $k=>$v){
			if($v['status']==-2){
				$v['tip']='竞拍已关闭';
			}else if($v['status']==0){
				$v['tip']='竞拍进行中';
			}else if($v['status']==1 ){
				$v['tip']='竞拍结束，等待用户支付';
			}else if($v['status']==2){
				$v['tip']='用户已支付';
			}else if($v['status']==3){
				$v['tip']='交易已完成';
			}else{
				$v['tip']='交易进行中';
			}
			$list[$k]=$v;
			if($v['status']>0){
				$userinfo=$User->field("contacts_name,contacts_mobile")->where("id={$v['bid_uid']}")->find();
				$list[$k]['userinfo']=$userinfo;
			}
			
		}
		$this->assign("list",$list);
		
		$this->display();
	}
}