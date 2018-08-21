<?php if (!defined('THINK_PATH')) exit();?><div class="dialog-tc">
 
	<div class="mode">
		<a>直播配置</a>
		<span onclick="liveType.closePorp()" class="rotate"></span>
	</div>
	<div class="mount_title">直播标题</div>
	<div class="mount_num" style="display:block"> 
		<input id="title"  placeholder="请填写标题"  type="text" class="minput-text">
	</div>
	<div class="mount_title">直播类型</div>
	<div class="mount-method" id="mount_method" data-type="0">
		<?php if(is_array($config["live_type"])): $i = 0; $__LIST__ = $config["live_type"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><a class="<?php if($i == 1): ?>mount_btn_on<?php else: ?>mount_btn_no<?php endif; ?>" id="btn<?php echo ($vo['0']); ?>"><?php echo ($vo['1']); ?></a><?php endforeach; endif; else: echo "" ;endif; ?>
	</div>
	<div class="mount_num" id="mount_num"> 
		<input id="gift_number"  placeholder="" onkeyup="this.value=this.value.replace(/\D/g,'')" onafterpaste="this.value=this.value.replace(/\D/g,'')"  type="text" class="minput-text">
	</div>
	<div class="mount_num" id="mount_select"> 
		<select id="gift_select">
			<?php if(is_array($config["live_time_coin"])): $i = 0; $__LIST__ = $config["live_time_coin"];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?><option value="<?php echo ($vo); ?>"><?php echo ($vo); echo ($config['name_coin']); ?>/分钟</option><?php endforeach; endif; else: echo "" ;endif; ?>
		</select>
	</div>
	<div class="deal-footer">
		<a class="mount_con" onclick="liveType.other()">确定</a>
		<a class="mount_cancel" id="mount_cancel">取消</a>
	</div>
</div>

<script type="text/javascript">
	$("#mount_method a").click(function(){
			$(this).attr("class","mount_btn_on");
			$(this).siblings().attr("class","mount_btn_no");
			var id=$(this).attr("id");
			if(id=="btn1" || id=="btn2")
			{
				$("#mount_num input").val("");
				if(id=="btn1")
				{
					$("#mount_num input").attr("placeholder","请设置密码");
				}
				else
				{
					$("#mount_num input").attr("placeholder","请设置金额");
				}
				$("#mount_select").css('display','none');
				$("#mount_num").css('display','block');
				
			}
			else if(id=="btn3"){
				$("#mount_num").css('display','none');
				$("#mount_select").css('display','block');
			}
			else
			{
				$("#mount_num").css('display','none');
				$("#mount_select").css('display','none');
			}
	});
	$("#mount_cancel").click(function(){
		liveType.closePorp()
	});
</script>