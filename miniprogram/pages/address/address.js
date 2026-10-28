const { request } = require('../../utils/request.js')

function saveUserInfo(user) {
  try {
    wx.setStorageSync('userInfo', {
      id: user.id,
      username: user.username || '',
      phone: user.phone || '',
      openid: user.openid || '',
      login_type: user.login_type || '',
      nickname: user.nickname || '',
      avatar: user.avatar || '',
      student_id: user.student_id || '',
      address: user.address || '',
      nickName: user.nickname || '',
      studentId: user.student_id || ''
    })
  } catch (e) {}
}

Page({
  data: {
    address: ''
  },

  onShow() {
    const user = wx.getStorageSync('userInfo') || {}
    const userId = wx.getStorageSync('userId')
    this.setData({ address: user.address || '' })
    if (!userId) return
    request('/user/get_profile.php', { user_id: Number(userId) })
      .then((u) => {
        if (!u) return
        saveUserInfo(u)
        this.setData({ address: u.address || '' })
      })
      .catch(() => {})
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  },

  onInput(e) {
    this.setData({ address: e.detail.value })
  },

  onSubmit() {
    const address = (this.data.address || '').trim()
    if (!address) {
      wx.showToast({ title: '请填写交接地址', icon: 'none' })
      return
    }
    const userId = wx.getStorageSync('userId')
    if (!userId) {
      wx.showToast({ title: '请先登录', icon: 'none' })
      return
    }
    wx.showLoading({ title: '保存中' })
    request('/user/update_address.php', {
      user_id: Number(userId),
      address
    })
      .then((user) => {
        wx.hideLoading()
        saveUserInfo(user)
        wx.showToast({ title: '已保存', icon: 'success' })
        setTimeout(() => this.onBack(), 400)
      })
      .catch((err) => {
        wx.hideLoading()
        wx.showToast({ title: err.message || '保存失败', icon: 'none' })
      })
  }
})
