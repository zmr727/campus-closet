const { request, BASE_URL } = require('../../utils/request.js')
const DRAFT_KEY = 'campus_publish_drafts'

Page({
  data: {
    title: '',
    intro: '',
    coverLocal: '',
    coverPath: ''
  },

  onLoad(query) {
    if (query.draftId) this.loadDraft(query.draftId)
  },

  loadDraft(draftId) {
    const list = wx.getStorageSync(DRAFT_KEY) || []
    const item = list.find((d) => String(d.id) === String(draftId))
    if (!item) return
    this._draftId = item.id
    this.setData({
      title: item.title || '',
      intro: item.intro || '',
      coverLocal: item.coverLocal || item.cover || '',
      coverPath: item.cover || ''
    })
  },

  onTitle(e) { this.setData({ title: e.detail.value }) },
  onIntro(e) { this.setData({ intro: e.detail.value }) },

  onChooseCover() {
    const that = this
    wx.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success(res) {
        const path = res.tempFilePaths[0]
        that.setData({ coverLocal: path })
        that.uploadCover(path)
      }
    })
  },

  uploadCover(filePath) {
    wx.showLoading({ title: '上传中' })
    return new Promise((resolve, reject) => {
      wx.uploadFile({
        url: BASE_URL + '/upload_cover.php',
        filePath,
        name: 'file',
        success: (res) => {
          try {
            const body = JSON.parse(res.data || '{}')
            if (body.code === 0 && body.data) {
              this.setData({ coverPath: body.data.url || body.data.path })
              resolve(body.data)
            } else {
              reject(new Error(body.msg || '上传失败'))
            }
          } catch (e) {
            reject(new Error('上传返回异常'))
          }
        },
        fail: () => reject(new Error('无法连接服务器上传'))
      })
    })
      .catch((err) => {
        wx.showToast({ title: err.message || '上传失败', icon: 'none' })
      })
      .then(() => wx.hideLoading())
  },

  onSaveDraft() {
    const user = wx.getStorageSync('userInfo') || {}
    const draft = {
      id: this._draftId || Date.now(),
      type: 'show',
      title: (this.data.title || '').trim(),
      intro: (this.data.intro || '').trim(),
      cover: this.data.coverPath || this.data.coverLocal,
      coverLocal: this.data.coverLocal,
      seller: user.nickname || user.nickName || '校园用户',
      updatedAt: Date.now()
    }
    if (!draft.title && !draft.cover) {
      wx.showToast({ title: '先填写一点内容再存草稿', icon: 'none' })
      return
    }
    let list = wx.getStorageSync(DRAFT_KEY) || []
    if (!Array.isArray(list)) list = []
    const idx = list.findIndex((d) => d.id === draft.id)
    if (idx >= 0) list[idx] = draft
    else list.unshift(draft)
    wx.setStorageSync(DRAFT_KEY, list)
    this._draftId = draft.id
    wx.showToast({ title: '已存草稿', icon: 'success' })
  },

  onSubmit() {
    const title = (this.data.title || '').trim()
    const cover = this.data.coverPath || this.data.coverLocal
    if (!title) {
      wx.showToast({ title: '请填写标题', icon: 'none' })
      return
    }
    if (!cover) {
      wx.showToast({ title: '请上传照片', icon: 'none' })
      return
    }

    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showModal({
        title: '需要登录',
        content: '发布前请先登录账号',
        confirmText: '去登录',
        confirmColor: '#FF4768',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
        }
      })
      return
    }

    const user = wx.getStorageSync('userInfo') || {}
    wx.showLoading({ title: '发布中' })
    request('/buyershow/create_buyer_show.php', {
      title,
      cover,
      content: (this.data.intro || '').trim(),
      user_id: userId,
      nickname: user.nickname || user.nickName || '校园用户'
    })
      .then((data) => {
        if (this._draftId) {
          let list = wx.getStorageSync(DRAFT_KEY) || []
          list = list.filter((d) => d.id !== this._draftId)
          wx.setStorageSync(DRAFT_KEY, list)
        }
        wx.showToast({ title: '发布成功', icon: 'success' })
        setTimeout(() => {
          wx.navigateTo({ url: '/pages/showDetail/showDetail?id=' + data.id })
        }, 500)
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '发布失败', icon: 'none' })
      })
      .then(() => wx.hideLoading())
  },

  onBack() {
    wx.navigateBack()
  }
})
