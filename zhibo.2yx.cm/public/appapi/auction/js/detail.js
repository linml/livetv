  function lxfEndtime(){
		var tip=$(".surplus-time");
		//var lxfday=$(this).attr("lxfday");//用来判断是否显示天数的变量
		//var endtime = new Date($(this).attr("endtime")).getTime();//取结束日期(毫秒值)
		//var nowtime = new Date().getTime();	//今天的日期(毫秒值)
		//var youtime = endtime-nowtime;//还有多久(毫秒值)
		var youtime = surplus;//还有多久(毫秒值)
		//var seconds = youtime/1000; 
		var seconds = youtime;
		var minutes = Math.floor(seconds/60);
		var hours = Math.floor(minutes/60);
		var days = Math.floor(hours/24);
		var CDay= days ;
		var CHour= hours % 24;
		var CMinute= minutes % 60;
		var CSecond= Math.floor(seconds%60);//"%"是取余运算，可以理解为60进一后取余数，然后只要余数。
		if(youtime<1){
			tip.html('竞拍已结束');
			return !1;
		}else{
			if(CHour<10){
				CHour='0'+CHour;
			}
			if(CMinute<10){
				CMinute='0'+CMinute;
			}
			if(CSecond<10){
				CSecond='0'+CSecond;
			}

			tip.html("<span>"+CHour+"</span>:<span>"+CMinute+"</span>:<span>"+CSecond+"</span>");	  //输出没有天数的数据
			surplus--;
		}

		setTimeout("lxfEndtime()",1000);
  };


	(function(){
		if(info.status==0 && info.surplus > 0){
			lxfEndtime();
		}
		
		/* $(".setbond").on("click",function(){
			$.ajax({
				url:'/index.php?g=Appapi&m=Auction&a=setBond',
				type:'POST',
				data:{
					uid:uid,
					token:token,
					auctionid:info.id,
				},
				dataType:'json',
				success:function(data){
					if(data.code==0){
						layer.msg(data.msg);
						$(".setbond").hide();
					}else{
						layer.msg(data.msg);
					}
					
				},
				error:function(e){
					layer.msg("缴纳失败");
				}
			});
			
		}) */

		
	})()