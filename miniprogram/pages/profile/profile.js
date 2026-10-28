const { request, BASE_URL, assetUrl, displayImage, displayImageUrl } = require('../../utils/request.js')

const DEFAULT_AVATAR_PATH = '/images/mine/avatar.png'
const PRESET_PATHS = [
  DEFAULT_AVATAR_PATH,
  '/images/avatar/a1.png',
  '/images/avatar/a2.png',
  '/images/avatar/a3.png',
  '/images/avatar/a4.png',
  '/images/avatar/a5.png',
  '/images/avatar/a6.png',
  '/images/avatar/a7.png',
  '/images/avatar/a8.png',
  '/images/avatar/a9.png',
  '/images/avatar/a10.png'
]

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
    nickname: '',
    avatar: '',
    presets: []
  },

  onLoad() {
    Promise.all(PRESET_PATHS.map((p) => displayImage(p))).then((presets) => {
      this.setData({ presets, avatar: presets[0] || '' })
    })
  },

  onShow() {
    const user = wx.getStorageSync('userInfo') || {}
    const userId = wx.getStorageSync('userId')
    const fallback = assetUrl(DEFAULT_AVATAR_PATH)
    this.setData({
      nickname: user.nickname || user.nickName || ''
    })
    displayImageUrl(assetUrl(user.avatar) || fallback).then((avatar) => {
      this.setData({ avatar })
    })
    if (!userId) return
    request('/user/get_profile.php', { user_id: Number(userId) })
      .then((u) => {
        if (!u) return
        saveUserInfo(u)
        this.setData({ nickname: u.nickname || '' })
        return displayImageUrl(assetUrl(u.avatar) || fallback)
      })
      .then((avatar) => {
        if (avatar) this.setData({ avatar })
      })
      .catch(() => {})
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  },

  onNick(e) {
    this.setData({ nickname: e.detail.value })
  },

  onPickPreset(e) {
    const src = e.currentTarget.dataset.src
    if (src) this.setData({ avatar: src })
  },

  onPickAlbum() {
    const that = this
    wx.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success(res) {
        const path = res.tempFilePaths[0]
        that.uploadAvatar(path)
      }
    })
  },

  uploadAvatar(filePath) {
    wx.showLoading({ title: '上传中' })
    wx.uploadFile({
      url: BASE_URL + '/upload_cover.php',
      filePath,
      name: 'file',
      success: (res) => {
        wx.hideLoading()
        try {
          const body = JSON.parse(res.data || '{}')
          if (body.code === 0 && body.data) {
            const url = body.data.url || body.data.path
            displayImageUrl(url).then((avatar) => this.setData({ avatar }))
          } else {
            wx.showToast({ title: body.msg || '上传失败', icon: 'none' })
          }
        } catch (e) {
          wx.showToast({ title: '上传返回异常', icon: 'none' })
        }
      },
      fail: () => {
        wx.hideLoading()
        wx.showToast({ title: '无法连接服务器', icon: 'none' })
      }
    })
  },

  onSubmit() {
    const nickname = (this.data.nickname || '').trim()
    const avatar = this.data.avatar || ''
    if (!nickname) {
      wx.showToast({ title: '请填写昵称', icon: 'none' })
      return
    }
    if (!avatar) {
      wx.showToast({ title: '请选择头像', icon: 'none' })
      return
    }
    const userId = wx.getStorageSync('userId')
    if (!userId) {
      wx.showToast({ title: '请先登录', icon: 'none' })
      return
    }
    wx.showLoading({ title: '保存中' })
    request('/user/update_profile.php', {
      user_id: Number(userId),
      nickname,
      avatar
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
