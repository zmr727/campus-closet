const { request, assetUrl, mapDisplayImages } = require('../../utils/request.js')

Page({
  data: { list: [], userId: 0 },

  onShow() {
    const userId = Number(wx.getStorageSync('userId') || 0)
    this.setData({ userId })
    this.loadList()
  },

  loadList() {
    if (!this.data.userId) {
      this.setData({ list: [] })
      return
    }
    request('/favorite/list_favorites.php', { user_id: this.data.userId })
      .then((data) => {
        const list = ((data && data.list) || []).map((item) =>
          Object.assign({}, item, { cover: assetUrl(item.cover) })
        )
        return mapDisplayImages(list, 'cover')
      })
      .then((list) => {
        this.setData({ list })
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
        this.setData({ list: [] })
      })
  },

  onOpen(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id })
  },

  onRemove(e) {
    const id = e.currentTarget.dataset.id
    const item = this.data.list.find((x) => Number(x.id) === Number(id))
    if (!item) return
    wx.showModal({
      title: '取消收藏',
      content: '确定取消收藏「' + item.title + '」吗？',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        request('/favorite/toggle_favorite.php', {
          user_id: this.data.userId,
          clothes_id: id
        })
          .then(() => this.loadList())
          .catch((err) => {
            wx.showToast({ title: err.message || '操作失败', icon: 'none' })
          })
      }
    })
  },

  goHome() {
    wx.switchTab({ url: '/pages/index/index' })
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  }
})
