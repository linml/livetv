<?php

/**
 * 推送管理
 */
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class PushController extends AdminbaseController {

    function index(){
       if($_REQUEST['start_time']!=''){
            $map['addtime']=array("gt",strtotime($_REQUEST['start_time']));
            $_GET['start_time']=$_REQUEST['start_time'];
         }
         
         if($_REQUEST['end_time']!=''){
            $map['addtime']=array("lt",strtotime($_REQUEST['end_time']));
            $_GET['end_time']=$_REQUEST['end_time'];
         }
         
         if($_REQUEST['start_time']!='' && $_REQUEST['end_time']!='' ){
            $map['addtime']=array("between",array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])));
            $_GET['start_time']=$_REQUEST['start_time'];
            $_GET['end_time']=$_REQUEST['end_time'];
         }

        if($_REQUEST['keyword']!=''){
            $map['touid|adminid']=array("like","%".$_REQUEST['keyword']."%"); 
            $_GET['keyword']=$_REQUEST['keyword'];
        }		
			
    	$Pushrecord=M("pushrecord");
    	$count=$Pushrecord->where($map)->count();
    	$page = $this->page($count, 20);
    	$lists = $Pushrecord
            ->where($map)
            ->order("addtime DESC")
            ->limit($page->firstRow . ',' . $page->listRows)
            ->select();

    	$this->assign('msg_type', $this->msg_type);
    	$this->assign('lists', $lists);
    	$this->assign('formget', $_GET);
    	$this->assign("page", $page->show('Admin'));
    	
    	$this->display();
    }
		
    function del(){
        $id=intval($_GET['id']);
        if($id){
            $result=M("pushrecord")->delete($id);				
                if($result){
                    $action="删除推送信息：{$id}";
                    setAdminLog($action);
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
            $content=str_replace("\r","", $_POST['content']);
            $content=str_replace("\n","", $content);
            
            $touid=str_replace("\r","", $_POST['touid']);
            $touid=str_replace("\n","", $touid);
            

			$Pushrecord=M("pushrecord");
			$Pushrecord->create();
            $Pushrecord->touid=$touid;
            $Pushrecord->content=$content;
            if($content==''){
                $this->error('推送内容不能为空');
            }
            
            $configpri=getConfigPri();
            
            /* 极光推送 */
            $app_key = $configpri['jpush_key'];
            $master_secret = $configpri['jpush_secret'];
            
            if(!$app_key || !$master_secret){
                $this->error('请先设置推送配置'); 
            }
            $issuccess=0;
            $error='推送失败';
            if($app_key && $master_secret ){
                 
                require SITE_PATH.'api/public/JPush/autoload.php';

                // 初始化
                $client = new \JPush\Client($app_key, $master_secret,null);
				//file_put_contents('./jpush.txt',date('y-m-d h:i:s').'提交参数信息 设备名client2:'.json_encode($client)."\r\n",FILE_APPEND);
				//file_put_contents('./jpush.txt',date('y-m-d h:i:s').'提交参数信息 设备名client:'.$client."\r\n",FILE_APPEND);
                $anthorinfo=array();
                // $anthorinfo=array(
                    // "uid"=>$dataroom['uid'],
                    // "avatar"=>$dataroom['avatar'],
                    // "avatar_thumb"=>$dataroom['avatar_thumb'],
                    // "user_nicename"=>$dataroom['user_nicename'],
                    // "title"=>$dataroom['title'],
                    // "city"=>$dataroom['city'],
                    // "stream"=>$dataroom['stream'],
                    // "pull"=>$dataroom['pull'],
                    // "thumb"=>$dataroom['thumb'],
                // );
                if($touid==''){
                    $uidall=M("users")->field("id")->where("user_type='2'")->select();
                    $uids=array_column2($uidall,'id');
                }else{
                    $uids=preg_split('/,|，/',$touid);
                    
                }

                $nums=count($uids);	
                $apns_production=false;
                if($configpri['jpush_sandbox']){
                    $apns_production=true;
                }
                $title=$content;
                for($i=0;$i<$nums;){
                    $alias=array();
                    for($n=0;$n<1000;$n++,$i++){
                        if($uids[$i]){
                            $alias[]=$uids[$i].'PUSH';								 
                        }else{
                            break;
                        }
                    }	 
                    try{	
                        $result = $client->push()
                                ->setPlatform('all')
                                ->addAlias($alias)
                                ->setNotificationAlert($title)
                                ->iosNotification($title, array(
                                    'sound' => 'sound.caf',
                                    'category' => 'jiguang',
                                    'extras' => array(
                                        'userinfo' => $anthorinfo
                                    ),
                                ))
                                ->androidNotification($title, array(
                                    'extras' => array(
                                        'userinfo' => $anthorinfo
                                    ),
                                ))
                                ->options(array(
                                    'sendno' => 100,
                                    'time_to_live' => 0,
                                    'apns_production' =>  $apns_production,
                                ))
                                ->send();
                        if($result['code']==0){
                            $issuccess=1;
                        }else{
                            $error=$result['msg'];
                        }
                    } catch (Exception $e) {   
                        file_put_contents('./jpush.txt',date('y-m-d h:i:s').'提交参数信息 设备名:'.json_encode($alias)."\r\n",FILE_APPEND);
                        file_put_contents('./jpush.txt',date('y-m-d h:i:s').'提交参数信息:'.$e."\r\n",FILE_APPEND);
                    }					
                }			
            }
            /* 极光推送 */
            if($issuccess==0){
                $this->error($error);
            }
			$Pushrecord->adminid=$_SESSION['ADMIN_ID'];
			$Pushrecord->admin=$_SESSION['name'];
			$Pushrecord->addtime=time();
			$Pushrecord->ip=ip2long($_SERVER['REMOTE_ADDR']);
			$result=$Pushrecord->add(); 
			if($result!==false){
                $action="推送信息：{$title}";
                    setAdminLog($action);
				$this->success('推送成功');
			}else{
				$this->error('推送失败');
			}
		}			
	}		
    
    function export()
    {
        if($_REQUEST['start_time']!=''){
            $map['addtime']=array("gt",strtotime($_REQUEST['start_time']));
        }			 
        if($_REQUEST['end_time']!=''){	 
            $map['addtime']=array("lt",strtotime($_REQUEST['end_time']));
        }
        if($_REQUEST['start_time']!='' && $_REQUEST['end_time']!='' ){	 
            $map['addtime']=array("between",array(strtotime($_REQUEST['start_time']),strtotime($_REQUEST['end_time'])));
        }
        if($_REQUEST['keyword']!=''){
            $map['touid|adminid']=array("like","%".$_REQUEST['keyword']."%"); 
        }
        $xlsName  = "Excel";
        $Pushrecord=M("pushrecord");
        $xlsData=$Pushrecord->where($map)->order("addtime DESC")->select();
        foreach ($xlsData as $k => $v)
        {
            if(!$v['touid']){
                $xlsData[$k]['touid']='所有会员';
                $xlsData[$k]['addtime']=date("Y-m-d H:i:s",$v['addtime']);
            }          
        }
        
        $action="导出推送信息：".M("pushrecord")->getLastSql();
                    setAdminLog($action);
        $cellName = array('A','B','C','D','E','F');
        $xlsCell  = array(
            array('id','序号'),
            array('admin','管理员'),
            array('ip','IP'),
            array('touid','推送对象'),
            array('content','推送内容'),
            array('addtime','提交时间'),
        );
        exportExcel($xlsName,$xlsCell,$xlsData,$cellName);
    }
    
}
