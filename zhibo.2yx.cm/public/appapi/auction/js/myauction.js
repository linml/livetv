

	(function(){
		$(".pay").on("click",function(){
			var bid_price=$(this).data("price");
			var auctionid=$(this).data("auctionid");

			if(!uid || !token || !bid_price || !auctionid){
				layer.msg("信息错误");
				return !1;
			}
			layer.confirm('确定支付？',{}, function(index){
				layer.close(index);
				$.ajax({
					url:'/index.php?g=Appapi&m=Auction&a=setBidPrice',
					type:'POST',
					data:{
						uid:uid,
						token:token,
						auctionid:auctionid,
					},
					dataType:'json',
					success:function(data){
						if(data.code==0){
							layer.msg(data.msg,{},function(){
								location.reload();
							});
						}else if(data.code==1003){
							layer.confirm('余额不足，去支付',{}, function(index){
								layer.close(index);
								location.href='phonelive://pay';
							})
							
						}else{
							layer.msg(data.msg);
						}
						
					},
					error:function(e){
						layer.msg("缴纳失败");
					}
				});

			});

			
		})

		
	})()