const DRAFT_KEY = 'campus_publish_drafts'

Page({
  data: {
    draftCount: 0
  },

  onShow() {
    this.refreshDraftCount()
  },
  // 刷新草稿数量，读取本地缓存里的草稿数组
  refreshDraftCount() {
    let list = []
    try {
      list = wx.getStorageSync(DRAFT_KEY) || []
    } catch (e) {}
    this.setData({ draftCount: Array.isArray(list) ? list.length : 0 })
  },
// 小程序页面跳转 API，作用：保留当前页面，跳转到应用内新页面；左上角自带返回箭头，可回到上一页
  onRent() {
    wx.navigateTo({ url: '/pages/rentForm/rentForm' })
  },

  onBuyerShow() {
    wx.navigateTo({ url: '/pages/showForm/showForm' })
  },

  onRules() {
    wx.navigateTo({ url: '/pages/rules/rules' })
  },

  onDrafts() {
    wx.navigateTo({ url: '/pages/drafts/drafts' })
  }
})
