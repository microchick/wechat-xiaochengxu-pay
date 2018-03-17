// list.js
var api = require('../../api.js');
var app = getApp();
var is_loading_more = false;
var is_no_more = false;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    cat_id: "",
    success: true,
    successData: {},
    show: true,
    order:[],
    is_pay:2,//未支付
  },

  onLoad: function (options) {
    console.log(options.id);
    var that = this;
    app.request({
      url: api.default.jack_find_order_api,
      data: { id: options.id },
      success: function (res) {
        if (res.status == 1) {
          console.log(res.data);
          that.setData({
            order: res.data,
           
          })
        }
      },
      complete: function () {
        // wx.stopPullDownRefresh();
      }
    });
    that.updata_user_info();
  },
 
  /**
   * 加载初始数据 加载用户信息
   * */
  loadData: function (options) {
    var page = this;
    console.log('loaddata');
     
  },
  /*更新用户信息...*/
  updata_user_info: function (options) {
    var page = this;
    var user_info = wx.getStorageSync("user_info");

    if (user_info.id) {
      console.log(user_info);
      wx.showLoading({
        title: "正在更新",
        mask: true,
      });
      app.request({
        url: api.default.jack_find_user_info_api,
        method: "post",
        data: {
          id: user_info.id,
        },
        success: function (res) {
          wx.hideLoading();
          // console.log(code)
          if (res.status == 1) {

            wx.setStorageSync("user_info", {
              user_nicename: res.data.user_nicename,
              id: res.data.id,
              user_login: res.data.user_login,
              user_email: res.data.user_email,
              avatar: res.data.avatar,
              sex: res.data.sex,
              birthday: res.data.birthday,
              user_type: res.data.user_type,
              mobile: res.data.mobile,
              membershipnumber: res.data.membershipnumber,
              coin: res.data.coin,
              openid: res.data.openid,
            });
            console.log(res.data);
            page.setData({
              user_info: res.data,
              is_login: 1,//已登录
            });

          } else {
            wx.showModal({
              title: "提示",
              content: res.data,
              showCancel: false,
              confirm: function (e) {
                if (e.confirm) {
                  wx.navigateBack();
                }
              }
            });
          }
        }
      });

      
    } else {
      page.setData({
        is_login: 2,//未登录
      });
    }


  },
 
  
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  }
  ,
 
  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  }
  ,

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  }
  ,

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },


  bindChange: function (e) {

    var that = this;
    that.setData({ currentTab: e.detail.current });

  },
  swichNav: function (e) {

    var that = this;

    if (this.data.currentTab === e.target.dataset.current) {
      return false;
    } else {
      that.setData({
        currentTab: e.target.dataset.current
      })
    }
  },
  get_list: function () {
    var t = this;
    e.get("order/pay", t.data.options, function (i) {
      if (50018 == i.error)
        return void wx.navigateTo({
          url: "/pages/order/details/index?id=" + t.data.options.id
        });
      !i.wechat.success && "0.00" != i.order.price && i.wechat.payinfo && e.alert(i.wechat.payinfo.message + "\n不能使用微信支付!"),
        t.setData({
          list: i,
          show: true
        })
    })
  },
  pay: function (t) {
    var i = t.currentTarget.dataset.type; //支付类型
    var order_id = t.currentTarget.dataset.order_id;//订单id
    var o =this;
    if(i=='wechat'){
    //微信支付的
      o.wechatpay(i, order_id);
    }else if(i=="credit"){

      wx.showModal({
        title: '提示',
        content: '确认要支付吗?',
        success: function (res) {
          if (res.confirm) {
            o.complete(i, order_id);
          } else if (res.cancel) {
            console.log('用户点击取消')
          }
        }
      });
  
    }

  
  },
  complete: function (t, order_id) {
    var o = this;
    var user_info = wx.getStorageSync('user_info');
    wx.showLoading({
      title: "正在提交数据",
      mask: true,
    });
    app.request({
      url: api.default.jack_pay_api,
      method: "post",
      data: {
        order_id: order_id,
        user_id: user_info.id,
        type: t,
      },
      success: function (res) {
        wx.hideLoading();
        if (res.status == 1) {
         // console.log(res.data);
          o.setData({
            is_pay: 1,//支付成功
          });
          wx.showModal({
            title: "提示",
            content: res.data,
            showCancel: false,
            success: function (resa) {
              wx.redirectTo({
                url: "/pages/order/order?status=1",
                fail: function () {
                },
              });

            }
          });
          
 
        } else {
          wx.showModal({
            title: "提示",
            content: res.data,
            showCancel: false,
            confirm: function (e) {
              if (e.confirm) {
                wx.navigateBack();
              }
            }
          });
        }
      }
    });
 
  },
  wechatpay: function (t, order_id) {

    var o = this;
    var user_info = wx.getStorageSync('user_info');
    //console.log( user_info);
    wx.showLoading({
      title: "正在提交数据",
      mask: true,
    });
  
    
    app.request({
      url: api.default.jack_pay_wechat_api,
      method: "post",
      data: {
        order_id: order_id,
        user_id: user_info.id,
        openid: user_info.openid,
        type: t,
      },
      success: function (res) {
        wx.hideLoading();
        console.log(res)
        if (res.status == 1) {
          wx.requestPayment({
            'timeStamp': res.data.timeStamp,
            'nonceStr': res.data.nonceStr,
            'package': res.data.package,
            'signType': 'MD5',
            'paySign': res.data.paySign,
            'success': function (res) {
              console.log('success');
              wx.showToast({
                title: '支付成功',
                icon: 'success',
                duration: 3000
              });
              wx.redirectTo({
                url: "/pages/order/order?status=1",
                fail: function () {
                },
              });

            },
            'fail': function (res) {

              console.log(res);
              console.log('fail');
            },
            'complete': function (res) {
              console.log('complete');
            }
          });
    /*      wx.showModal({
            title: "提示",
            content: res.data,
            showCancel: false,
            success: function (resa) {
              
            }
          });
    */

        } else {
          wx.showModal({
            title: "提示",
            content: res.data,
            showCancel: false,
            confirm: function (e) {
              if (e.confirm) {
                wx.navigateBack();
              }
            }
          });
        }
      }
    });
    
  },
  shop: function (t) {
    0 == e.pdata(t).id ? this.setData({
      shop: 1
    }) : this.setData({
      shop: 0
    })
  },
  phone: function (t) {
    e.phone(t)
  },

  
});
