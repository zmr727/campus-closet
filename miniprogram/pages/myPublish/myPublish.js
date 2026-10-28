const { request, assetUrl, mapDisplayImages } = require('../../utils/request.js')

Page({
  data: {
    list: [],
    loading: true
  },

  onShow() {
    this.loadList()
  },

  loadList() {
    const userId = Number(wx.getStorageSync('userId') || 0)
    this.setData({ loading: true })

    if (!userId) {
      this.setData({ list: [], loading: false })
      return
    }

    request('/clothes/list_clothes.php', { tag: 'all', user_id: userId })
      .then((list) => {
        const mapped = (Array.isArray(list) ? list : []).map((item) =>
          Object.assign({}, item, { cover: assetUrl(item.cover) })
        )
        return mapDisplayImages(mapped, 'cover')
      })
      .then((mapped) => {
        this.setData({
          list: mapped,
          loading: false
        })
      })
      .catch((err) => {
        this.setData({ list: [], loading: false })
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
  },

  onEdit(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/rentForm/rentForm?id=' + id })
  },

  onDelete(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    const userId = Number(wx.getStorageSync('userId') || 0)
    if (!userId) {
      wx.showToast({ title: '请先登录', icon: 'none' })
      return
    }
    wx.showModal({
      title: '删除发布',
      content: '删除后不可恢复，确定删除这件衣服吗？',
      confirmText: '删除',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        wx.showLoading({ title: '删除中' })
        request('/clothes/delete_clothes.php', { id: Number(id), user_id: userId })
          .then(() => {
            wx.hideLoading()
            wx.showToast({ title: '已删除', icon: 'success' })
            this.loadList()
          })
          .catch((err) => {
            wx.hideLoading()
            wx.showToast({ title: err.message || '删除失败', icon: 'none' })
          })
      }
    })
  },

  goPublish() {
    wx.switchTab({ url: '/pages/publish/publish' })
  },

  goDrafts() {
    wx.navigateTo({ url: '/pages/drafts/drafts' })
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  }
})
