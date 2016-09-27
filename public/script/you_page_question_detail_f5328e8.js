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
      , "async": false
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
              cbk && cbk(res.err_msg != "get_brand_wcpay_request：ok", res)
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
wml.define("you/page/question_detail", function (require, exports) {
  var login = require('you/app/login')
  login.login();
  var urlHandler = require('component/urlHandler')
  var shareTmp = require('component/shareTmp')
  var gotoLink = require('you/app/link')
  var wxpay = require('you/app/wxpay')

  var detail_id = urlHandler.getParam(location.href, 'id')
    , detail_type = 'question';

  var limits = {
    content: 300,
    qrcode_content: 2000
  };
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
  function forceReload() {
    window.location.href = location.origin + location.pathname + '?id=' + detail_id + '&_=' + new Date().getTime();
  }
  

  $('.main_container').on('click', '.price_wrap .price_item', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
    $('.price_custom').val('')
  }).on('input', '.price_custom', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
  }).on('click', '.reply_btn', function (event) {
    $('.question_btn_group').hide()
    $('.reply_wrap').show()
    $('[name=content]').on('input', function(){
      onTextareaInput($(this), limits.content);
    });
    $('[name=qrcode_content]').on('input', function(){
      onTextareaInput($(this), limits.qrcode_content);
    });
  }).on('click', '.cancel_btn', function (event) {
    $('.question_btn_group').show()
    $('.reply_wrap').hide()
  }).on('click', '.ssz-container [data-value]', function (event) {
    // 投票
    // 购买后才能进行评价，否则点击无效
    if ($(this).parent().prev('.open_btn').length > 0) {
      return false;
    }
    if ($(this).parent().attr('data-me') == 1 || $(this).parent().attr('data-vote-value')) {
      console.log('不能给自己投票或已经有过投票了');
      return false;
    }
    if ($(this).attr('data-value') == 1 && $('#cannot-click').length > 0) {
      alert('此为很赞信息，不能进行此种评价!');
      return;
    }
    if (!confirm('确认提交评价吗？')) {
      return false;
    }
    var __id = $(this).parent().attr('data-id');
    if (this.isDoing) return;
    var _this = this;
    event.preventDefault();
    var val = $(this).attr('data-value')
    $.ajax({
      url: '/news/vote',
      type: 'post',
      dataType: 'json',
      data: {
        q_id: detail_id,
        id: __id
        , type: 'answer'
        , vote: val
      },
      beforeSend: function () {
        _this.isDoing = true;
      },
      success: function (res) {
        if (res.code == 123) {
          return;
        }
        alert(res.msg);
        if ( res.code == 102) {
          return;
        }
        if (res.code == 200) {
          $(_this).parent().children('div').eq(4-val-1).find('img').attr('src', './img/0' + (4 - val) + '.png');
          $ele = $('#ssz-data-' + (4 - val));
          $ele.html(parseInt($ele.html()) + 1);
          $(_this).parent().attr('data-vote-value', val);
        }
      },
      complete: function () {
        _this.isDoing = false;
      }
    });
  }).on('click', '.see_btn', function (event) {
    $(this).addClass('visited');
    var id = $(this).parents('.answer_item').attr('data-aid')
    var price = $('[name=q_price]').val() | 0
    wxpay.callPay('answer', id, price, function (err, res) {
      forceReload();
      return;
    })
  });

// $('.main_container').on('click', '.hide_con_btn', function(event) {
// 	var item = $('.hide_con_wrap').toggleClass('act')
// 	if(item.hasClass('act')){
// 		$('#qrcode_content').focus()
// 	}
// });

  var uploading = false;

  // 图片上传最大数量限制
  function getFileInfo(file) {
    var type = 'image';
    if (/\.(mp4|avi|mpg|mpe|mpge|mov|wmv|rm|rmvb|ogg|qt|amr)$/.test(file.name)) {
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
    //duan
    var type = $this.attr("data-value");
    if("ssz_news"==type){
      if (parseInt($this.data('uploadnum')) >= parseInt($this.data('maxnum'))) {
        alert('最多上传' + $this.data('maxnum') + '个文件!');
        ret = false;
      }
    }
    if (fileInfo.type == 'image') {
      if (fileInfo.size > 80 * 1024 * 1024) { // 图片大小不能超过2m
        alert('上传图片大小最大不能超过80M!');
        ret = false;
      }
    }
    if (fileInfo.type == 'video') {
      if (fileInfo.size > 300 * 1024 * 1024) { // 图片大小不能超过20m
        alert('上传视频大小最大不能超过300M!');
        ret = false;
      }
    }
    return ret;
  }

  $('.main_container').on('change', 'input[type=file]', function (event) {
    var _this = this
    var file = this.files[0]
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

            $item.attr('type', 'vedio');
            $item.html('<a href="'+res.data.url+'" class="col-12 col img_item">' +
                '<img alt="" src="' + res.data.imgurl + '"></a>');
          } else {

            $item.attr('type', res.data.file_type);
            $item.html('<a href="'+res.data.url+'" class="col-12 col img_item">' +
                '<img alt="" src="' + res.data.imgurl + '"></a>');
          }
          $input.data('uploadnum', parseInt($input.data('uploadnum')) + 1);
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
        $item.remove()
        if (!uploading) {
          var type = $item.attr('type')
          if("hide_file_input"==$input.attr("id")){
            $input.attr(type+'Num', parseInt($input.attr(type+'Num')) - 1);
          }
          $input.data('uploadnum', parseInt($input.data('uploadnum')) - 1);
        }
        ajax && ajax.abort()
      }
    });
  });


  $('.main_container').on('click', '.pub_btn', function (event) {
    if (this.isSub) return;
    if (uploading) {
      alert('正在上传文件，请稍后发布')
      return;
    }
    if (audioManager.isVoiceUploading()) {
      alert('语音正在上传中, 请稍后发布');
      return;
    }
    var _this = this
    var content = $('[name=content]').val()
      , content_img = []
      , thumb = []
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
    var voice_data = audioManager.getVoiceData();
    if (!qrcode_content && voice_data.length == 0) {
      alert('请输入现场信息支付部分文字');
      $('[name=qrcode_content]').focus();
      return;
    }
    if (qrcode_content && qrcode_content.length > 2000) {
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
    var params = {
      type: detail_type
      , q_id: detail_id
      , content: content
      , qrcode_content: qrcode_content
      , confirm_status: $('[name=confirm_status]:checked').length
      , content_img: content_img.join(',')
      , thumb: thumb.join(',')
      , thumb_des: $('[name=thumb_des]').val()
      , file_number :filenumber
      , voice_data : voice_data
    }
    $.ajax({
      url: '/news/answer'
      , type: 'POST'
      , dataType: 'json'
      , data: params
      , beforeSend: function () {
        _this.isSub = true;
        _this.innerHTML = '正在提交'
      }
      , success: function (res) {
        //alert(res.msg)
        if (res.code == 200) {
          forceReload();
        }
      }
      , error: function () {
        alert('系统错误')
      }
      , complete: function () {
        _this.isSub = false;
        _this.innerHTML = '发布回答'
      }
    })
  });

  //add by duan
  $('#ssz-description').on('input', function (event) {
    var _l = $(this).val().length;
    if (_l > 500) {
      $(this).val($(this).val().substr(0, 500));
      $('#ssz-limit-4').html("写的也太多了吧，这里请不要超过500字！");
    }
  });

  function isImage(fileName) {
    var reg = /\.(gif|jpg|jpeg|png|bmp)$/;
    return reg.test(fileName.toLowerCase());
  }
  var imageList = [];
  function handleQuestionData(obj) {
    var fields = ['thumb', 'content_img'];
    $.each(fields, function(index, item){
      $.each(obj.answer, function(k, v){
        obj.answer[k][item] = $.map(obj.answer[k][item], function(val, key){
          var $a = $(val);
          var href = $a.attr('href');
          if (isImage(href)) {
            imageList[k] = imageList[k] || [];
            imageList[k].push(href);
            $a.data('href', href);
            $a.data('answer', k);
            $a.data('preview-image', 1);
            $a.attr('href', 'javascript:void(0);');
            return $a.prop('outerHTML');
          } else {
            return val;
          }
        });
        obj.answer[k].audio_file_count = obj.answer[k].voice_data ? obj.answer[k].voice_data.length : 0;
      });
    });
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
        var item = res.data;
        handleQuestionData(item);
        var html = shareTmp('detail_tpl', {
          item: item
        })
        $('.main_container').html(html).before(
          shareTmp('top_back_tpl', {
            link: gotoLink.questionList()
            , name: item.nickname
            , avatar: item.avatar
            , price: item.price
            , has_delete_btn: item.has_delete_btn
            , id: item.id
          })
        );
        $('.img_item.hide_files_info').height(($('.img_item.hide_files_info').width()-12) * 638 / 365 + 13);
        $('.main_container a[data-preview-image]').click(function(){
          var index = parseInt($(this).data('answer'));
          wx.previewImage({
              current: $(this).data('href'),
              urls: imageList[index],
          });
        });
        $('.ssz-auth').click(function(event) {
          event.preventDefault();
          var id = $(this).data('id');
          if (confirm('确定申请小丫鉴定吗？')) {
            $.post('/news/update', {id: id, type: 'answer', 'confirm_status': 1}, function(data) {
              if (data.code == 200) {
                alert('申请鉴定成功');
                forceReload();
              } else {
                alert('申请鉴定失败');
                console.log(data);
              }
            });
          }
        });
        $('#for_pub_btn').on('click', function () {
          $(this).hide();
          $('#shishi-auth').show();
        })

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
            content_img.push($(ele).find('img').attr('src'))
          });
          var des = $('#ssz-description').val();
          if (des.length > 300) {
            alert('描述不能超过300字');
            return;
          }
          if (des.length == 0) {
            alert("请填写描述部分。");
            return;
          }
          var params = {
            vote_id: $('#for_pub_btn').attr('data-id')
            , type: 'answer'
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
                // location.href = gotoLink.inforDetail(res.data.id)
                // alert(res.msg);
              }
            }
            , error: function () {
              alert('系统错误');
            }
            , complete: function () {
              _this.isSub = false;
              _this.innerHTML = '申请信息失实已提交，请等待审核...';
              $('#shishi-container').hide();
              $(_this).css('background-color', '#f8f7f7');
              $(_this).css('color', 'black');
            }
          })
        });
        // var $eles = $('.other_img_wrap.clearfix a');
        // $eles.height($eles.width());
        // $eles.each(function() {
        //   // if ($(this).children('img').height() < $eles.height()) $(this).children('img').height($eles.height());
        //   $(this).children('img').css('clip', 'rect(0,0,' + $eles.width() + 'px,' + $eles.width() + 'px)');
        // });
        // $eles = $('.hide_img_wrap.clearfix a');
        // $eles.height($eles.width());
        // $eles.each(function() {
        //   // if ($(this).children('img').height() < $eles.height()) $(this).children('img').height($eles.height());
        //   $(this).children('img').css('clip', 'rect(0,0,' + $eles.width() + 'px,' + $eles.width() + 'px)');
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
          $('body').addClass('modal-open');
        });
        $('#intro_page .close').click(function(){
          $('#intro_page').hide();
          $('body').removeClass('modal-open');
        });

        var activeAudioCell = null;
        $('.audio_cell .symbol').on('PlayStart', function(){
          if (this.timer) {
            clearInterval(this.timer);
          }
          var $symbol = $(this);
          this.timer = setInterval(function(){
            var text = $symbol.text();
            if (text.length < 3) {
              $symbol.text(text+'>');
            } else {
              $symbol.text('>');
            }
          }, 400);
        }).on('PlayStop', function(){
          if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
          }
          $(this).text('>>>');
        });

        $('.audio_cell audio').on('play', function(){
          $(this).siblings('.symbol').trigger('PlayStart');
        }).on('pause ended', function(){
          $(this).siblings('.symbol').trigger('PlayStop');
        });

        $('.audio_cell').click(function() {
          var audioDom;
          if (activeAudioCell && activeAudioCell != this ) {
            audioDom = $(activeAudioCell).find('audio')[0];
            audioDom.pause();
            audioDom.currentTime = 0;
          }
          activeAudioCell = this;
          audioDom = $(this).find('audio')[0];
          if (audioDom.paused) {
            audioDom.play();
          } else {
            audioDom.pause();
          }
        });
        
        wxShareConfig(item);
        audioManager = {
          limitCount: 3,
          list: [],
          current: null,
          $els: {
            addButton: $('#audioAddButton'),
            recordWrap: $('#audioRecordWrap'),
            startWidget: $('#audioRecordWrap .start_widget'),
            recordWidget: $('#audioRecordWrap .recording_widget'),
            startRecordButton: $('#audioRecordWrap .start_widget .start'),
            sendButton: $('#audioRecordWrap .recording_widget .send'),
            cancelButton: $('#audioRecordWrap .cancel'),
            durationSpan: $('#audioRecordWrap .recording_widget .duration'),
            audioListWrap: $('#audioListWrap'),
            audioAddWrap: $('#audioAddWrap'),
            ellipsisSpan: $('#audioRecordWrap .recording_widget .ellipsis'),
          },
          timer: null,
          showRecordWrap: function() {
            var $els = this.$els;
            $els.recordWrap.show();
            $els.startWidget.slideDown();
            $els.recordWidget.hide();
          },
          hideRecordWrap: function() {
            var $els = this.$els;
            $els.startWidget.hide();
            $els.recordWidget.hide();
            $els.recordWrap.hide();
            this.removeTimer();
          },
          removeTimer: function() {
            if (!this.timer) return;
            clearInterval(this.timer);
            this.timer = null;
          },
          showRecordWidget: function(){
            var $els = this.$els;
            var _this = this;
            $els.ellipsisSpan.text('. . .');
            $els.durationSpan.text('0');
            $els.startWidget.hide();
            $els.recordWidget.show();
            this.removeTimer();
            var duration = 0;
            this.timer = setInterval(function(){
              duration++;
              if (duration > 60) {
                $els.ellipsisSpan.text('. . .');
                $els.durationSpan.text('60');
                return _this.removeTimer();
              }
              $els.durationSpan.text(duration);
              var ellipsisText = $els.ellipsisSpan.text();
              if (ellipsisText.length < 5) {
                $els.ellipsisSpan.text(ellipsisText + ' .');
              } else {
                $els.ellipsisSpan.text('.');
              }
            }, 1000);
          },
          isVoiceUploading: function() {
            for (var i in this.list) {
              var audio = this.list[i];
              if (!audio.deleted && audio.isUploading) {
                return true;
              }
            }
            return false;
          },
          getVoiceData: function() {
            var arr = [];
            for (var i in this.list) {
              var audio = this.list[i];
              if (!audio.deleted && audio.uploadSuccess && audio.mediaUrl) {
                arr.push({
                  media_url: audio.mediaUrl,
                  media_duration: audio.duration
                });
              }
            }
            return arr;
          },
          addAudioCell: function(audio) {
            if (!audio) return;
            var _this = this;
            this.list.push(audio);
            var index = this.list.length-1;
            var $img = $('<img/>').attr('src', audio.getImageLink());
            var $duration = $('<span/>').addClass('duration').text(audio.duration + '"');
            var $symbol = $('<span/>').addClass('symbol').text('>>>');
            var $delButton = $('<span/>').addClass('button').text('取消');
            var $cell = $('<div/>').addClass('audio_cell').append($img).append($duration).append($symbol).append($delButton);
            this.$els.audioListWrap.append($cell);
            audio.index = index;
            audio.$el = $cell;
            audio.timer = null;
            $cell.on('PlayStart', function(){
              if (audio.timer) {
                clearInterval(audio.timer);
              }
              audio.timer = setInterval(function(){
                var text = $symbol.text();
                if (text.length < 3) {
                  $symbol.text(text+'>');
                } else {
                  $symbol.text('>');
                }
              }, 400);
            }).on('PlayStop', function(){
              if (audio.timer) {
                clearInterval(audio.timer);
                audio.timer = null;
              }
              $symbol.text('>>>');
            });
            $cell.click(function() {
              _this.playOrPauseAudio(audio);
            });
            $delButton.click(function(event){
              event.stopPropagation();
              if (confirm('确定删除?')) {
                _this.removeAudioCell(audio);
              }
            });
            this.onAudioCellChange();
          },
          playOrPauseAudio: function(audio){
            for (var i in this.list) {
              var _audio = this.list[i];
              if (audio != _audio) {
                _audio.stopVoice();
              }
            }
            audio.playOrPause();
          },
          removeAudioCell: function(audio) {
            if (!audio) return;
            audio.deleted = true;
            if (audio.timer) {
              clearInterval(audio.timer);
              audio.timer = null;
            }
            if (audio.localId && !audio.paused) {
              audio.paused = true;
              wx.stopVoice({
                localId: audio.localId,
              });
            }
            if (audio.$el) {
              audio.$el.remove();
              this.onAudioCellChange();
            }
          },
          stopAllVoice: function() {
            for (var i in this.list) {
              var audio = this.list[i];
              if (audio.localId && !audio.paused) {
                audio.paused = true;
                wx.stopVoice({
                  localId: audio.localId,
                });
              }
            }
          },
          getAudioCellCount: function() {
            var total = 0;
            for (var i in this.list) {
              if (!this.list[i].deleted) {
                total ++;
              }
            }
            return total;
          },
          onAudioCellChange: function() {
            var total = this.getAudioCellCount();
            var $els = this.$els;
            var $p = $els.audioAddWrap.children('p');
            if (total <= 0) {
              $els.addButton.detach().appendTo($els.audioAddWrap.siblings('h2'));
              $p.text('');
            } else if (total >= this.limitCount) {
              $els.addButton.detach().prependTo($els.audioAddWrap);
              $p.text('');
            } else {
              $els.addButton.detach().prependTo($els.audioAddWrap);
              $p.text('再说一段');
            }
          },
          init: function() {
            var $els = this.$els;
            var _this = this;
            $els.addButton.click(function() {
              var $p = $els.audioAddWrap.children('p');
              if (_this.getAudioCellCount() < _this.limitCount) {
                _this.showRecordWrap();
                $p.show();
              } else {
                $p.text('最多录'+_this.limitCount+'段语音');
                $p.show();
                $p.fadeOut(2000);
              }
            });
            $els.startRecordButton.click(function() {
              _this.showRecordWidget();
              _this.current = new AudioItem($els);
              _this.current.startRecord();
            });
            $els.cancelButton.click(function() {
              _this.hideRecordWrap();
              var audio = _this.current;
              if ( audio) {
                audio.startTime = null;
                if (audio.state == audioStates.isRecording) {
                  wx.stopRecord();
                }
              }
              _this.current = null;
            });
            $els.sendButton.click(function() {
              var audio = _this.current;
              if (!audio) return;
              _this.hideRecordWrap();
              _this.addAudioCell(audio);
              if (audio.localId) {
                audio.uploadRecord();
              } else {
                audio.stopAndUploadRecord();
              }
            });
          },
        };
        audioManager.init();
        window.onbeforeunload = function(){
          audioManager.stopAllVoice();
        };
      }else if (res.code == 100) {
        loadErr('此信息已被删除')
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
  var jsApiList = [
    'onMenuShareTimeline',
    'onMenuShareAppMessage',
    'onMenuShareQQ',
    'onMenuShareWeibo',
    'onMenuShareQZone',
    'startRecord',
    'stopRecord',
    'onVoiceRecordEnd',
    'playVoice',
    'pauseVoice',
    'stopVoice',
    'onVoicePlayEnd',
    'uploadVoice',
    'downloadVoice',
    'chooseImage',
    'previewImage',
    'uploadImage',
    'downloadImage',
  ];
  function wxShareConfig(item) {
    var shareConfig = {
      title: item.nickname+'打赏'+item.price+'元提问了现场'+item.event_type+'问题，有谁到场过？',
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
        res.jsApiList = jsApiList;
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
  var audioManager = {};
  var audioStates = {
    beforeRecord: 'beforeRecord',
    isRecording: 'isRecording',
    afterRecorded: 'afterRecorded',
    isStopRecording: 'isStopRecording',
  };
  function getSeconds(startDate, endDate) {
    return Math.round((endDate.getTime() - startDate.getTime())/1000);
  }
  function AudioItem($els) {
    this.state= audioStates.beforeRecord;
    this.startTime= null;
    this.endTime= null;
    this.localId= null;
    this.duration= 0;
    this.mediaUrl = null;
    this.serverId = null;
    this.isUploading = false;
    this.uploadSuccess = false;
    this.index = -1;
    this.paused = true;
    this.deleted = false;
    this.$el = null;
    this.$els = $els;
    this.timer = null;
  }
  AudioItem.prototype = {
    playOrPause: function() {
      if (!this.localId) return;
      var _this = this;
      if (this.paused) {
        this.onPlayStart();
        wx.playVoice({
          localId: this.localId,
          complete: function(){}
        });
        wx.onVoicePlayEnd({
          complete: function(){
            _this.onPlayStop();
          },
        });
      } else {
        this.onPlayStop();
        wx.pauseVoice({
            localId: this.localId,
            complete: function() {}
        });
      }
    },
    onPlayStart: function(){
      this.paused = false;
      if (this.$el) {
        this.$el.trigger('PlayStart');
      }
    },
    onPlayStop: function(){
      this.paused = true;
      if (this.$el) {
        this.$el.trigger('PlayStop');
      }
    },
    stopVoice: function() {
      if (this.localId) {
        wx.stopVoice({
          localId: this.localId,
        });
      }
      this.onPlayStop();
    },
    getImageLink: function() {
      var baseUrl = 'img/audio/';
      if (this.duration <= 20) {
        return baseUrl + 'audio2_1-20.jpg';
      }
      if (this.duration <= 30) {
        return baseUrl + 'audio2_21-30.jpg';
      }
      if (this.duration <= 40) {
        return baseUrl + 'audio2_31-40.jpg';
      }
      if (this.duration <= 50) {
        return baseUrl + 'audio2_41-50.jpg';
      }
      return baseUrl + 'audio2_51-60.jpg';
    },
    startRecord: function() {
      var _this = this;
      wx.startRecord();
      wx.onVoiceRecordEnd({
          complete: function (res) {
            _this.localId = res.localId;
            _this.endTime = new Date();
            _this.state = audioStates.afterRecorded;
            _this.$els.sendButton.trigger('click');
          }
      });
      this.startTime = new Date();
      this.state = audioStates.isRecording;
      this.countDuration();
    },
    countDuration: function() {
      if (!this.startTime) {
        this.duration = 0;
        return;
      }
      var endTime = this.endTime || new Date();
      this.duration = Math.round(getSeconds(this.startTime, endTime));
      if (this.duration >= 60) {
        this.duration = 60;
      }
      if (!this.endTime) {
        setTimeout(this.countDuration.bind(this), 1000);
      }
    },
    stopAndUploadRecord: function() {
      var _this = this;
      this.state = audioStates.isStopRecording;
      this.isUploading = true;
      wx.stopRecord({
          success: function (res) {
              _this.localId = res.localId;
              _this.state = audioStates.afterRecorded;
              _this.endTime = new Date();
              _this.uploadRecord();
          },
      });
    },
    uploadRecord: function() {
      if (!this.localId) return;
      this.isUploading = true;
      var _this = this;
      wx.uploadVoice({
          localId: this.localId,
          isShowProgressTips: 1,
          success: function (res) {
            _this.serverId = res.serverId;
            $.ajax({
              type: "POST",
              url: "/news/wechatmedia",
              dataType: "json",
              data: {
                media_id: _this.serverId
              },
              success: function(res) {
                if (res.code == 200) {
                  _this.uploadSuccess = true;
                  _this.isUploading = false;
                  _this.mediaUrl = res.media_url;
                } else {
                  _this.onUploadError();
                }
              },
              error: function() {
                _this.onUploadError();
              },
            });
          },
          error: function() {
            _this.onUploadError();
          },
      });
    },
    onUploadError: function() {
      this.isUploading = false;
      this.uploadSuccess = false;
      audioManager.removeAudioCell(this);
      alert('抱歉，语音上传失败，请再说一次吧');
    },
  };
  
});
wml.run("you/page/question_detail");
