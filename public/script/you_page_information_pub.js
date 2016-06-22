var wml = new (function () {
  var modules = {}

  function Module(fn) {
    this.exports = {}
    this.fn = fn
  }

  function require(name) {
    return modules[name].create();
  }

  Module.prototype = {
    require: require
    , create: function () {
      if (this.created) return this.exports;
      var result = this.fn(this.require, this.exports)
      if (result) this.exports = result;
      this.created = true;
      return this.exports;
    }
  }

  function define(name, fn) {
    if (modules[name]) {
      return modules[name].exports;
    }
    modules[name] = new Module(fn)
  }

  this.define = define
  this.run = function (name) {
    require(name)
  }
})();

wml.define("you/app/login", function (require, exports) {
  function login(succ_cbk) {
    $.ajax({
      "type": "GET"
      , "url": "/news/user"
      , "dataType": "json"
      , "data": {}
      , "beforeSend": function () {
      }
      , "success": function (res) {
        if (res.code === 200) {
          succ_cbk && succ_cbk(res);
          return;
        }
        ;
        location.href = '/news/login'
      }
      , "error": function () {
      }
      , "complete": function () {
      }
    })
  }

  exports.login = login
});
wml.define("component/shareTmp", function (require, exports) {
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

  return function (obj, data) {
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
wml.define("you/app/link", function (require, exports) {
  var isDebug = false;

  function _formatDebug(url) {
    return url.replace(/\//g, '_') + '.html';
  }

  exports.inforDetail = function (id) {
    var url = 'information/detail'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url + '?id=' + id
  }
  exports.inforList = function () {
    var url = 'information/list'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url
  }
  exports.inforPub = function () {
    var url = 'information/pub'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url
  }

  exports.questionList = function () {
    var url = 'question/list'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url
  }

  exports.questionDetail = function (id) {
    var url = 'question/detail'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url + '?id=' + id
  }
  exports.questionPub = function () {
    var url = 'question/pub'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url
  }
  exports.aboutus = function () {
    var url = 'welcome/aboutus'
    if (!isDebug) {
      url = _formatDebug(url)
    }
    return '/' + url
  }

});
wml.define("you/page/information_pub", function (require, exports) {
  var login = require('you/app/login')
  login.login();
  var shareTmp = require('component/shareTmp')
  var gotoLink = require('you/app/link')

  $('.price_wrap').on('click', '.price_item', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
    $('.price_custom').val('')
  }).on('input', '.price_custom', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
    var val = $(this).val();
    if (!val) return;
    var value = $(this).val();
    var length = $(this).val().length;
    var first = value.indexOf(".");//判断第一个小数点所在位置
    var last = value.lastIndexOf(".");//判断最后一个小数点所在的位置
    var temp_length = value.split(".").length - 1;//含有.的个数
    if(first == -1 || (!isNaN(value) && (temp_length == 1) && (first==last) && (length - last <= 3) )){
    }else{
      $(this).val(value.substr(0, Math.min(value.length, value.indexOf('.') + 3)));
    }
  });
  $('.event_type_wrap').on('click', '.price_item', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
  });

  // $('.hide_con_btn').on('click', function(event) {
  // 	var item = $('.hide_con_wrap').toggleClass('act')
  // 	if(item.hasClass('act')){
  // 		$('#qrcode_content').focus()
  // 	}
  // });

  var uploading = false;

  // 图片上传最大数量限制
  function getFileInfo(file) {
    var type = 'image';
    if (/\.(mkv|3gp|mp4|avi|mpg|mpe|mpge|mov|wmv|rm|rmvb|ogg|qt|amr)$/.test(file.name)) {
      type = 'video';
    }
    if(/\.(exe|sh|bin|bat)$/.test(file.name)){
      return false;
    }else{
      return {
        type: type,
        size: file.size
      }
    }
  }

  function uploadValide(fileInfo, $this) {
    var ret = true;
    if(!fileInfo){
      alert('上传类型不允许');
      ret = false;
    }
    if (fileInfo.type == 'image') {
      if (parseInt($this.attr('uploadImageNum')) >= parseInt($this.attr('data-max-image'))) {
        alert('上传图片数量最多不能超过' + $this.attr('data-max-image') + '张!');
        ret = false;
      }
      if (fileInfo.size > 80 * 1024 * 1024) { // 图片大小不能超过2m
        alert('上传图片大小最大不能超过80M!');
        ret = false;
      }
    }
    if (fileInfo.type == 'video') {
      if (parseInt($this.attr('uploadVideoNum')) >= parseInt($this.attr('data-max-video'))) {
        alert('上传数量最多不能超过' + $this.attr('data-max-video') + '个!');
        ret = false;
      }
      if (fileInfo.size > 300 * 1024 * 1024) { // 视频大小不能超过20m
        alert('上传视频大小最大不能超过300M!');
        ret = false;
      }
    }
    return ret;
  }
  /*
  $('#content').on('input', function (event) {
    var _l = $(this).val().length;
    var _i;
    if (_l > 200) {
      $(this).val($(this).val().substr(0, 200));
      _i = 0;
      $('#ssz-limit-1').html("写的也太多了吧，这里请不要超过200字！");//modify duan
    } else {
      _i = 200 - _l;
    }

  })

  $('#qrcode_content').on('input', function (event) {
    var _l = $(this).val().length;
    var _i;
    if (_l > 2000) {
      $(this).val($(this).val().substr(0, 2000));
      _i = 0;
      $('#ssz-limit-1').html("写的也太多了吧，这里请不要超过2000字！");//modify duan
    } else {
      _i = 2000 - _l;
    }
    // $('#ssz-limit-2').html(_i);
  });
  */
  //add by duan
  $('#thumb_des').on('input', function (event) {
    var _l = $(this).val().length;
    if (_l > 500) {
      $(this).val($(this).val().substr(0, 500));
      $('#ssz-limit-3').html("写的也太多了吧，这里请不要超过500字！");//modify duan
    }

  });

  $('input[type=file]').on('change', function (event) {
    var _this = this
    var file = this.files[0]
    console.log(file);
    if (!file) return;
    // 上传验证
    var $input = $(this);
    var fileInfo = getFileInfo(file);
    if (!uploadValide(fileInfo, $input)) {
      return;
    }
    var form = new FormData();
    form.append('file', file)
    var $item = $(shareTmp('upload_item_tpl'))
    $(this).parents('.upload_btn').before($item);

    var ajax = $.ajax({
      url: '/news/upload'
      , type: 'POST'
      , data: form
      , processData: false
      , contentType: false
      , dataType: 'json'
      , beforeSend: function () {
        uploading = true;
      }
      , success: function (res) {
        console.log(res)
        if (res.code == 200) {
          //duan
          if("hide_file_input"==$input.attr("id")){
            var file_num = $input.attr(res.data.file_type+'Num');
            var num = 1;
            if(file_num!=null){
              num = parseInt($input.attr(res.data.file_type+'Num')) + 1;
            }
            $input.attr(res.data.file_type+'Num', num);//duan
          }

          if (res.data.file_type == 'vedio') {
            $input.attr('uploadVideoNum', parseInt($input.attr('uploadVideoNum')) + 1);
            $item.attr('type', 'vedio');
            $item.html('<a href="'+res.data.url+'" class="table col-4 mt20 center p0 img_item left">' +
                '<span class="ssz-span table-cell align-middle overflow-hidden">' +
                '<img alt="点击播放" src="' + res.data.imgurl + '"></a></span>');

            //$item.html('<video width="1rem" height="1rem"  src="' + res.data + '" controls="controls">');
          } else {
            $input.attr('uploadImageNum', parseInt($input.attr('uploadImageNum')) + 1);
            $item.attr('type', res.data.file_type);
            $item.html('<a href="'+res.data.url+'" class="table col-4 mt20 center p0 img_item left"><span class="ssz-span table-cell align-middle overflow-hidden">' +
                '<img alt="点击查看" src="' + res.data.imgurl + '"></span></a>');
          }
        } else {
          alert(res.msg)
          $item.remove()
        }
      }
      , error: function (xhr, type) {
        if (type == 'abort') {

        } else {
          alert('上传失败')
        }
        $item.remove()
      }
      , complete: function () {
        $(_this).val('')
        uploading = false;
        ajax = null
      }
    });

    $item.on('click', function (event) {
      if (confirm('确定删除这个文件吗？')) {
        $item.remove();
        if (!uploading) {
          var type = $item.attr('type')
          if("hide_file_input"==$input.attr("id")){
            $input.attr(type+'Num', parseInt($input.attr(type+'Num')) - 1);
          }
          if (type == 'video') {
            $input.attr('uploadVideoNum', parseInt($input.attr('uploadVideoNum')) - 1);
          } else {
            $input.attr('uploadImageNum', parseInt($input.attr('uploadImageNum')) - 1);
          }
        }
        ajax && ajax.abort()
      }
    });
  });

  $('.pub_btn').on('click', function (event) {
    if (this.isSub) return;
    if (uploading) {
      alert('正在上传文件，请稍后发布')
      return;
    }
    var _this = this;
    var content = $('[name=content]').val()
    , content_img = []
    , thumb = []
    , price = 0
    , confirm_status = $('[name=confirm_status]').val()
    , qrcode_content = $('[name=qrcode_content]').val();
    if (!content) {
      alert('请输入现场信息显示部分文字');
      $('[name=content]').focus();
      return;
    } else if (content.length > 300) {
      alert('现场信息显示部分文字限制300个！');
      $('[name=content]').focus();
      return;
    }
    if (!qrcode_content) {
      alert('请输入现场信息支付部分文字');
      $('[name=qrcode_content]').focus();
      return;
    } else if (qrcode_content.length > 2000) {
      alert('现场信息支付部分文字限制2000个！');
      $('[name=qrcode_content]').focus()
      return;
    }
    //统计个数，不包含预览的文件个数
    var free_number = $("#free_number").val();
    var filenumber={};
    filenumber["vedio_count"] = 0;
    filenumber["image_count"] = 0;
    filenumber["voice_count"] = 0;
    filenumber["txt_count"] = 0;
    filenumber["other_count"] = 0;

    $('.hide_upload_wrap .upload_item').each(function (i, ele) {
      var type = $(ele).attr("type");

      if(free_number>0 && thumb.length<free_number){
        thumb.push($(ele).html())
      }else{
        filenumber[type+"_count"] =  parseInt(filenumber[type+"_count"]) + 1;
        content_img.push($(ele).html());
      }

    });

    var $priceAct = $('.price_wrap').find('.act')
    if ($priceAct.is('[data-value]')) {
      price = $priceAct.attr('data-value');
    } else {
      price =  $priceAct.val();
      if (price < 2) {
        alert('至少定价2元！');
        $priceAct.focus();
        return;
      } else if (price > 1000) {
        alert('最多定价1000元！');
        $priceAct.focus();
        return;
      }
    }
    price = price + '';
    
    if ($('.event_type_wrap .price_item.act').length == 0) {
      alert('请选择标签');
      return;
    }
    var params = {
      type: 'news'
      , content: content
      , qrcode_content: qrcode_content
      , content_img: content_img.join(',')
      , thumb: thumb.join(',')
      , thumb_des: $('[name=thumb_des]').val()
      , price: price.length == 0 ? 0 : price
      , event_type: $('.event_type_wrap .price_item.act').attr('data-value')
      , confirm_status: $('[name=confirm_status]:checked').length
      ,file_number:filenumber
    };

    $.ajax({
      url: '/news/add'
      , type: 'POST'
      , dataType: 'json'
      , data: params
      , beforeSend: function () {
        _this.isSub = true;
        _this.innerHTML = '正在发布'
      }
      , success: function (res) {
        //alert(res.msg)
        if (res.code == 200) {
          location.href = gotoLink.inforDetail(res.data.id)
        }
      }
      , error: function () {
        alert('系统错误')
      }
      , complete: function () {
        _this.isSub = false;
        _this.innerHTML = '发布'
      }
    })
  });

});
wml.run("you/page/information_pub");
