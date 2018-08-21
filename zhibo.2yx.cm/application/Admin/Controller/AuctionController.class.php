<?php

/**
 * 虚拟竞拍
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class AuctionController extends AdminbaseController {
    function index(){

		$_GET['status']=-3;
		if($_REQUEST['status']!='' && $_REQUEST['status']!=-3){
			 $map['status']=$_REQUEST['status']; 
			 $_GET['status']=(int)$_REQUEST['status'];
		 }	
		 
		 if($_REQUEST['uid']!=''){
			 $map['uid']=$_REQUEST['uid']; 
			 $_GET['uid']=$_REQUEST['uid'];
		 }	
		 if($_REQUEST['bid_uid']!=''){
			 $map['bid_uid']=$_REQUEST['bid_uid']; 
			 $_GET['bid_uid']=$_REQUEST['bid_uid'];
		 }	
    	$auction=M("auction");
    	$count=$auction->where($map)->count();
    	$page = $this->page($count, 20);
    	$lists = $auction
			->where($map)
			->order("addtime DESC")
			->limit($page->firstRow . ',' . $page->listRows)
			->select();
		foreach($lists as $k=>$v){
			$lists[$k]['userinfo']=getUserInfo($v['uid']);
			if($v['bid_uid']){
				$lists[$k]['biduserinfo']=getUserInfo($v['bid_uid']);
			}
		}
    	$this->assign('lists', $lists);
    	$this->assign("page", $page->show('Admin'));
    	$this->assign('formget', $_GET);

    	$this->display();
    }
		
    function index2(){
		$id=I("id");
		$map['auctionid']=$id;
		if($_REQUEST['uid']!=''){
			 $map['uid']=$_REQUEST['uid']; 
			 $_GET['uid']=$_REQUEST['uid'];
		 }	
    	$auction=M("auction_record");
    	$count=$auction->where($map)->count();
    	$page = $this->page($count, 20);
    	$lists = $auction
			->where($map)
			->order("addtime DESC")
			->limit($page->firstRow . ',' . $page->listRows)
			->select();
		foreach($lists as $k=>$v){
			$lists[$k]['userinfo']=getUserInfo($v['uid']);
		}
    	$this->assign('lists', $lists);
		$this->assign('formget', $_GET);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display();
    }
	
	
		function del(){
			$id=intval($_GET['id']);
			if($id){
				$result=M("auction")->delete($id);				
				if($result){
					$this->success('删除成功');
				}else{
					$this->error('删除失败');
				}			
			}else{				
				$this->error('数据传入失败！');
			}								  			
		}		

		function add(){
			$this->display();				
		}	
		function add_post(){
			if(IS_POST){			
				 $auction=M("auction");
				 $auction->create();
				 $auction->addtime=time();
				 $result=$auction->add(); 
				 if($result){
					  $this->success('添加成功');
				 }else{
					  $this->error('添加失败');
				 }
			}			
		}		
		function edit(){
			$id=intval($_GET['id']);
			if($id){
				$auction=M("auction")->where("id={$id}")->find();
				$this->assign('auction', $auction);						
			}else{				
				$this->error('数据传入失败！');
			}								  
			$this->display();				
		}
		
		function edit_post(){
			if(IS_POST){			
				 $auction=M("auction");
				 $auction->create();
				 $result=$auction->save(); 
				 if($result!==false){
					  $this->success('修改成功');
				 }else{
					  $this->error('修改失败');
				 }
			}			
		}
		
}
