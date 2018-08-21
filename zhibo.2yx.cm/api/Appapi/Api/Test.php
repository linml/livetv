<?php

class Api_Test extends Api_Common {

	public function getRules() {
		return array(
			'jssend' => array(
				'uid' => array('name' => 'uid', 'type' => 'int', 'min' => 1, 'require' => true, 'desc' => '会员ID'),
				'videoid' => array('name' => 'videoid', 'type' => 'int', 'min' => 1, 'require' => true, 'desc' => '会员ID'),
				'type' => array('name' => 'type', 'type' => 'int', 'desc' => '会员ID'),
                'token' => array('name' => 'token', 'desc' => '会员token'),
            ),
			'test' => array(
				 
            ),
		);
	}
	
	/* 获取订单号 */
	public function upload(){

		file_put_contents('./load.txt',date('y-m-d h:i:s').'提交参数信息 src:'.json_encode($_FILES['file'])."\r\n",FILE_APPEND);
		require(API_ROOT.'/public/txcloud/include.php');

		//bucketname
		$bucket = 'aosika';
		//uploadlocalpath
		/* $src = $_FILES['file'];//'./hello.txt'; */
		$src = $_FILES["file"]["tmp_name"];//'./hello.txt';
		
		//cospath
		$dst = '/test1/'.$_FILES["file"]["name"];
	
		//cosfolderpath
		$folder = '/test1';
		//config your information
		$config = array(
			'app_id' => '1255500835',
			'secret_id' => 'AKIDbBcrfKT7EE3gBUQqjPxKWWJvPxPk3thI',
			'secret_key' => 'XvCLJ7j8NSN6f7QcfXZR7g2C9tRCm5pQ',
			'region' => 'sh',   // bucket所属地域：华北 'tj' 华东 'sh' 华南 'gz'
			'timeout' => 60
		);
		
		

		date_default_timezone_set('PRC');
		
		$cosApi = new 	\QCloud\Cos\Api($config);

		// Create folder in bucket.
/* 		$ret = $cosApi->createFolder($bucket, $folder);
		var_dump($ret); */

		// Upload file into bucket.
		$ret = $cosApi->upload($bucket, $src, $dst);
		
		var_dump($ret);
	
		$auth = new \QCloud\Cos\Auth($config['app_id'], $config['secret_id'], $config['secret_key']);
		$signature = $auth->createNonreusableSignature($bucket, $dst);
		var_dump($signature);
		exit;
		
	}

	public function jssend(){
		$rs = array('code' => 0, 'msg' => '', 'info' => array());
		
		$domain = new Domain_Test();
		$info=$domain->jssend($this->uid,43,0);
		
		$rs['info']=$info;
		return $rs;
	}

	public function test(){
		$rs = array('code' => 0, 'msg' => '出来啦', 'info' => array());
		
	
		file_put_contents('./ztuiliu.txt',date('y-m-d h:i:s').'提交参数信息 :'.$reqParam."\r\n",FILE_APPEND);
		
		$rs['info']=$info;
		return $rs;
	}
}
