<?php if (!defined('THINK_PATH')) exit();?><!doctype html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>大鱼TV <?php echo L('ADMIN_CENTER');?></title>
		<meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge" />
		<meta name="renderer" content="webkit|ie-comp|ie-stand">
		<meta name="robots" content="noindex,nofollow">
		<link href="/admin/themes/simplebootx/Public/assets/css/admin_login.css" rel="stylesheet" />
		<style>
			#login_btn_wraper{
				text-align: center;
			}
			#login_btn_wraper .tips_success{
				color:#fff;
			}
			#login_btn_wraper .tips_error{
				color:#DFC05D;
			}
			#login_btn_wraper button:focus{outline:none;}
		</style>
		<style>
			.footer{
				width: 100%;
				height: 20%;
				<!-- position: absolute; -->
				bottom: 20px;
				border-top: 3px solid #fff;
				min-width: 1200px;
                margin-top:100px;
			}

			.footer-box{
				width: 80%;
				margin: 0 auto;
				display: flex;
			}

			.footer-left{
				height: 100%;
				line-height: 200px;
				width: 510px;
			}

			.footer-img{
				background-image: url("/public/images/login_icon.png");
				background-size: 100% 100%;
				width: 120px;
				height: 120px;
				float: left;
				margin-top: 30px;
				margin-right: 10px;
			}

			.footer-content{
				font-size: 24px;
				color: #fff;
			}

			.footer-center{
				height: 100%;
				line-height: 200px;
				width: 290px;
				font-size: 24px;
				color: #fff;
			}

			.footer-right{
				height: 100%;
				line-height: 200px;
				/* width: 510px; */
				font-size: 24px;
				color: #fff;
			}

			.footer-empty{
				flex: 1;
			}

		</style>
		<script>
			if (window.parent !== window.self) {
					document.write = '';
					window.parent.location.href = window.self.location.href;
					setTimeout(function () {
							document.body.innerHTML = '';
					}, 0);
			}
		</script>
		
	</head>
<body>
	<div class="wrap">
		<h1><a href="">大鱼TV <?php echo L('ADMIN_CENTER');?></a></h1>
		<form method="post" name="login" action="<?php echo U('public/dologin');?>" autoComplete="off" class="js-ajax-form">
			<div class="login">
				<ul>
					<li>
						<input class="input" id="js-admin-name" required name="username" type="text" placeholder="<?php echo L('USERNAME_OR_EMAIL');?>" title="<?php echo L('USERNAME_OR_EMAIL');?>" value="<?php echo ($_COOKIE['admin_username']); ?>"/>
					</li>
					<li>
						<input class="input" id="admin_pwd" type="password" required name="password" placeholder="<?php echo L('PASSWORD');?>" title="<?php echo L('PASSWORD');?>" />
					</li>
					<li class="verifycode-wrapper">
						<?php echo sp_verifycode_img('length=4&font_size=20&width=248&height=42&use_noise=1&use_curve=0','style="cursor: pointer;" title="点击获取"');?>
					</li>
					<li>
						<input class="input" type="text" name="verify" placeholder="<?php echo L('ENTER_VERIFY_CODE');?>" />
					</li>
				</ul>
				<div id="login_btn_wraper">
					<button type="submit" name="submit" class="btn js-ajax-submit" data-loadingmsg="<?php echo L('LOADING');?>"><?php echo L('LOGIN');?></button>
				</div>
			</div>
		</form>
	</div>

	<div class="footer">
		<div class="footer-box">
			<div class="footer-left">
				<div class="footer-img"></div>
				<div class="footer-content">开发：泰安云豹网络科技有限公司</div>
			</div>
			<div class="footer-empty"></div>
			<div class="footer-center">官网:www.yunbaokj.com</div>
			<div class="footer-empty"></div>
			<div class="footer-right">咨询电话：0538-8270220</div>

		</div>


	</div>

<script>
var GV = {
	DIMAUB: "",
	JS_ROOT: "/public/js/",//js版本号
	TOKEN : ''	//token ajax全局
};
</script>
<script src="/public/js/wind.js"></script>
<script src="/public/js/jquery.js"></script>
<script type="text/javascript" src="/public/js/common.js"></script>
<script>
;(function(){
	document.getElementById('js-admin-name').focus();
})();
</script>
</body>
</html>