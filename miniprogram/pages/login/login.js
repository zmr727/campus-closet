const { displayImage } = require('../../utils/request.js')

Page({
  data: {
    logo: '',
    hero: ''
  },

  onLoad() {
    Promise.all([
      displayImage('/images/login/logo-brand.png'),
      displayImage('/images/login/hero.png')
    ]).then(([logo, hero]) => this.setData({ logo, hero }))
  },

  /** 开启校园衣橱 → 登录/注册流程 */
  onStart() {
    wx.navigateTo({
      url: '/pages/auth/auth'
    })
  },

  /** 先逛逛 → 游客进入首页 */
  onBrowse() {
    try {
      wx.setStorageSync('isGuest', true)
      wx.removeStorageSync('userInfo')
    } catch (e) {}
    wx.switchTab({
      url: '/pages/index/index'
    })
  }
})
