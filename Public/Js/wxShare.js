/*******************************
 * Author:Mr.Think
 * Description:微信分享通用代码
 * 使用方法：_WXShare('分享显示的LOGO','LOGO宽度','LOGO高度','分享标题','分享描述','分享链接','微信APPID(一般不用填)');
 *******************************/

    //��ʼ������
    
    var width=320;
    var height=195;

    
    var appid='';
	var score='';
	
	//
	var title  = window.shareData.tTitle;
	var desc= window.shareData.tContent;
	var img111= window.shareData.imgUrl;
	var url= window.shareData.timeLineLink;
	var cfg_wx_share_redirect = window.shareData.cfg_wx_share_redirect;
	
     //微信内置方法
    function _ShareFriend() {
		
        WeixinJSBridge.invoke('sendAppMessage',{
              'appid': appid,
              'img_url': img111,
              'img_width': width,
              'img_height': height,
              'link': url,
              'desc': desc,
              'title': title
              }, function (res) {
            	  
            	  if(res.err_msg=="send_app_msg:ok") {
  					//alert('分享朋友成功' + url);
  					window.location.href = cfg_wx_share_redirect;
  				  }
				
				_report('send_msg', res.err_msg);
				})
    }
	
    function _ShareTL() {	
		
        WeixinJSBridge.invoke('shareTimeline',{
              'img_url': img111,
              'img_width': width,
              'img_height': height,
              'link': url,
              'desc': desc,
              'title': desc
              }, function(res) {
			  //分享给好友成功
            	  if(res.err_msg=="share_timeline:ok"){
            		  // alert('分享到朋友圈成功' + url);
            		  window.location.href = cfg_wx_share_redirect;
  				  }
			  
              _report('timeline', res.err_msg);
              });
    }
	
	
    function _ShareWB() {
		
        WeixinJSBridge.invoke('shareWeibo',{
              'content': desc,
              'url': url,
              }, function(res) {
				//分享微博成功
            	  //alert('分享微博成功' + url);
            	  window.location.href = cfg_wx_share_redirect;
				
              _report('weibo', res.err_msg);
              });
    }
    // 当微信内置浏览器初始化后会触发WeixinJSBridgeReady事件。
    document.addEventListener('WeixinJSBridgeReady', function onBridgeReady() {
            // 发送给好友
            WeixinJSBridge.on('menu:share:appmessage', function(argv){
                _ShareFriend();
          });

            // 分享到朋友圈
            WeixinJSBridge.on('menu:share:timeline', function(argv){
                _ShareTL();
                });

            // 分享到微博
            WeixinJSBridge.on('menu:share:weibo', function(argv){
                _ShareWB();
           });
    }, false);
