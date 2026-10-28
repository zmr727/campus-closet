const { request } = require('../../utils/request.js')

Page({
  data: { studentId: '', name: '' },

  onBack() {
    wx.switchTab({ url: '/pages/index/index' })
  },

  onId(e) { this.setData({ studentId: e.detail.value }) },
  onName(e) { this.setData({ name: e.detail.value }) },

  onSubmit() {
    const { studentId, name } = this.data
    if (!studentId) {
      wx.showToast({ title: '请填写学号', icon: 'none' })
      return
    }
    if (!name || !String(name).trim()) {
      wx.showToast({ title: '请填写真实姓名', icon: 'none' })
      return
    }
    const userId = wx.getStorageSync('userId')
    if (!userId) {
      wx.showToast({ title: '请先登录', icon: 'none' })
      return
    }

    wx.showLoading({ title: '提交中' })
    request('/user/student_auth.php', {
      user_id: userId,
      student_id: studentId,
      real_name: String(name).trim()
    }).then((user) => {
      wx.hideLoading()
      try {
        wx.setStorageSync('userInfo', {
          id: user.id,
          username: user.username,
          phone: user.phone || '',
          openid: user.openid || '',
          login_type: user.login_type || '',
          nickname: user.nickname,
          real_name: user.real_name || '',
          avatar: user.avatar || '',
          student_id: user.student_id,
          address: user.address || '',
          campus: user.campus || '',
          nickName: user.nickname,
          studentId: user.student_id
        })
      } catch (e) {}
      wx.showToast({ title: '认证成功', icon: 'success' })
      setTimeout(() => {
        wx.switchTab({ url: '/pages/index/index' })
      }, 400)
    }).catch((err) => {
      wx.hideLoading()
      wx.showToast({ title: err.message || '认证失败', icon: 'none' })
    })
  }
})
