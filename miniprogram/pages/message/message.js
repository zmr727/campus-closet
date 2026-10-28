const { request, assetUrl, mapDisplayImages } = require('../../utils/request.js')

const TABS = [
  { key: 'buyer', label: '买家咨询' },
  { key: 'mine', label: '我的咨询' },
  { key: 'order', label: '订单通知' },
  { key: 'system', label: '系统通知' }
]

Page({
  data: {
    tabs: TABS,
    activeType: 'buyer',
    sectionTitle: '买家咨询会话',
    list: [],
    oldList: [],
    badges: { buyer: 0, mine: 0, order: 0, system: 0 },
    showOld: false,
    draft: null,
    needLogin: false,
    userId: 0
  },

  onShow() {
    const draft = wx.getStorageSync('draft_message') || null
    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    const needLogin = !userId || isGuest
    this.setData({
      draft,
      needLogin,
      userId: needLogin ? 0 : Number(userId)
    })
    if (needLogin) {
      this.setData({
        list: [],
        oldList: [],
        badges: { buyer: 0, mine: 0, order: 0, system: 0 }
      })
      return
    }
    this.loadList()
  },

  onTab(e) {
    const key = e.currentTarget.dataset.key
    if (!key || key === this.data.activeType) return
    this.setData({ activeType: key, showOld: false })
    if (!this.data.needLogin) this.loadList()
  },

  loadList() {
    request('/message/list_messages.php', {
      type: this.data.activeType,
      user_id: this.data.userId
    })
      .then((data) => {
        const list = ((data && data.list) || []).map((item) =>
          Object.assign({}, item, { avatar: assetUrl(item.avatar) })
        )
        const oldList = ((data && data.oldList) || []).map((item) =>
          Object.assign({}, item, { avatar: assetUrl(item.avatar) })
        )
        return Promise.all([
          mapDisplayImages(list, 'avatar'),
          mapDisplayImages(oldList, 'avatar')
        ]).then(([resolvedList, resolvedOldList]) => ({
          list: resolvedList,
          oldList: resolvedOldList,
          badges: (data && data.badges) || this.data.badges,
          sectionTitle: (data && data.sectionTitle) || '消息'
        }))
      })
      .then((payload) => {
        this.setData(payload)
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
  },

  onToggleOld() {
    this.setData({ showOld: !this.data.showOld })
  },

  onOpen(e) {
    if (this.data.needLogin) {
      this.goLogin()
      return
    }
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/chat/chat?id=' + id })
  },

  goLogin() {
    wx.navigateTo({ url: '/pages/login/login' })
  },

  onSendDraft() {
    if (this.data.needLogin) {
      this.goLogin()
      return
    }
    const draft = this.data.draft
    if (!draft) return
    const clothesId = Number(draft.clothesId || 0)
    const content = (draft.content || '').trim()
    if (!clothesId) {
      wx.showToast({ title: '缺少商品信息，请从详情页重新咨询', icon: 'none' })
      return
    }
    if (!content) {
      wx.showToast({ title: '请填写咨询内容', icon: 'none' })
      return
    }
    if (this._sending) return
    this._sending = true
    wx.showLoading({ title: '发送中' })
    request('/message/start_consult.php', {
      user_id: this.data.userId,
      clothes_id: clothesId,
      content
    })
      .then((data) => {
        wx.removeStorageSync('draft_message')
        this.setData({ draft: null, activeType: 'mine' })
        const mid = data && data.message_id
        wx.showToast({ title: '已发送给卖家', icon: 'success' })
        this.loadList()
        if (mid) {
          setTimeout(() => {
            wx.navigateTo({ url: '/pages/chat/chat?id=' + mid })
          }, 400)
        }
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '发送失败', icon: 'none' })
      })
      .then(() => {
        this._sending = false
        wx.hideLoading()
      })
  },

  onClearDraft() {
    wx.removeStorageSync('draft_message')
    this.setData({ draft: null })
  }
})
