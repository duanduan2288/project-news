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
wml.define("component/urlHandler", function (require, exports) {
  function parse(url) {
    var r = {
      protocol: /([^\/]+:)\/\/(.*)/i,
      host: /(^[^\:\/]+)((?:\/|:|$)?.*)/,
      port: /\:?([^\/]*)(\/?.*)/,
      pathname: /([^\?#]+)(\??[^#]*)(#?.*)/
    };
    var tmp, res = {};
    res["href"] = url;
    for (p in r) {
      tmp = r[p].exec(url);
      res[p] = tmp[1];
      url = tmp[2];
      if (url === "") {
        url = "/";
      }
      if (p === "pathname") {
        res["pathname"] = tmp[1];
        res["search"] = tmp[2];
        res["hash"] = tmp[3];
      }
    }
    return res;
  }

  function getParamObj(url) {
    var paramStr = parse(url).search.split('?')[1]
    var result = {}
    if (!paramStr) return result;
    var arr = paramStr.split('&')
    for (var i = 0; i < arr.length; i++) {
      var a2 = arr[i].split('=')
      result[a2[0]] = a2[1]
    }
    ;
    return result;
  }

  function getParam(url, key) {
    var paramObj = getParamObj(url)
    return paramObj[key] || '';
  }

  exports.parse = parse
  exports.getParamObj = getParamObj
  exports.getParam = getParam
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
wml.define("you/app/top_back", function (require, exports) {
  var shareTmp = require('component/shareTmp')

  return function (link, name, avatar, time) {
    return shareTmp('top_back_tpl', {
      link: link
      , name: name
      , avatar: avatar
      , time: time
      , confirm_status: "xxx"
    })
  }
});
wml.define("you/app/wxpay", function (require, exports) {
  var isLoading = false
  var isPaying = false
  var $mask = $('#PAY_loading_wrap')

  function callPay(type, id, total_fee, cbk) {
    if (isLoading || isPaying) return;
    $.ajax({
      url: '/order/pay',
      type: 'post',
      dataType: 'json',
      data: {
        'type': type
        , 'id': id
        , 'total_fee': total_fee
      },
      beforeSend: function () {
        isLoading = true;
        $mask.show()
      },
      success: function (res) {
        isPaying = true;
        var ready = function () {
          WeixinJSBridge.invoke(
            'getBrandWCPayRequest',
            res.data,
            function (res) {
              //cbk(err , data)
              isPaying = false;
              cbk && cbk(res.err_msg == "get_brand_wcpay_request：ok", res)
            }
          );
        }
        _pay(ready);
      },
      complete: function () {
        isLoading = false
        $mask.hide()
      }
    })
  }

  function _pay(onBridgeReady) {
    if (typeof WeixinJSBridge == "undefined") {
      if (document.addEventListener) {
        document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
      } else if (document.attachEvent) {
        document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
        document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
      }
    } else {
      onBridgeReady();
    }
  }

  exports.callPay = callPay


});
wml.define("you/page/information_detail", function (require, exports) {
  var login = require('you/app/login')
  login.login();
  var urlHandler = require('component/urlHandler')
  var shareTmp = require('component/shareTmp')
  var gotoLink = require('you/app/link')
  var topBack = require('you/app/top_back')
  var wxpay = require('you/app/wxpay')


  var detail_id = urlHandler.getParam(location.href, 'id')
    , detail_type = 'news'

  $('.main_container').on('click', '.open_btn', function (event) {
    //支付刷新
    event.preventDefault();
    wxpay.callPay('news', detail_id, $('[name=q_price]').val(), function (err, res) {
      location.reload();
      return;
    })
  }).on('click', '.ssz-container [data-value], .opt_wrap [data-value]', function (event) {
    //投票
    // 购买后才能进行评价，否则点击无效
    if ($('.open_btn').length > 0) {
      return false;
    }
    if ($(this).parent().attr('data-me') == 1 || $(this).parent().attr('data-vote-value') != 0) {
      return false;
    }
    if ($(this).attr('data-value') == 1 && $('#cannot-click').length > 0) {
      alert('此为很赞信息，不能进行此种评价!');
      return;
    }
    if (!confirm('确认提交评价吗？')) {
      return false;
    }

    if (this.isDoing) return;
    var _this = this
    event.preventDefault();
    var val = $(this).attr('data-value')
    $.ajax({
      url: '/news/vote',
      type: 'post',
      dataType: 'json',
      data: {
        id: detail_id
        , type: detail_type
        , vote: val
      },
      beforeSend: function () {
        _this.isDoing = true;
      },
      success: function (res) {
        if (res.code == 101 || res.code == 102) {
          console.log(res.msg);
          return;
        }
        alert(res.msg)
        if (res.code == 200) {
          $(_this).parent().children('div').eq(4-val-1).find('img').attr('src', './img/0' + (4 - val) + '.png');
          $ele = $('#ssz-data-' + val);
          $ele.html(parseInt($ele.html()) + 1);
          $(_this).parent().attr('data-vote-value', val);
          var price = $(_this).parent().data('price') ? $(_this).parent().data('price') : 0;
          if (val == 1 && price >= 1) {
            location.reload();
          }
        }
      },
      complete: function () {
        _this.isDoing = false;
      }
    });
  });

  $('.main_container').on('click', '.confirm_wrap [name=confirm_status]', function () {
    $('.confirm_wrap [name=confirm_status]').each(function (i, e) {
      $(e).removeAttr('checked');
    });
    $(this).attr('checked', 'checked');
  });

  var uploading = false;

  // 图片上传最大数量限制
  function getFileInfo(file) {
    var type = 'image';
    if (/\.(mkv|3gp|mp4|avi|mpg|mpe|mpge|mov|wmv|rm|rmvb|ogg|qt)$/.test(file.name)) {
      type = 'video'
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
    //modify duan
    if (parseInt($this.attr('uploadNum')) >= parseInt($this.attr('data-max-file'))) {
      alert('最多上传' + $this.attr('data-max-file') + '个文件!');
      return false;
    }
    if (fileInfo.type == 'image') {
      if (fileInfo.size > 80 * 1024 * 1024) { // 图片大小不能超过2m
        alert('上传图片大小最大不能超过80M!');
        return false;
      }
    }
    if (fileInfo.type == 'video') {
      if (fileInfo.size > 300 * 1024 * 1024) { // 图片大小不能超过20m
        alert('上传视频大小最大不能超过300M!');
        return false;
      }
    }
    return ret;
  }

  $('.main_container').on('click', '.confirm_wrap .btn', function () {
    var v = $('.confirm_wrap [checked]').val();
    $.ajax({
      "type": "POST"
      , "url": "/news/update"
      , "dataType": "json"
      , "data": {
        id: detail_id
        , type: 'news'
        , confirm_status: v
      }
      , "beforeSend": function () {
        $('.confirm_wrap  .btn').text('鉴定中...');
      }
      , "success": function (res) {
        if (res.code == 200) {
          history.back();
          location.reload();
        }
      }
      , "error": function (e) {
        alert(e.message);
      }
    });
  });
  function onTextareaInput($textarea, limit) {
    var len = $textarea.val().length;
    var $hint = $textarea.siblings('.input_hint');
    if (len > limit) {
      $hint.addClass('red');
    } else {
      $hint.removeClass('red');
    }
    $hint.text(limit-len);
  }

//init
  $.ajax({
    "type": "GET"
    , "url": "/news/content"
    , "dataType": "json"
    , "data": {
      id: detail_id
      , type: detail_type
    }
    , "beforeSend": function () {
    }
    , "success": function (res) {
      if (res.code == 200) {
        var item = res.data[0]
        var html = shareTmp('detail_tpl', {
          item: item
        })
        $('.main_container').html(html).before(topBack(gotoLink.inforList(), item.nickname, item.avatar, item.created_at));
        /*
        if (!item.is_me || item.is_me && item.confirm_status != 0) {
          var a = ['', '小丫鉴定中', '小丫觉得很赞', '小丫觉得有效', '小丫不确定', '小丫觉得部分不确定', '小丫觉得扯淡'];
          $('.confirm_status_tip').text(a[item.confirm_status]).css('display', 'block').addClass('ssz-ddd');
        } else {
          $('.confirm_status_tip').text('申请小丫鉴定').addClass('ssz-btn');
          $('.confirm_status_tip').on('click', function () {
            if (confirm('确定申请小丫鉴定吗？')) {
              $.post('/news/update', {type: 'news', id: detail_id, 'confirm_status': 1}, function(data) {
                if (data.code == 200) {
                  alert('申请鉴定成功！');
                  location.reload();
                } else {
                  alert('程序开了个小差...');
                }
              });
            }
          });
        }
        */
        if (item.confirm_status != 0) {
          var a = ['', '小丫鉴定中', '小丫觉得很赞', '小丫觉得有效', '小丫不确定', '小丫部分不确定', '小丫觉得无效'];
          $('.confirm_status_tip').html('<span style="color:orange;">丫</span><span style="color:#48abff;">丫</span>现场信息审核：本条信息'+ a[item.confirm_status]);
        } else if (item.is_me && item.price > 0) {
          $('.confirm_status_tip').text('申请小丫鉴定').addClass('ssz-btn');
          $('.confirm_status_tip').on('click', function () {
            if (confirm('确定申请小丫鉴定吗？')) {
              $.post('/news/update', {type: 'news', id: detail_id, 'confirm_status': 1}, function(data) {
                if (data.code == 200) {
                  alert('申请鉴定成功！');
                  location.reload();
                } else {
                  alert('程序开了个小差...');
                }
              });
            }
          });
        } else {
          $('.confirm_status_tip').html('<span style="color:orange;">丫</span><span style="color:#48abff;">丫</span>现场，传递现场信息给需要现场的人');
        }
        // 显示删除按钮
        if (item.is_me) {
          $('.ssz-delete').css('display', 'block');
          $('.ssz-delete').on('click', function (event) {
            event.preventDefault();
            if (!confirm('是否确定删除')) {
              return;
            }
            $.post('/news/deletenew', {id: detail_id}, function(data) {
              if (data.code == 200) {
                alert(data.msg);
                location.href = '/information_list.html';
              }
            });
          });
        }

        $('#ssz-description').on('input', function(){
          onTextareaInput($(this), 500);
        });

        // 提交

        $('#ssz-submit').on('click', function (event) {
          if (this.isSub) return;
          /*if (this.innerHTML.length > 2) {
            return false;
          }*/
          if (!confirm("是否确定提交？")) {
            return false;
          }
          if (uploading) {
            alert('正在上传文件，请稍后发布')
            return;
          }
          var _this = this
          var content_img = [];
          $('.other_upload_wrap .upload_item').each(function (i, ele) {
            content_img.push($(ele).html())
          });
          var des = $('#ssz-description').val();
          if (des.length > 500) {
            alert('描述不能超过500字');
            return;
          }
          if (des.length == 0) {
            alert("请填写描述部分。");
            return;
          }
          var params = {
            vote_id: detail_id
            , type: detail_type
            , files: content_img.join(',')
            , description: des
          }
          $.ajax({
            url: '/news/orderpayback'
            , type: 'POST'
            , dataType: 'json'
            , data: params
            , beforeSend: function () {
              _this.isSub = true;
              _this.innerHTML = '正在提交'
            }
            , success: function (res) {
              if (res.code == 200) {
                alert('申请信息失实已提交，请等待审核...');
                location.reload();
                // location.href = gotoLink.inforDetail(res.data.id)
                // alert(res.msg);
              } else {
                alert('提交失败');
              }
            }
            , error: function () {
              alert('提交失败');
            }
            , complete: function () {
              _this.isSub = false;
              /*_this.innerHTML = '申请信息失实已提交，请等待审核...';
              $(_this).css('background-color', '#f8f7f7');
              $(_this).css('color', 'black');*/
            }
          })
        });
        // 上传图片
        $('#other_file_input').change(function (event) {
          var _this = this;
          var file = this.files[0];
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
                $input.attr('uploadNum', parseInt($input.attr('uploadNum')) + 1);//duan
                if (fileInfo.type == 'video') {
                  $item.attr('type', 'video');
                  $item.html('<a href="'+res.data.url+'" class="table col-4 mt20 center p0 img_item left">' +
                      '<span class="ssz-span table-cell align-middle overflow-hidden">' +
                      '<img alt="点击播放" src="' + res.data.imgurl + '"></a></span>');

                  //$item.html('<video width="1rem" height="1rem"  src="' + res.data + '" controls="controls">');
                } else {
                  $item.attr('type', 'image');
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
                $input.attr('uploadNum', parseInt($input.attr('uploadNum')) - 1);//duan
                /*var type = $item.attr('type')
                if (type == 'video') {
                  $input.attr('uploadVideoNum', parseInt($input.attr('uploadVideoNum')) - 1);
                } else {
                  $input.attr('uploadImageNum', parseInt($input.attr('uploadImageNum')) - 1);
                }*/
              }
              ajax && ajax.abort()
            }
          });
        });

        $('#for_pub_btn').on('click', function () {
          $(this).hide();
          $('#shishi-auth').show();
        })
        $('#cancel-btn').click(function(){
          $('#for_pub_btn').show();
          $('#shishi-auth').hide();
        });

        // var $eles = $('.ssz-p-2 a');
        // $eles.height($eles.width());
        // $('.ssz-p-2 a span').height($eles.width());
        // $('.ssz-p-2 a span').width($eles.width());
        // $eles.each(function() {
        //   // if ($(this).children('img').height() < $eles.height()) $(this).children('img').height($eles.height());
        //   var $img = $(this).children('span').children('img');
        //   console.log($img.width(), $img.height());
        //   if ($img.width() < $img.height()) {
        //     // $img.addClass('ssz-image-1');
        //     $img.css('width', 'auto');
        //     $img.css('height', $('.ssz-p-2').width() * .3333 - 4);
        //   } else {
        //     // $img.addClass('ssz-image-2');
        //     $img.css('width', $('.ssz-p-2').width() * .3333 - 4);
        //     $img.css('height', 'auto');
        //   }
        // });
        // $eles = $('.pic_con_wrap .clearfix a');
        // $eles.height($eles.width());
        // $eles.each(function() {
        //   var $img = $(this).children('span').children('img');
        //   if ($img.width() < $img.height()) {
        //     $img.css('width', 'auto');
        //     $img.css('height', $('.ssz-p-2').width() * .3333 - 4);
        //   } else {
        //     $img.css('width', $('.ssz-p-2').width() * .3333 - 4);
        //     $img.css('height', 'auto');
        //   }
        //   // $(this).children('span').children('img').css('clip', 'rect(0,' + $eles.width() + 'px,' + $eles.width() + 'px, 0)');
        // });
        $('.share_btn').click(function(){
          $('#share_page').show();
        });
        $('#share_page').click(function(){
          $('#share_page').hide();
        });
        $('.show_after_loaded').show();
        $('#intro_btn').click(function(){
          $('#intro_page').show();
        });
        $('#intro_page .close').click(function(){
          $('#intro_page').hide();
        });
        wxShareConfig(item);
      } else {
        loadErr(res.msg)
      }
    }
    , "error": function () {
      loadErr();
    }
    , "complete": function () {
    }
  })

  function loadErr(msg) {
    msg = msg || '获取数据失败'
    $('.tip').html(msg)
  }
  function wxShareConfig(item) {
    var price = item.price > 0 ? '开价'+item.price+'元' : '公开';
    var title = item.nickname+'发布了'+price+'的现场'+item.event_type+'信息，快来看啊';
    var shareConfig = {
      title: title,
      imgUrl: item.avatar || window.location.origin+'/img/share_avatar.png'
    };
    $.ajax({
      type: "POST",
      url: "/news/share",
      dataType: "json",
      data: {
        url: location.href.split('#')[0]
      },
      success: function(res){
        wx.config(res);
        wx.ready(function(){
          wx.onMenuShareAppMessage(shareConfig);
          wx.onMenuShareTimeline(shareConfig);
        });
        wx.error(function(){
          console.log('jssdk config error');
        });
      },
      error: function(){
        console.log('获取jssdk配置失败');
      }
    });
  }

});
wml.run("you/page/information_detail");
