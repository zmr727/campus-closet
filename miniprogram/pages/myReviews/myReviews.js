const { request, assetUrl, mapDisplayImages } = require('../../utils/request.js')

Page({
  data: {
    role: 'all',
    list: [],
    sellerCount: 0,
    renterCount: 0,
    avgAsSeller: 0,
    avgAsRenter: 0,
    userId: 0
  },

  onShow() {
    const userId = Number(wx.getStorageSync('userId') || 0)
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showModal({
        title: '需要登录',
        content: '登录后可查看对方给你的评价',
        confirmText: '去登录',
        confirmColor: '#FF4768',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
          else wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
        }
      })
      return
    }
    this.setData({ userId })
    this.loadList()
  },

  loadList() {
    request('/order/list_received_reviews.php', {
      user_id: this.data.userId,
      role: this.data.role
    })
      .then((data) => {
        const raw = (data && data.list) || []
        const mapped = raw.map((item) =>
          Object.assign({}, item, {
            fromAvatar: assetUrl(item.fromAvatar),
            cover: assetUrl(item.cover)
          })
        )
        return mapDisplayImages(mapped, 'fromAvatar').then((withAvatar) =>
          mapDisplayImages(withAvatar, 'cover')
        ).then((list) => ({
          list,
          sellerCount: (data && data.sellerCount) || 0,
          renterCount: (data && data.renterCount) || 0,
          avgAsSeller: (data && data.avgAsSeller) || 0,
          avgAsRenter: (data && data.avgAsRenter) || 0
        }))
      })
      .then((payload) => {
        this.setData(payload)
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
  },

  onRole(e) {
    const role = e.currentTarget.dataset.role
    if (!role || role === this.data.role) return
    this.setData({ role })
    this.loadList()
  },

  onOpenGoods(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id })
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  }
})
