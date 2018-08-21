    function file_click(e){
			var n= e.attr("data-index");
			upload(n);
    }
    function upload(index) {
			$('#upload').empty();
			var input = '<input type="file" id="ipt-file1" name="image" accept="image/*" />';
			$('#upload').html(input);
			var iptt=document.getElementById(index);
			if(window.addEventListener) { // Mozilla, Netscape, Firefox
					iptt.addEventListener('change',function(){
						ajaxFileUpload(index);
						$(".shadd[data-select="+index+"]").show();
					},false);
			}else{
					iptt.attachEvent('onchange',function(){
						ajaxFileUpload(index);
						$(".shadd[data-select="+index+"]").show();
					});
			}
			$('#'+index).click();
    }
    function ajaxFileUpload(img) {
			$("."+img).css({"width":"0px"});
			$("."+img).animate({"width":"100%"},700,function(){
				var id= img;
				$.ajaxFileUpload
				(
					{
						url: '/index.php?g=Appapi&m=Auction&a=upload',
						secureuri: false,
						fileElementId: id,
						data: { },
						dataType: 'html',
						success: function(data) {
							data=data.replace(/<[^>]+>/g,"");
							var str=JSON.parse(data);
							if(str.ret==200){
								var sub=img.substr(8,1);
								$(".sf"+sub).attr("value",str.data.url);
								//$.alert("上传成功");
								$(".shadd[data-select="+img+"]").hide();
								$(".img-sfz[data-index="+img+"]").attr("src",str.data.url);
							}else{
								$.alert(str.msg);
								$(".shadd[data-select="+img+"]").hide();
							}
						},
						error: function(data) {
							console.log(data);
							layer.msg("上传失败");
							$(".shadd[data-select="+img+"]").hide();
						}
					}
				)
				return true;
			});
    }
	
	(function($) {
		$.init();
		var btns = $('#time');
		btns.each(function(i, btn) {
			btn.addEventListener('tap', function() {
				var _this=this;
				var optionsJson = this.getAttribute('data-options') || '{}';
				var options = JSON.parse(optionsJson);
				var id = this.getAttribute('id');
				/*
				 * 首次显示时实例化组件
				 * 示例为了简洁，将 options 放在了按钮的 dom 上
				 * 也可以直接通过代码声明 optinos 用于实例化 DtPicker
				 */
				var picker = new $.DtPicker(options);
				picker.show(function(rs) {
					/*
					 * rs.value 拼合后的 value
					 * rs.text 拼合后的 text
					 * rs.y 年，可以通过 rs.y.vaue 和 rs.y.text 获取值和文本
					 * rs.m 月，用法同年
					 * rs.d 日，用法同年
					 * rs.h 时，用法同年
					 * rs.i 分（minutes 的第二个字母），用法同年
					 */
					//$('#time').value=rs.text;
					//document.getElementById('time').value=rs.text;
					_this.value=rs.text;
					/* 
					 * 返回 false 可以阻止选择框的关闭
					 * return false;
					 */
					/*
					 * 释放组件资源，释放后将将不能再操作组件
					 * 通常情况下，不需要示放组件，new DtPicker(options) 后，可以一直使用。
					 * 当前示例，因为内容较多，如不进行资原释放，在某些设备上会较慢。
					 * 所以每次用完便立即调用 dispose 进行释放，下次用时再创建新实例。
					 */
					picker.dispose();
				});
			}, false);
		});
		
		$.ready(function() {
			//普通示例
			var userPicker = new $.PopPicker();
			userPicker.setData([{
				value: '0.1',
				text: '0.1'
			}, {
				value: '0.2',
				text: '0.2'
			}, {
				value: '0.3',
				text: '0.3'
			}, {
				value: '0.5',
				text: '0.5'
			}, {
				value: '1.0',
				text: '1.0'
			}, {
				value: '1.5',
				text: '1.5'
			}, {
				value: '2.0',
				text: '2.0'
			}]);
			var showUserPickerButton = document.getElementById('longtime');
			var userResult = document.getElementById('longtime');
			showUserPickerButton.addEventListener('tap', function(event) {
				userPicker.show(function(items) {
					//userResult.value = JSON.stringify(items[0]);
					userResult.value = items[0].value;
					//返回 false 可以阻止选择框的关闭
					//return false;
				});
			}, false);


		});
	})(mui);
	
	(function(){
		$(".submit").on("click",function(){
			var reg_phone=/^1[3|4|5|7|8]\d{9}$/;
			var thumb=$("#thumb").val();
			var title=$("#title").val();
			var time=$("#time").val();
			var addr=$("#addr").val();
			var contacts=$("#contacts").val();
			var contacts_mobile=$("#contacts_mobile").val();
			var price_start=$("#price_start").val();
			var price_bond=$("#price_bond").val();
			var price_fare=$("#price_fare").val();
			var longtime=$("#longtime").val();
			var delayed_time=$("#delayed_time").val();
			var delayed_nums=$("#delayed_nums").val();
			var des=$("#des").val();
			var stream=$("#stream").val();
			
			if(thumb==''){
				layer.msg("请上传图片");
				return !1;
			}
			
			if(title==''){
				layer.msg("请输入拍品名称");
				return !1;
			}
			
			if(time==''){
				layer.msg("请选择约会时间");
				return !1;
			}
			
			if(addr==''){
				layer.msg("请选择地点");
				return !1;
			}
			
			if(contacts==''){
				layer.msg("请填写联系人");
				return !1;
			}
			
			if(contacts_mobile==''){
				layer.msg("请填写联系电话");
				return !1;
			}
			
			if(reg_phone.test(contacts_mobile)==false){
				layer.msg("请填写正确手机号码");
				return !1;
			}
			
			if(price_start==''){
				layer.msg("请填写起拍价");
				return !1;
			}
			if(price_bond==''){
				layer.msg("请填写保证金");
				return !1;
			}
			if(price_fare==''){
				layer.msg("请填写加价幅度");
				return !1;
			}
			if(longtime==''){
				layer.msg("请选择竞拍时间");
				return !1;
			}
			/* if(delayed_time==''){
				layer.msg("请选择延时值");
				return !1;
			} */
			/* if(delayed_nums==''){
				layer.msg("请选择最大延时");
				return !1;
			} */
			
			/* if(des==''){
				layer.msg("请填写描述");
				return !1;
			} */
			
			$.ajax({
				url:'/index.php?g=Appapi&m=Auction&a=add_post',
				type:'POST',
				data:{
					uid:uid,
					token:token,
					title:title,
					thumb:thumb,
					time:time,
					addr:addr,
					contacts:contacts,
					contacts_mobile:contacts_mobile,
					price_start:price_start,
					price_bond:price_bond,
					price_fare:price_fare,
					longtime:longtime,
					delayed_time:delayed_time,
					delayed_nums:delayed_nums,
					stream:stream,
					des:des
				},
				dataType:'json',
				success:function(data){
					if(data.code==0){
						location.href="phonelive://auction/"+data.info;
						return !1;
					}else{
						layer.msg(data.msg);
					}
					
				},
				error:function(e){
					layer.msg("发布失败");
				}
			});
			
		})
		
	})()