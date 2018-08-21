

	(function(){
		$(".submit").on("click",function(){
			var contacts_name=$.trim($("#contacts_name").val());
			var contacts_mobile=$.trim($("#contacts_mobile").val());
			var reg_phone=/^1[3|4|5|7|8]\d{9}$/;
			if(contacts_name==''){
				layer.msg("请填写联系人");
				return !1;
			}
			if(contacts_mobile==''){
				layer.msg("请填写联系人电话");
				return !1;
			}
			if(reg_phone.test(contacts_mobile)==false){
				layer.msg("请填写正确手机号码");
				return !1;
			}
			$.ajax({
				url:'/index.php?g=Appapi&m=Auction&a=setBond',
				type:'POST',
				data:{
					uid:uid,
					token:token,
					auctionid:auctionid,
					contacts_name:contacts_name,
					contacts_mobile:contacts_mobile,
				},
				dataType:'json',
				success:function(data){
					if(data.code==0){
						layer.msg(data.msg);
						
					}else{
						layer.msg(data.msg);
					}
					
				},
				error:function(e){
					layer.msg("缴纳失败");
				}
			});
			
		})

		
	})()