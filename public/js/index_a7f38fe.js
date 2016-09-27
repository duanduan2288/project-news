
;$(function () {
	var local;
	var map;
	var lp;
	var testLimit = 0;
	var myIcon = new BMap.Icon("http://developer.baidu.com/map/jsdemo/img/fox.gif", new BMap.Size(300,157));
	var userList;
	var piCiNum = 0;// 换到那一批了
	var piciLen = 6; // 批次人数
	var lastLp = {lng: '116.404', lat: '39.915'};//最后一次坐标位置
	var mapApp = {
		getParams: function () {
			var params = {};
			var search = location.search;
			search = search.substr(1);
			var map = search.split('&');
			for (var i = 0; i < map.length; i++) {
				var qs = map[i].split('=');
				params[qs[0]] = decodeURIComponent(qs[1]);
			}
			return params;
		},
		getParamValue: function (key) {
			return mapApp.getParams()[key];
		},
		login: function(succ_cbk){
			$.ajax({
				"type":"GET"
				,"url":"/news/user"
				,"dataType":"json"
				, "async": false
				,"data":{}
				,"beforeSend":function(){}
				,"success":function(res){
					if(res.code===200) {
						succ_cbk && succ_cbk(res);
						return;
					};
					location.href = '/news/login'
				}
				,"error":function(){}
				,"complete":function(){}
			})
		},
		init: function () {
			if(!mapApp.getParamValue('code')) {
				// mapApp.login(function(data) {

				// });
			}
			$('#l-map').height($(window).height() - 67);
			map = new BMap.Map("l-map");
			map.centerAndZoom(new BMap.Point(116.404, 39.915), 14);
			var options = {
			    onSearchComplete: function (results) {    
			        if (local.getStatus() == BMAP_STATUS_SUCCESS){        
			                var s = [];
			                if (results.getCurrentNumPois()) {
			                	$('#searchList').show();
			                	for (var i = 0; i < results.getCurrentNumPois(); i ++) {
				                	s.push('<div class="poi poi-click" data-lp=' + JSON.stringify(results.getPoi(i).point) + '><div class="s-title">' + results.getPoi(i).title + '</div><div class="s-desc">' + results.getPoi(i).address +' </div></div>');   
				                }      
				            	$("#contentList").html(s.join(''));
			                } else {
			                	$('#searchList').show();
			                	$("#contentList").html('<div class="poi-nodata">没有您搜索的地点</div>');      
			                }
			          }      
			      }      
			};      
			local = new BMap.LocalSearch(map, options);
			//{lng: '116.321984', lat: '40.043131'}
			mapApp.getAllSays({lng: '116.404', lat: '39.915'});
			map.addEventListener("touchend", function() {
			    var center = map.getCenter();
			    if (Math.abs(center.lng - lastLp.lng) > 0.002
			    	|| Math.abs(center.lat - lastLp.lat) > 0.002) {
			    	clearTimeout(window.timer);
				    window.timer = setTimeout(function () {
				    	mapApp.getAllSays({lng: center.lng, lat: center.lat});
				    }, 500);   
			    }
			});
			mapApp.getStateStatus();
		},
		getStateStatus: function () {
			$.ajax({
				url: '/tweets/geosearch',
				data: {lng: '116.404', lat: '39.915'},
				dataType: 'json',
				success: function (data) {
					if (data.code == 200) {
						$('.operation .pubSay').show();
						$('.operation .myStateSay,.operation .exitSay').hide();
					} else {
						$('.operation .pubSay').hide();
						$('.operation .myStateSay,.operation .exitSay').show();
					}
				},
				error: function () {
					$('.operation .pubSay').show();
					$('.operation .myStateSay,.operation .exitSay').hide();
				}
			});
		},
		//  退出现场
		exitSay: function () {
			$('.operation .pubSay').show();
			$('.operation .myStateSay,.operation .exitSay').hide();
		},
		// getUserInfo: function () {
		// 	var params = {
		// 		appid: 'wxb56787ede27d5697',
		// 		redirect_uri: 'http://www.yayachuan.com/index.html',
		// 		response_type: 'code',
		// 		scope: 'snsapi_base',
		// 		'state': 123
		// 	};
		// 	var search = [];
		// 	for (var i in params) {
		// 		if (i && params.hasOwnProperty(i)) {
		// 			search.push(i + '=' + encodeURIComponent(params[i]));
		// 		}
		// 	}
		// 	location.href = 'https://open.weixin.qq.com/connect/oauth2/authorize?' + search.join('&') + '#wechat_redirect';
		// },
		getAllSays: function (ll) {
			$.getJSON('/tweets/geosearch', {location: ll.lng + ',' + ll.lat, zoom: map.getZoom()}, function (data) {
				if (data.code == 200) {
					lastLp = {lng: ll.lng, lat: ll.lat};
					var list = data.result.contents;
					// var allOverlay = map.getOverlays();
					if (list.length) {
						$('#sayList').show().find('._title').text('当前区域内有' + list.length + '人');
					} else {
						$('#sayList').show().find('._title').text('当前区域内暂没有人在现场');
					}
					userList = list;
					var len = list.length > piciLen ? piciLen : list.length;
					if (list.length > piciLen) {
						$('.operation .changePici').show();
					} else {
						$('.operation .changePici').hide();
					}
					map.clearOverlays();
					for(var i = 0; i < len; i++) {
						mapApp.addMarker({
							src: list[i].avatar && list[i].avatar.sml,
							name: list[i].nickname,
							lng: list[i].location[0],
							lat: list[i].location[1],
							content: list[i].content,
							uid: list[i].uid
						});
					}
				}
			});
		},
		searchLayer: function () {
			$(this).closest('.search').hide().siblings('.search-input').show();
		},
		hidesearchLayer: function () {
			$(this).closest('.search-input').hide().siblings('.search').show();
			$('#contentList').html('');
			$('#searchList').hide();
		},
		selectTab: function () {
			$(this).addClass('active').siblings().removeClass('active');
		},
		searchList: function () {
			var val = $(this).val();
			clearTimeout(window.timer);
			window.timer = setTimeout(function () {
				local.search(val);
			}, 300);
		},
		goAddress: function () {
			var localLP = $(this).data('lp');
			try {
				mapApp.hidesearchLayer.call($('.cancel-btn'));
				map.centerAndZoom(new BMap.Point(localLP.lng, localLP.lat), 14);
			} catch (e) {}
		},
		myStateSay: function () {
			$('#publishLayer').show();
			$('.getGeo').text('定位中...').removeClass('getGeo');
			mapApp.getLocation();
		},
		pubSayLayer: function () {
			$('#publishLayer').show();
			$('.getGeo').text('定位中...').removeClass('getGeo');
			mapApp.getLocation();
		},
		hidepubSayLayer: function () {
			$('#publishLayer').hide();
		},
		publish: function () {
			var val = $('#saySomething').val();
			if (!val || val.length > 40) {
				return alert('请输入说说');
			}
			var type = $('#tab .active').text();
			if (!type) {
				return alert('请选择你的现场类型');
			}

			if (lp.lng || lp.lat) {
				$.post('/tweets/tweets', {
					latitude: lp.lat,
					longitude: lp.lng,
					content: val,
					event_type: type
				}, function (data) {
					if (data && data.code == 200) {
						mapApp.addMarker({
							src: 'http://app.baidu.com/map/images/tiananmen.jpg',
							name: '用户',
							lng: lp.lng,
							lat: lp.lat,
							content: $('#saySomething').val(),
							uid: ''
						});
						$('#publishLayer').hide();
					} else {
						alert('发表失败');
					}
				}, 'json');
			} else {
				alert('请先定位您的位置');
			}
			
		},

		/**
		* obj : lng, lat, src, name, content
		*/
		addMarker: function (obj) {
			var sContent = $('#shuo').html();
			var appHtml = $(sContent);
			appHtml.find('#img').attr('src', obj.src);
			appHtml.find('#userName').text(obj.name);
			appHtml.find('#content').text(obj.content);
			var point = new BMap.Point(obj.lng, obj.lat);
			var marker = new BMap.Marker(point, {icon: myIcon});
			var infoWindow = new BMap.InfoWindow(appHtml.html());  // 创建信息窗口对象
			// map.addControl(new BMap.ZoomControl());          //添加地图缩放控件

			map.addOverlay(marker);
			marker.info = obj;
			// marker.openInfoWindow(infoWindow);
			marker.addEventListener("click", function () {
			    // this.openInfoWindow(infoWindow);
			   // $('.BMap_pop div').css({opacity: 0});
			   // 图片加载完毕重绘infowindow
			    // document.getElementById('img').onload = function (){
				   // 	infoWindow.redraw();   //防止在网速较慢，图片未加载时，生成的信息框高度比图片的总高度小，导致图片部分被隐藏
			    // }
				mapApp.markershowTip(marker.info);
			});
		},
		// 显示用户说说
		markershowTip: function (obj) {
			$('#kansayList').attr('data', JSON.stringify(obj)).show();
			var html = '<div class="saycontent">' + (obj.name || '匿名用户')+ '说:' + obj.content +'</div>';
			$('#kansayList ._title').html(html);
		},
		getGeo: function () {
			$(this).text('定位中...').removeClass('getGeo');
			mapApp.getLocation();
		},
		getLocation: function () {
			var lbsGeo = document.createElement('lbs-geo');
			lbsGeo.addEventListener("geofail",function(evt){ //注册事件
				$('.get-geo').text('获取定位失败');
				if ( ++testLimit <= 3) {
              		$('.get-geo').addClass('getGeo');
				}
			});
			lbsGeo.addEventListener("geosuccess",function(evt){ //注册
				var addr = evt.detail.coords;
				lp = {
					lng:  addr.lng,
					lat: addr.lat
				};
				$('#geo').hide();
				mapApp.searchNearBy(evt.detail.address);
				window.markAdress = evt.detail.address;
				$('.get-geo').addClass('showList');
			});
			lbsGeo.setAttribute("enable-modified","true");
			lbsGeo.setAttribute("id","geo");
			document.body.appendChild(lbsGeo);
		},
		searchSayPoint: function () {
			$('#saySomething').attr('disable', 'disable').attr('readonly', 'readonly');
			lp = $(this).data('lp');
			$('.get-geo').text($(this).find('.s-title').text());
			mapApp.hideList();
			setTimeout(function () {
				$('#saySomething').removeAttr('disable').removeAttr('readonly');
			}, 500);
			return false;
		},
		showList: function () {
			$('#selectNearyBy').show();
		},
		hideList: function () {
			$('#selectNearyBy').hide();
			$('.get-geo').text(window.markAdress);
		},
		searchNearBy: function (key) {
			map.centerAndZoom(new BMap.Point(lp.lng, lp.lat), 11);
			var options = {      
			    onSearchComplete: function(results){    
			        if (localsearch.getStatus() == BMAP_STATUS_SUCCESS){        
		                var s = [];
	                	$('#selectNearyBy').show();
	                	for (var i = 0; i < results.getCurrentNumPois(); i ++) {
		                	s.push('<div class="poi search-poi-click" data-lp=' + JSON.stringify(results.getPoi(i).point) + '><div class="s-title">' + results.getPoi(i).title + '</div><div class="s-desc">' + results.getPoi(i).address +' </div></div>');   
		                }      
		            	$("#nearBycontentList").html(s.join(''));
			          }
			      }      
			};      
			var localsearch = new BMap.LocalSearch(map, options);
			localsearch.search(key);
		},
		// 换一批
		changePici: function () {
			var len = userList.length;
			piCiNum++;

			var startNum = piCiNum * piciLen;
			if (startNum > len) {
				piCiNum = 0;
				startNum = 0;
			}
			var endNum = startNum + 6 > (len-1) ? (len -1) : startNum + 6;
			map.clearOverlays();
			for( var i = startNum; i < endNum; i++) {
				mapApp.addMarker({
					src: userList[i].avatar && userList[i].avatar.sml,
					name: userList[i].nickname,
					lng: userList[i].location[0],
					lat: userList[i].location[1],
					content: userList[i].content
				});
			}
		},
		// 关闭查看说说
		closeLayer: function () {
			$('#kansayList').hide();
		},
		replyUserLayer: function () {
			var data = JSON.parse($('#kansayList').attr('data'));
			$('#replyLayer .reply-title').text('回复' + (data.name||'匿名') +':')
			$('#replyLayer').show();
			$('#publishReply').data('id', data.uid);
		},
		hidereplyUserLayer: function () {
			$('#replyLayer').hide();
		},
		// 选择DS金额
		selectDaShang: function () {
			$(this).closest('.da-shang').find('div').removeClass('active');
			$(this).addClass('active');
		},
		replyUser: function () {
			var reply = $('#replyInput').val();
			if (!reply) {
				return alert('请填写回复内容');
			}
			var index = $('.da-shang div[class="active"]').index();
			var money;
			if (index == 3) {
				money = $('#giveMoney').val();
			} else {
				money = $('.da-shang div[class="active"]').text();
			}
			money = parseInt(money, 10);
			if (isNaN(money) || money < 0) {
				return alert('请填写正确的DS金额');
			}
			var params = {
				reply: reply,
				money: money,
				replyUid: $(this).data('id'),
				uid: ''
			}
			$.post('/xxx/xx', params, function (data) {
				if (data.code == 200) {
					$('#replyLayer').hide();
					alert('回复成功');
				} else {
					alert('回复失败')
				}
			}, 'json');
		}
	};
	
	mapApp.init();
	
	$(document)
		.on('tap', '.search-btn', mapApp.searchLayer)
		.on('tap', '.cancel-btn', mapApp.hidesearchLayer)
		.on('tap', '.tab .item', mapApp.selectTab)
		.on('input', '#searchInput', mapApp.searchList)
		.on('tap', '.poi-click', mapApp.goAddress)
		.on('tap', '.pubSay', mapApp.pubSayLayer)
		.on('tap', '.cancel-publish', mapApp.hidepubSayLayer)
		.on('tap', '.publish', mapApp.publish)
		.on('tap', '.getGeo', mapApp.getGeo)
		.on('tap', '.showList', mapApp.showList)
		.on('tap', '.go-back', mapApp.hideList)
		.on('tap', '.search-poi-click', mapApp.searchSayPoint)
		.on('tap', '.exitSay', mapApp.exitSay)
		.on('tap', '.myStateSay', mapApp.myStateSay)
		.on('tap', '.changePici', mapApp.changePici)
		.on('tap', '._close', mapApp.closeLayer)
		.on('tap', '._reply', mapApp.replyUserLayer)
		.on('tap', '#replyLayer .xx', mapApp.hidereplyUserLayer)
		.on('tap', '.da-shang >div', mapApp.selectDaShang)
		.on('tap', '#publishReply', mapApp.replyUser)

});