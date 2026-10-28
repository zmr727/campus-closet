const { request, assetUrl, displayImageUrl } = require('../../utils/request.js')

Page({
  data: {
    id: 0,
    show: {},
    comments: [],
    commentText: ''
  },

  onLoad(query) {
    const id = Number(query.id || 0)
    this.setData({ id })
    if (id) this.loadDetail()
  },

  loadDetail() {
    wx.showLoading({ title: '加载中' })
    request('/buyershow/detail_buyer_show.php', { id: this.data.id })
      .then(async (data) => {
        const show = Object.assign({}, (data && data.show) || {})
        if (show.cover) {
          show.cover = await displayImageUrl(assetUrl(show.cover))
        }
        this.setData({
          show,
          comments: (data && data.comments) || []
        })
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
      .then(() => wx.hideLoading())
  },

  onCommentInput(e) {
    this.setData({ commentText: e.detail.value })
  },

  onSendComment() {
    const content = (this.data.commentText || '').trim()
    if (!content) {
      wx.showToast({ title: '请输入评论', icon: 'none' })
      return
    }

    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showModal({
        title: '需要登录',
        content: '评论前请先登录账号',
        confirmText: '去登录',
        confirmColor: '#FF4768',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
        }
      })
      return
    }

    const user = wx.getStorageSync('userInfo') || {}
    wx.showLoading({ title: '发送中' })
    request('/buyershow/create_buyer_comment.php', {
      show_id: this.data.id,
      content,
      user_id: userId,
      nickname: user.nickname || user.nickName || '校园用户'
    })
      .then(() => {
        this.setData({ commentText: '' })
        wx.showToast({ title: '已评论', icon: 'success' })
        this.loadDetail()
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '评论失败', icon: 'none' })
      })
      .then(() => wx.hideLoading())
  },

  onOpenClothes() {
    const cid = this.data.show && this.data.show.clothes_id
    if (!cid) return
    wx.navigateTo({ url: '/pages/detail/detail?id=' + cid })
  },

  onBack() {
    wx.navigateBack()
  }
})
