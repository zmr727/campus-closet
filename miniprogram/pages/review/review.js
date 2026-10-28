const { request, assetUrl, displayImageUrl } = require('../../utils/request.js')

const SCORE_TIPS = ['', '很差', '较差', '一般', '满意', '非常满意']

Page({
  data: {
    loaded: false,
    orderId: 0,
    userId: 0,
    side: 'rent',
    title: '',
    cover: '',
    peerName: '',
    roleTip: '评价',
    starList: [1, 2, 3, 4, 5],
    score: 0,
    scoreTip: '点击星星评分',
    tagOptions: [],
    content: '',
    reviewed: false,
    reviewScore: 0,
    reviewContent: '',
    reviewTags: []
  },

  onLoad(query) {
    const orderId = Number(query.order_id || query.id || 0)
    const userId = Number(wx.getStorageSync('userId') || 0)
    this.setData({ orderId, userId })
    if (!userId || !orderId) {
      wx.showToast({ title: '参数错误', icon: 'none' })
      return
    }
    this.loadOrder()
  },

  loadOrder() {
    request('/order/get_review_order.php', {
      user_id: this.data.userId,
      order_id: this.data.orderId
    })
      .then(async (data) => {
        if (!data) return
        const tagOptions = ((data.tagOptions) || []).map((text) => ({
          text,
          on: false
        }))
        const cover = await displayImageUrl(assetUrl(data.cover) || '')
        this.setData({
          loaded: true,
          side: data.side || 'rent',
          title: data.title || '',
          cover,
          peerName: data.peerName || '',
          roleTip: data.roleTip || '评价',
          tagOptions,
          reviewed: !!data.reviewed,
          reviewScore: data.reviewScore || 0,
          reviewContent: data.reviewContent || '',
          reviewTags: data.reviewTags || []
        })
        if (!data.canReview && !data.reviewed) {
          wx.showToast({ title: '当前不可评价', icon: 'none' })
        }
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
  },

  onStar(e) {
    if (this.data.reviewed) return
    const score = Number(e.currentTarget.dataset.score) || 0
    this.setData({
      score,
      scoreTip: SCORE_TIPS[score] || '点击星星评分'
    })
  },

  onTag(e) {
    if (this.data.reviewed) return
    const index = Number(e.currentTarget.dataset.index)
    const tagOptions = this.data.tagOptions.slice()
    if (!tagOptions[index]) return
    tagOptions[index] = {
      text: tagOptions[index].text,
      on: !tagOptions[index].on
    }
    this.setData({ tagOptions })
  },

  onContent(e) {
    this.setData({ content: e.detail.value || '' })
  },

  onSubmit() {
    if (this.data.reviewed) return
    const score = this.data.score
    if (score < 1) {
      wx.showToast({ title: '请先点星评分', icon: 'none' })
      return
    }
    const tags = this.data.tagOptions.filter((t) => t.on).map((t) => t.text)
    wx.showLoading({ title: '提交中' })
    request('/order/submit_review.php', {
      user_id: this.data.userId,
      order_id: this.data.orderId,
      score,
      content: (this.data.content || '').trim(),
      tags
    })
      .then(() => {
        wx.hideLoading()
        wx.showToast({ title: '评价成功', icon: 'success' })
        setTimeout(() => {
          wx.navigateBack({
            fail: () => wx.switchTab({ url: '/pages/mine/mine' })
          })
        }, 500)
      })
      .catch((err) => {
        wx.hideLoading()
        wx.showToast({ title: err.message || '提交失败', icon: 'none' })
      })
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  }
})
