<?php

class Model_Login extends Model_Common {
	
	protected $fields='id,user_nicename,avatar,avatar_thumb,sex,signature,coin,consumption,votestotal,user_status,login_type,province,city,birthday,last_login_time,code';

	/* 会员登录 */   	
    public function userLogin($user_login,$user_pass) {

		$user_pass=$this->setPass($user_pass);
		
		$info=DI()->notorm->users
				->select($this->fields.',user_pass')
				->where('user_login=? and user_type="2"',$user_login) 
				->fetchOne();
		if(!$info || $info['user_pass'] != $user_pass){
			return 1001;
		}
		unset($info['user_pass']);
		if($info['user_status']=='0'){
			return 1002;					
		}
		unset($info['user_status']);
		
		$info['isreg']='0';
        
		if($info['last_login_time']=='' || $info['code']==''){
			$info['isreg']='1';
		}
		
        $configpri=$this->getConfigPri();
        if($configpri['agent_switch']==0){
            $info['isreg']='0';
        }
		
		$info['level']=$this->getLevel($info['consumption']);
		$info['level_anchor']=$this->getLevelAnchor($info['votestotal']);

		$token=md5(md5($info['id'].$user_login.time()));
		
		$info['token']=$token;
		$info['avatar']=$this->get_upload_path($info['avatar']);
		$info['avatar_thumb']=$this->get_upload_path($info['avatar_thumb']);
		
		$this->updateToken($info['id'],$token);
		
		$cache=array("token_".$info['id'],"userinfo_".$info['id']);
		$this->delcache($cache);
        return $info;
    }	
	
	/* 会员注册 */
    public function userReg($user_login,$user_pass) {

		$user_pass=$this->setPass($user_pass);
		
		$code=$this->createCode();
		
		$configpri=$this->getConfigPri();
		
		$data=array(
			'user_login' => $user_login,
			'mobile' =>$user_login,
			'user_nicename' =>'手机用户'.substr($user_login,-4),
			'user_pass' =>$user_pass,
			'signature' =>'这家伙很懒，什么都没留下',
			'avatar' =>'/default.jpg',
			'avatar_thumb' =>'/default_thumb.jpg',
			'last_login_ip' =>$_SERVER['REMOTE_ADDR'],
			'create_time' => date("Y-m-d H:i:s"),
			'user_status' => 1,
			"user_type"=>2,//会员
			"code"=>$code,
			"coin"=>$configpri['reg_reward'],
		);

		$isexist=DI()->notorm->users
				->select('id')
				->where('user_login=? and user_type="2"',$user_login) 
				->fetchOne();
		if($isexist){
			return 1006;
		}

		$rs=DI()->notorm->users->insert($data);	
		if(!$rs){
			return 1007;
		}		
		
		// $info['id']=$rs['id'];
		// $info['user_nicename']=$data['user_nicename'];
		// $info['avatar']=$this->get_upload_path($data['avatar']);
		// $info['avatar_thumb']=$this->get_upload_path($data['avatar_thumb']);
		// $info['sex']='2';
		// $info['signature']=$data['signature'];
		// $info['coin']='0';
		// $info['login_type']='phone';
		// $info['level']='1';
		// $info['level_anchor']='1';
		// $info['province']='';
		// $info['city']='';
		// $info['birthday']='';

		
		// $token=md5(md5($info['id'].$user_login.time()));
		
		// $info['token']=$token;
		
		// $this->updateToken($info['id'],$token);
		
		// $cache=array("token_".$info['id'],"userinfo_".$info['id']);
		// $this->delcache($cache);
		return 1;
    }	

	/* 找回密码 */
	public function userFindPass($user_login,$user_pass){
		$isexist=DI()->notorm->users
				->select('id')
				->where('user_login=? and user_type="2"',$user_login) 
				->fetchOne();
		if(!$isexist){
			return 1006;
		}		
		$user_pass=$this->setPass($user_pass);

		return DI()->notorm->users
				->where('id=?',$isexist['id']) 
				->update(array('user_pass'=>$user_pass));
		
	}	
		
	/* 第三方会员登录 */
    public function userLoginByThird($openid,$type,$nickname,$avatar) {			
        $info=DI()->notorm->users
            ->select($this->fields)
            ->where('openid=? and login_type=? and user_type="2"',$openid,$type)
            ->fetchOne();
		$configpri=$this->getConfigPri();
		if(!$info){
			/* 注册 */
			$user_pass='yunbaokeji';
			$user_pass=$this->setPass($user_pass);
			$user_login=$type.'_'.time().rand(100,999);

			if(!$nickname){
				$nickname=$type.'用户-'.substr($openid,-4);
			}else{
				$nickname=urldecode($nickname);
			}
			if(!$avatar){
				$avatar='/default.jpg';
				$avatar_thumb='/default_thumb.jpg';
			}else{
				$avatar=urldecode($avatar);
				$avatar_a=explode('/',$avatar);
				$avatar_a_n=count($avatar_a);
				if($type=='qq'){
					$avatar_a[$avatar_a_n-1]='100';
					$avatar_thumb=implode('/',$avatar_a);
				}else if($type=='wx'){
					$avatar_a[$avatar_a_n-1]='64';
					$avatar_thumb=implode('/',$avatar_a);
				}else{
					$avatar_thumb=$avatar;
				}
				
			}

			$code=$this->createCode();
			
			$data=array(
				'user_login' => $user_login,
				'user_nicename' =>$nickname,
				'user_pass' =>$user_pass,
				'signature' =>'这家伙很懒，什么都没留下',
				'avatar' =>$avatar,
				'avatar_thumb' =>$avatar_thumb,
				'last_login_ip' =>$_SERVER['REMOTE_ADDR'],
				'create_time' => date("Y-m-d H:i:s"),
				'user_status' => 1,
				'openid' => $openid,
				'login_type' => $type, 
				"user_type"=>2,//会员
				"code"=>$code,
				"coin"=>$configpri['reg_reward'],
			);
			
			$rs=DI()->notorm->users->insert($data);		
		
			$info['id']=$rs['id'];
			$info['user_nicename']=$data['user_nicename'];
			$info['avatar']=$this->get_upload_path($data['avatar']);
			$info['avatar_thumb']=$this->get_upload_path($data['avatar_thumb']);
			$info['sex']='2';
			$info['signature']=$data['signature'];
			$info['coin']='0';
			$info['login_type']=$data['login_type'];
			$info['province']='';
			$info['city']='';
			$info['birthday']='';
			$info['consumption']='0';
			$info['user_status']=1;
			$info['last_login_time']='';
		}else{
			if(!$avatar){
				$avatar='/default.jpg';
				$avatar_thumb='/default_thumb.jpg';
			}else{
				$avatar=urldecode($avatar);
				$avatar_a=explode('/',$avatar);
				$avatar_a_n=count($avatar_a);
				if($type=='qq'){
					$avatar_a[$avatar_a_n-1]='100';
					$avatar_thumb=implode('/',$avatar_a);
				}else if($type=='wx'){
					$avatar_a[$avatar_a_n-1]='64';
					$avatar_thumb=implode('/',$avatar_a);
				}else{
					$avatar_thumb=$avatar;
				}
				
			}
			
			$info['avatar']=$avatar;
			$info['avatar_thumb']=$avatar_thumb;
			
			$data=array(
				'avatar' =>$avatar,
				'avatar_thumb' =>$avatar_thumb,
			);
			
		}
		
		if($info['user_status']=='0'){
			return 1001;					
		}
		unset($info['user_status']);
		
		$info['isreg']='0';
		if($info['last_login_time']=='' || $info['code']==''){
			$info['isreg']='1';
		}

        if($configpri['agent_switch']==0){
            $info['isreg']='0';
        }
		unset($info['last_login_time']);
		
		$info['level']=$this->getLevel($info['consumption']);

		$info['level_anchor']=$this->getLevelAnchor($info['votestotal']);

		$token=md5(md5($info['id'].$openid.time()));
		
		$info['token']=$token;
		$info['avatar']=$this->get_upload_path($info['avatar']);
		$info['avatar_thumb']=$this->get_upload_path($info['avatar_thumb']);
		
		$this->updateToken($info['id'],$token);
		
		$cache=array("token_".$info['id'],"userinfo_".$info['id']);
		$this->delcache($cache);
        return $info;
    }		
	
	/* 更新token 登陆信息 */
    public function updateToken($uid,$token,$data=array()) {
		$expiretime=time()+60*60*24*300;

		DI()->notorm->users
			->where('id=?',$uid)
			->update(array("token"=>$token, "expiretime"=>$expiretime ,'last_login_time' => date("Y-m-d H:i:s"), "last_login_ip"=>$_SERVER['REMOTE_ADDR'] ));

		
        
		return 1;
    }	
	
	/* 生成邀请码 */
	/*public function createCode(){
		$code = 'ABCDEFGHIJKLMNPQRSTUVWXYZ';
		$rand = $code[rand(0,25)]
			.strtoupper(dechex(date('m')))
			.date('d').substr(time(),-5)
			.substr(microtime(),2,5)
			.sprintf('%02d',rand(0,99));
		for(
			$a = md5( $rand, true ),
			$s = '123456789ABCDEFGHIJKLMNPQRSTUV',
			$d = '',
			$f = 0;
			$f < 6;
			$g = ord( $a[ $f ] ),
			$d .= $s[ ( $g ^ ord( $a[ $f + 6 ] ) ) - $g & 0x1F ],
			$f++
		);
		if(mb_strlen($d)==6){
			$oneinfo=DI()->notorm->users
					->select("id")
					->where('code=?',$d)
					->fetchOne();
			if(!$oneinfo){
				return $d;
			}
		}
		$d=$this->createCode();
		return $d;
	}*/


	public function createCode($len=6,$format='ALL2'){
        $is_abc = $is_numer = 0;
        $password = $tmp =''; 
        switch($format){
            case 'ALL':
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                break;
            case 'ALL2':
                $chars='ABCDEFGHJKLMNPQRSTUVWXYZ0123456789';
                break;
            case 'CHAR':
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
                break;
            case 'NUMBER':
                $chars='0123456789';
                break;
            default :
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                break;
        }
        
        while(strlen($password)<$len){
            $tmp =substr($chars,(mt_rand()%strlen($chars)),1);
            if(($is_numer <> 1 && is_numeric($tmp) && $tmp > 0 )|| $format == 'CHAR'){
                $is_numer = 1;
            }
            if(($is_abc <> 1 && preg_match('/[a-zA-Z]/',$tmp)) || $format == 'NUMBER'){
                $is_abc = 1;
            }
            $password.= $tmp;
        }
        if($is_numer <> 1 || $is_abc <> 1 || empty($password) ){
            $password = createCode($len,$format);
        }
        if($password!=''){
            
            $oneinfo=DI()->notorm->users
	            ->select("id")
	            ->where("code=?",$password)
	            ->fetchOne();
	        
            if(!$oneinfo){
                return $password;
            }            
        }
        $password = createCode($len,$format);
        return $password;
    }

}
