<?php

/**
 * 礼物
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class LevellimitController extends AdminbaseController {

		
    function index(){
	
    	$levellimit=M("experlevel_limit");
    	$count=$levellimit->count();
    	$page = $this->page($count, 20);
    	$lists = $levellimit
    	->order("orderno asc")
    	->limit($page->firstRow . ',' . $page->listRows)
    	->select();
    	$this->assign('lists', $lists);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display();
    }		
		
		function del(){
			 	$id=intval($_GET['id']);
					if($id){
						$result=M("experlevel_limit")->delete($id);				
							if($result){
                                $action="删除等级提现：{$id}";
                    setAdminLog($action);
									$this->success('删除成功');
							 }else{
									$this->error('删除失败');
							 }						
					}else{				
						$this->error('数据传入失败！');
					}								  
					$this->display();				
		}		
    //排序
    public function listorders() { 
		
        $ids = $_POST['listorders'];
        foreach ($ids as $key => $r) {
            $data['orderno'] = $r;
            M("experlevel_limit")->where(array('id' => $key))->save($data);
        }
				
        $status = true;
        if ($status) {
            $action="更新等级提现排序";
                    setAdminLog($action);
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }				
    function add(){
		    	
    	$this->display();
    }		
		function do_add(){

				if(IS_POST){	

				
					 $levellimit=M("experlevel_limit");
					 $levellimit->create();
					 $levellimit->addtime=time();
					 
					 $result=$levellimit->add(); 
					 if($result){
                         $action="添加等级提现：{$result}";
                    setAdminLog($action);
						  $this->success('添加成功');
					 }else{
						  $this->error('添加失败');
					 }
				}				
    }		
    function edit(){

			 	$id=intval($_GET['id']);
					if($id){
						$levellimit	=M("experlevel_limit")->find($id);
						$this->assign('levellimit', $levellimit);						
					}else{				
						$this->error('数据传入失败！');
					}								      	
    	$this->display();
    }			
		function do_edit(){
				if(IS_POST){			
					 $levellimit=M("experlevel_limit");
					 $levellimit->create();
					 $result=$levellimit->save(); 
					 if($result){
                         $action="编辑等级提现：{$_POST['id']}";
                    setAdminLog($action);
						  $this->success('修改成功');
					 }else{
						  $this->error('修改失败');
					 }
				}	
    }				
}
