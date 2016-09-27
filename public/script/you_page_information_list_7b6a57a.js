var wml = new (function(){
	var modules = {}
	function Module(fn){
		this.exports = {}
		this.fn = fn
	}

	function require(name){
		return modules[name].create();
	}

	Module.prototype = {
		require: require
		,create: function(){
			if(this.created) return this.exports;
			var result = this.fn(this.require, this.exports)
			if(result) this.exports = result;
			this.created = true;
			return this.exports;
		}
	}

	function define(name, fn){
		if(modules[name]){
			return modules[name].exports;
		}
		modules[name] = new Module(fn)
	}

	this.define = define
	this.run = function(name){
		require(name)
	}
})();

wml.define("you/app/login", function(require, exports){
function login(succ_cbk){
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
}

exports.login = login
});
wml.define("you/app/link", function(require, exports){
var isDebug = false;
var extra = '_=' + new Date().getTime();
var andExtra = '&' + extra;
var qExtra = '?' + extra;

function _formatDebug(url){
	return url.replace(/\//g, '_') +'.html';
}

exports.inforDetail = function(id){
	var url = 'information/detail'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url+'?id='+id + andExtra;
}
exports.inforList = function(){
	var url = 'information/list'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url + qExtra;
}
exports.inforPub = function(){
	var url = 'information/pub'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url + qExtra;
}

exports.questionList = function(){
	var url = 'question/list'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url + qExtra;
}

exports.questionDetail = function(id){
	var url = 'question/detail'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url+'?id='+id + andExtra;
}
exports.questionPub = function(){
	var url = 'question/pub'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url + qExtra;
}
exports.aboutus = function(){
	var url = 'welcome/aboutus'
	if(!isDebug){
		url = _formatDebug(url)
	}
	return '/'+url + qExtra;
}

});
wml.define("component/shareTmp", function(require, exports){
var cache = {}
function tmpl(str, data) {
	var fn = !/\W/.test(str) ?
		cache[str] = cache[str] ||
		tmpl(document.getElementById(str).innerHTML) :
		new Function("obj",
			"var p=[],print=function(){p.push.apply(p,arguments);};" +
			"with(obj){p.push('" +
			str
			.replace(/[\r\t\n]/g, " ")
			.split("<\?").join("\t")
			.replace(/((^|\?>)[^\t]*)'/g, "$1\r")
			.replace(/\t=(.*?)\?>/g, "',$1,'")
			.split("\t").join("');")
			.split("\?>").join("p.push('")
			.split("\r").join("\\'") + "');}return p.join('');")

	return data ? fn(data) : fn
}

return function(obj, data) {
	if (!document.getElementById(obj)) {
		console.log(obj + ' is lost')
		return
	}

	data = data || Object

	try {
		var shareTpl = tmpl(obj, data)
	} catch (e) {
		console.log(e)
	}

	return shareTpl
}

});
wml.define("you/app/menu", function(require, exports){
return function(menu){
	var str = ''
	for (var i = 0; i < menu.length; i++) {
		str += '<li><a href="'+menu[i].link+'">'+menu[i].txt+'</a></li>'
	};
	return str;
}
});
wml.define("you/page/information_list", function(require, exports){
var login = require('you/app/login')
login.login();
var gotoLink = require('you/app/link')

var shareTmp = require('component/shareTmp')
var menu = require('you/app/menu')

var TYPE = 'news'

var menuData = [
	{link:gotoLink.aboutus(),txt:'关于我们'}
]
$('.menu_list').html(menu(menuData))

$('.menu_btn').on('click', function(event) {
	event.preventDefault();
	$('#intro_page').show();
	$('body').addClass('modal-open');
	event.stopPropagation();
})
$('#intro_page .close').click(function(){
	$('#intro_page').hide();
	$('body').removeClass('modal-open');
});
$(window).on('click', function(event) {
	if($(this.target).parents('.menu_wrap').length===0){
		setTimeout(function(){
			$('.menu_list_wrap').hide()
		},100)
	}
});

$('.con_wrap').on('click', '.con_item', function(event) {
	event.preventDefault();
	var id = $(this).attr('data-id')
	location.hash = '#' + $(this).attr('id');
	location.href = gotoLink.inforDetail(id)
});

var page = 0
	,limit =10
	,isLoading = false
	,isFinish = false
	,hashToken = '#item'
	,hashId = 0;

var hash = window.location.hash;
if (hash && hash.indexOf(hashToken) === 0) {
	hash = hash.replace(hashToken, '');
	hash = parseInt(hash);
	if (!isNaN(hash) && hash > 0) {
		hashId = hash;
	}
}

$('#search_form').on('submit', function(event) {
	event.preventDefault();
	var key = $(this).find('[name=key]').val().trim()
	if(!key){
		page = 0
		isFinish = false
		loadData(true)
	} else {
		search(key)
	}
});

//init
$(window).on('scroll', function(event) {
	var _this = this
	if($('.main_container').height() - $(_this).height() < $(_this).scrollTop()){
		loadData()
	}
}).trigger('scroll')
function loadData(isInit){
	if(hashId == 0 && (isLoading || isFinish)) return;
	$.ajax({
		"type":"GET"
		,"url":"/news/index"
		,"dataType":"json"
		,"data":{
			page:++page
			,type:TYPE
		}
		,"beforeSend":function(){
			$('.loading_txt').show()
			isLoading = true;
		}
		,"success":function(res){
			if(res.code==200){
				var html =''
				res.data.forEach(function(item){
					html += shareTmp('con_item_tpl',{item:item})
				});
				if(isInit){
					$('.con_wrap').html(html)
					
				} else {
					$('.con_wrap').append(html)
				}
				if(res.data.length<limit){
					$('.no_more').show()
					isFinish = true;
				}
				if (hashId > 0 && res.data && res.data.length > 0) {
					var len = res.data.length;
					var maxId = res.data[0].id;
					var minId = res.data[len-1].id;
					if (hashId <= maxId && hashId >= minId) {
						var $item = $('#item'+hashId);
						if ($item) {
							$item[0].scrollIntoView();
						}
						hashId = 0;
					} else if (minId > hashId) {
						loadData();
					} else {
						hashId = 0;
					}
				}
			}
		}
		,"error":function(){}
		,"complete":function(){
			if (hashId == 0) {
				$('.loading_txt').hide()
				isLoading =false;
			}
		}
	})
}

function search(key){
	if(isLoading) return;
	$.ajax({
		"type":"GET"
		,"url":"/news/search"
		,"dataType":"json"
		,"data":{
			key:key
			,type:TYPE
		}
		,"beforeSend":function(){
			$('.loading_txt').show()
			isLoading = true;
		}
		,"success":function(res){
			if(res.code==200){
				var html =''
				res.data.forEach(function(item){
					html += shareTmp('con_item_tpl',{item:item})
				});
				$('.con_wrap').html(html);

				$('.no_more').show();
				isFinish = true;
			}
		}
		,"error":function(){}
		,"complete":function(){
			$('.loading_txt').hide()
			isLoading =false;
		}
	})
}

$('.tab_wrap').on('click', '[link-type]', function(event) {
	var linkFn = $(this).attr('link-type')
	location.href =gotoLink[linkFn]()
});



});
wml.run("you/page/information_list");
