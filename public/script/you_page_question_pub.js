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
wml.define("you/app/top_back", function (require, exports) {
  var shareTmp = require('component/shareTmp')

  return function (link, name, avatar) {
    return shareTmp('top_back_tpl', {
      link: link
      , name: name
      , avatar: avatar
    })
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
wml.define("you/page/question_pub", function (require, exports) {
  var topBack = require('you/app/top_back')
  var gotoLink = require('you/app/link')

  var login = require('you/app/login')
  login.login(function (res) {
    $('.main_container').before(topBack(gotoLink.questionList(), '我问现场', res.data.avatar))
  });

  $('.price_wrap').on('click', '.price_item', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
    $('.price_custom').val('')
  }).on('input', '.price_custom', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
    var val = $(this).val()
    if (!val) return;
    val = parseInt(val);
    if (isNaN(val)) {
      val = '';
    }else if (val < 1){
      val = 1;
    } else if (val > 10000){
      val = 10000;
    }
    $(this).val(val);
  })
  $('.event_type_wrap').on('click', '.price_item', function (event) {
    $(this).addClass('act').siblings().removeClass('act')
  })
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
  $('#content').on('input', function(){
    onTextareaInput($(this), 500);
  });

  $('.pub_btn').on('click', function (event) {
    if (this.isSub) return;
    var _this = this
    var content = $('[name=content]').val(), price = 0;
    if (!content) {
      alert('请输入提问信息');
      $('[name=content]').focus();
      return;
    } else if (content.length > 500) {
      alert('问问文字限制500个！');
      $('[name=content]').focus();
      return false;
    }
    var $priceAct = $('.price_wrap').find('.act')
    price = $priceAct.is('[data-value]') ? $priceAct.attr('data-value') : $priceAct.val()
    if (price < 2) {
      alert('最低价格2元');
      return;
    }
    if ($('.event_type_wrap .price_item.act').length == 0) {
      alert('请选择标签');
      return;
    }

    var params = {
      type: 'question'
      , content: content
      , price: price
      , event_type: $('.event_type_wrap .price_item.act').attr('data-value')
    }
    $.ajax({
      url: '/news/add'
      , type: 'POST'
      , dataType: 'json'
      , data: params
      , beforeSend: function () {
        _this.isSub = true;
        _this.innerHTML = '提交中'
      }
      , success: function (res) {
        //alert(res.msg)
        if (res.code == 200) {
          location.href = gotoLink.questionDetail(res.data.id)
        }
      }
      , error: function () {
        alert('系统错误')
      }
      , complete: function () {
        _this.isSub = false;
        _this.innerHTML = '发布提问'
      }
    })
  });

});
wml.run("you/page/question_pub");
