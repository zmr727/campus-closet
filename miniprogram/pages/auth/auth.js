const { afterLogin } = require('../../utils/auth.js')
const { request, displayImage } = require('../../utils/request.js')

Page({
  data: {
    agreed: false,
    logo: ''
  },

  onLoad() {
    displayImage('/images/login/logo-brand.png').then((logo) => this.setData({ logo }))
  },

  onBack() {
    wx.navigateBack({ delta: 1 })
  },

  toggleAgree() {
    this.setData({ agreed: !this.data.agreed })
  },

  ensureAgree() {
    if (!this.data.agreed) {
      wx.showToast({ title: '请先勾选同意协议', icon: 'none' })
      return false
    }
    return true
  },

  onWxLogin() {
    if (!this.ensureAgree()) return
    wx.showLoading({ title: '登录中' })
    wx.login({
      success: (res) => {
        request('/user/login_wechat.php', {
          code: res.code || '',
          nickname: '微信用户'
        })
          .then((user) => {
            wx.hideLoading()
            afterLogin(user)
          })
          .catch((err) => {
            wx.hideLoading()
            wx.showToast({ title: err.message || '登录失败', icon: 'none' })
          })
      },
      fail: () => {
        request('/user/login_wechat.php', { code: '', nickname: '微信用户' })
          .then((user) => {
            wx.hideLoading()
            afterLogin(user)
          })
          .catch((err) => {
            wx.hideLoading()
            wx.showToast({ title: err.message || '登录失败', icon: 'none' })
          })
      }
    })
  },

  onPhone() {
    if (!this.ensureAgree()) return
    wx.navigateTo({ url: '/pages/auth/phone' })
  },

  onAgreement() {
    wx.showModal({
      title: '用户协议',
      content: '本小程序为校园衣橱课程演示项目，登录即表示同意平台使用规范。',
      showCancel: false
    })
  },

  onPrivacy() {
    wx.showModal({
      title: '隐私政策',
      content: '我们仅收集登录与学号认证所需信息，用于校园租赁演示，不会对外出售。',
      showCancel: false
    })
  }
})
