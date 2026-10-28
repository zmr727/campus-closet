/** 登录成功：写入本地，并引导学号认证 */
// 登录成功后，把后端返回的 user 整理成一份本地要用的资料：
function afterLogin(user) {
  const info = {
    id: user.id,
    username: user.username || '',
    phone: user.phone || '',
    openid: user.openid || '',
    login_type: user.login_type || '',
    nickname: user.nickname || user.nickName || '用户',
    real_name: user.real_name || '',
    avatar: user.avatar || '',
    student_id: user.student_id || user.studentId || '',
    address: user.address || '',
    campus: user.campus || '',
    nickName: user.nickname || user.nickName || '用户',
    studentId: user.student_id || user.studentId || ''
  }
  // 登录后写入本地缓存的部分
  try {
    wx.setStorageSync('isGuest', false)
    wx.setStorageSync('userInfo', info)
    wx.setStorageSync('userId', info.id)
    if (info.campus) {
      wx.setStorageSync('campusName', info.campus)
    }
  } catch (e) {}
  // 已经认证过学号的跳转，进入首页
  if (info.student_id) {
    setTimeout(() => {
      wx.switchTab({ url: '/pages/index/index' })
    }, 50)
    return
  }
  // 未认证时弹出的对话框，让去认证
  wx.showModal({
    title: '完善学号认证',
    content: '登录成功。请完善学号与真实姓名（均为必填）。',
    confirmText: '去认证',
    cancelText: '稍后',
    success: (res) => {
      setTimeout(() => {
        if (res.confirm) {
          wx.redirectTo({ url: '/pages/auth/student' })
        } else {
          wx.switchTab({ url: '/pages/index/index' })
        }
      }, 50)
    }
  })
}
  // 定义给别人用，导出才能被引入
module.exports = {
  afterLogin
}
