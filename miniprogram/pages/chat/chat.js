const { request, assetUrl, displayImageUrl } = require('../../utils/request.js')

Page({
  data: {
    id: 0,
    userId: 0,
    session: {},
    chats: [],
    text: '',
    scrollInto: ''
  },

  onLoad(query) {
    const id = Number(query.id || 0)
    const userId = Number(wx.getStorageSync('userId') || 0)
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showToast({ title: '请先登录', icon: 'none' })
      setTimeout(() => wx.navigateBack(), 500)
      return
    }
    this.setData({ id, userId })
    if (id) this.loadChat()
  },

  loadChat() {
    wx.showLoading({ title: '加载中' })
    request('/message/detail_message.php', {
      id: this.data.id,
      user_id: this.data.userId
    })
      .then(async (data) => {
        const chats = (data && data.chats) || []
        const session = Object.assign({}, (data && data.session) || {})
        if (session.avatar) {
          session.avatar = await displayImageUrl(assetUrl(session.avatar))
        }
        this.setData({ session, chats })
        this.scrollBottom(chats)
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
      .then(() => wx.hideLoading())
  },

  scrollBottom(chats) {
    const list = chats || this.data.chats
    if (!list.length) {
      this.setData({ scrollInto: 'msg-bottom' })
      return
    }
    const last = list[list.length - 1]
    this.setData({ scrollInto: 'msg-' + last.id })
  },

  onInput(e) {
    this.setData({ text: e.detail.value })
  },

  onSend() {
    const content = (this.data.text || '').trim()
    if (!content) {
      wx.showToast({ title: '请输入消息', icon: 'none' })
      return
    }
    request('/message/send_message.php', {
      message_id: this.data.id,
      user_id: this.data.userId,
      content
    })
      .then((item) => {
        const chats = this.data.chats.concat([item || {
          id: Date.now(),
          content,
          is_me: true,
          sender_id: this.data.userId
        }])
        this.setData({ chats, text: '' })
        this.scrollBottom(chats)
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '发送失败', icon: 'none' })
      })
  },

  onBack() {
    wx.navigateBack()
  }
})
