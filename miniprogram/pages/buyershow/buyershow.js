const { request, assetUrl, mapDisplayImages } = require('../../utils/request.js')

Page({
  data: {
    list: []
  },

  onShow() {
    this.loadList()
  },

  loadList() {
    wx.showLoading({ title: '加载中' })
    request('/buyershow/list_buyer_shows.php', {})
      .then((list) => {
        const mapped = (list || []).map((item) =>
          Object.assign({}, item, { cover: assetUrl(item.cover) })
        )
        return mapDisplayImages(mapped, 'cover')
      })
      .then((mapped) => {
        this.setData({ list: mapped })
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
      .then(() => wx.hideLoading())
  },

  onTapItem(e) {
    const id = e.currentTarget.dataset.id
    wx.navigateTo({ url: '/pages/showDetail/showDetail?id=' + id })
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/index/index' }) })
  }
})
