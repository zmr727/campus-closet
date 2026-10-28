const { request, assetUrl, mapDisplayImages } = require('../../utils/request.js')

const RENT_LABELS = {
  pay: '待付款',
  pickup: '待取衣',
  renting: '租赁中',
  return: '待归还',
  review: '评价'
}
const LEND_LABELS = {
  pay: '待付款',
  deliver: '待交衣',
  lending: '出租中',
  reclaim: '待收回',
  rated: '待评价'
}

Page({
  data: {
    pageTitle: '我的订单',
    side: 'rent',
    status: '',
    statusList: [],
    list: [],
    userId: 0,
    verifyCodes: {}
  },

  onLoad(query) {
    const side = query.side === 'lend' ? 'lend' : 'rent'
    const status = query.status || ''
    const userId = Number(wx.getStorageSync('userId') || 0)
    this.setData({ side, status, userId })
    this.refreshStatusList()
    this.refreshTitle()
  },

  onShow() {
    this.loadList()
  },
// 用户点击页面渲染出来的标签按钮后，会把标签的 `key` 作为 `status` 参数传给后端列表接口，后端根据 `side（lend/rent）+ status + userId` 筛选出对应用户的订单 / 商品数据
  refreshStatusList() {
    const map = this.data.side === 'lend' ? LEND_LABELS : RENT_LABELS
    const statusList = Object.keys(map).map((key) => ({ key, label: map[key] }))
    this.setData({ statusList })
  },

  refreshTitle() {
    const { side, status } = this.data
    const map = side === 'lend' ? LEND_LABELS : RENT_LABELS
    const label = status ? map[status] || status : '全部'
    const sideText = side === 'lend' ? '我出租的' : '我租到的'
    this.setData({
      pageTitle: status ? sideText + ' · ' + label : '我的订单'
    })
  },
// 1. 和前面`onLoad`、`refreshStatusList`配合：页面加载初始化筛选参数、生成状态标签，再调用`loadList`加载对应筛选条件的订单；
// 2. 后端接口`list_orders.php`接收`user_id、side、status`三个参数，拼接 SQL 条件，精准查询当前用户对应类型、对应状态的租赁订单。
  loadList() {
    const { userId, side, status } = this.data
    if (!userId) {
      this.setData({ list: [] })
      return
    }
    request('/order/list_orders.php', { user_id: userId, side, status })
      .then((data) => {
        const raw = (data && data.list) || []
        const list = raw.map((item) =>
          Object.assign({}, item, {
            codeKey: 'c' + item.id,
            cover: assetUrl(item.cover)
          })
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
// 该方法是页面**出租 / 求租 tab 切换**的核心入口，一次点击联动整套页面逻辑：筛选标签、页面标题、订单列表全部同步切换，同时清空旧筛选条件，保证切换后展示全部对应类型订单。
  onSide(e) {
    const side = e.currentTarget.dataset.side
    if (!side || side === this.data.side) return
    this.setData({ side, status: '', verifyCodes: {} })
    this.refreshStatusList()
    this.refreshTitle()
    this.loadList()
  },

  onStatus(e) {
    const status = e.currentTarget.dataset.status
    if (status === this.data.status) return
    this.setData({ status: status || '' })
    this.refreshTitle()
    this.loadList()
  },

  onOpen(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id })
  },

  onPay(e) {
    const id = e.currentTarget.dataset.id
    const price = e.currentTarget.dataset.price
    wx.showModal({
      title: '确认付款',
      content: '需支付 ￥' + price + '\n（演示环境，确认后即视为已支付）',
      confirmText: '确认支付',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        request('/order/pay_order.php', {
          user_id: this.data.userId,
          order_id: id
        })
          .then((data) => {
            wx.showModal({
              title: '支付成功',
              content:
                '已生成取件码：' +
                data.pickupCode +
                '\n取件点：' +
                data.station +
                '\n请将取件码出示给出借方核销',
              showCancel: false,
              confirmText: '知道了',
              confirmColor: '#FF4768',
              success: () => {
                this.setData({ status: 'pickup' })
                this.refreshTitle()
                this.loadList()
              }
            })
          })
          .catch((err) => {
            wx.showToast({ title: err.message || '支付失败', icon: 'none' })
          })
      }
    })
  },

  onCopyCode(e) {
    const code = e.currentTarget.dataset.code
    if (!code) return
    wx.setClipboardData({
      data: String(code),
      success: () => wx.showToast({ title: '已复制', icon: 'none' })
    })
  },

  onCodeInput(e) {
    const id = e.currentTarget.dataset.id
    if (id == null || id === '') return
    // 必须用字符串键：纯数字键会被微信当成数组下标，触发 Cannot read property '0' of undefined
    this.setData({
      ['verifyCodes.c' + id]: (e.detail.value || '').trim()
    })
  },

  onCancel(e) {
    const id = e.currentTarget.dataset.id
    wx.showModal({
      title: '取消订单',
      content: '确定取消该待付款订单吗？',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        request('/order/cancel_order.php', {
          user_id: this.data.userId,
          order_id: id
        })
          .then(() => {
            this.loadList()
            wx.showToast({ title: '已取消', icon: 'none' })
          })
          .catch((err) => {
            wx.showToast({ title: err.message || '取消失败', icon: 'none' })
          })
      }
    })
  },

  onVerify(e) {
    const id = e.currentTarget.dataset.id
    const action = e.currentTarget.dataset.action
    const code = (this.data.verifyCodes['c' + id] || '').trim()
    if (!id || !action) return
    if (!code || code.length < 4) {
      wx.showToast({ title: '请输入核销码', icon: 'none' })
      return
    }

    const isPickup = action === 'confirm_pickup'
    wx.showModal({
      title: isPickup ? '核销取件码' : '核销归还码',
      content: isPickup
        ? '确认码正确后，订单将进入租赁中'
        : '确认码正确后，订单将进入评价阶段',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        this._postAdvance(id, action, code)
      }
    })
  },

  onGoReview(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/review/review?order_id=' + id })
  },

  onAdvance(e) {
    const id = e.currentTarget.dataset.id
    const action = e.currentTarget.dataset.action
    if (!id || !action) return

    const tips = {
      start_return: {
        title: this.data.side === 'lend' ? '提醒归还' : '申请归还',
        content: '将生成归还码，请出借方核销后完成收回'
      }
    }
    const tip = tips[action] || { title: '确认操作', content: '是否继续？' }

    wx.showModal({
      title: tip.title,
      content: tip.content,
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        this._postAdvance(id, action, '')
      }
    })
  },

  _postAdvance(id, action, code) {
    wx.showLoading({ title: '处理中' })
    const payload = {
      user_id: this.data.userId,
      order_id: Number(id),
      action
    }
    if (code) payload.code = code

    request('/order/advance_order.php', payload)
      .then((data) => {
        wx.hideLoading()
        const next = (data && data.status) || ''
        if (action === 'start_return' && data && data.returnCode) {
          wx.showModal({
            title: '归还码 ' + data.returnCode,
            content: '请将此码出示给出借方核销',
            showCancel: false,
            confirmColor: '#FF4768'
          })
        }
        if (next) {
          this.setData({ status: next })
          this.refreshTitle()
        }
        const verifyCodes = Object.assign({}, this.data.verifyCodes)
        delete verifyCodes['c' + id]
        this.setData({ verifyCodes })
        this.loadList()
        if (action !== 'start_return') {
          wx.showToast({ title: '操作成功', icon: 'none' })
        }
      })
      .catch((err) => {
        wx.hideLoading()
        wx.showToast({ title: err.message || '操作失败', icon: 'none' })
      })
  },

  noop() {},

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/mine/mine' }) })
  }
})
