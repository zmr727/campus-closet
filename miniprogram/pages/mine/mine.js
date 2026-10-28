const { request, assetUrl, displayImageUrl } = require('../../utils/request.js')

const ORDER_RENT = [
  { key: 'pay', label: '待付款', icon: '/images/mine/pay.png' },
  { key: 'pickup', label: '待取衣', icon: '/images/mine/pickup.png' },
  { key: 'renting', label: '租赁中', icon: '/images/mine/renting.png' },
  { key: 'return', label: '待归还', icon: '/images/mine/return.png' },
  { key: 'review', label: '评价', icon: '/images/mine/review.png' }
]

const ORDER_LEND = [
  { key: 'pay', label: '待付款', icon: '/images/mine/lend_pay.png' },
  { key: 'deliver', label: '待交衣', icon: '/images/mine/lend_deliver.png' },
  { key: 'lending', label: '出租中', icon: '/images/mine/lend_renting.png' },
  { key: 'reclaim', label: '待收回', icon: '/images/mine/lend_return.png' },
  { key: 'rated', label: '待评价', icon: '/images/mine/lend_review.png' }
]

const EMPTY_COUNTS = {
  rent: { pay: 0, pickup: 0, renting: 0, return: 0, review: 0 },
  lend: { pay: 0, deliver: 0, lending: 0, reclaim: 0, rated: 0 }
}

function withBadges(list, counts) {
  const map = counts || {}
  return list.map((item) => ({
    key: item.key,
    label: item.label,
    icon: item.icon,
    badge: Number(map[item.key]) || 0
  }))
}

Page({
  data: {
    nickname: '未登录',
    avatar: '',
    roleText: '普通用户',
    studentId: '',
    orderTab: 'rent',
    orderStatuses: withBadges(ORDER_RENT, EMPTY_COUNTS.rent),
    rentTotal: 0,
    lendTotal: 0,
    loggedIn: false,
    isVip: false,
    userId: 0
  },

  onShow() {
    this.refreshUser()
  },

  refreshUser() {
    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    const user = wx.getStorageSync('userInfo') || {}
    const loggedIn = !!(userId && !isGuest)
    const fallbackAvatar = assetUrl('/images/mine/avatar.png')

    let nickname = '未登录'
    let studentId = ''
    let roleText = '游客'
    let avatarUrl = fallbackAvatar

    if (loggedIn) {
      nickname = user.nickname || user.nickName || '校园用户'
      studentId = user.student_id || user.studentId || ''
      roleText = studentId ? '认证用户' : '普通用户'
      avatarUrl = assetUrl(user.avatar) || fallbackAvatar
    } else if (isGuest) {
      nickname = '游客浏览'
      roleText = '游客'
    }

    displayImageUrl(avatarUrl).then((avatar) => {
      this.setData({
        loggedIn,
        nickname,
        studentId,
        roleText,
        avatar,
        userId: loggedIn ? Number(userId) : 0,
        isVip: false
      })
    })

    if (!loggedIn) {
      this._orderCounts = EMPTY_COUNTS
      this.applyOrderBadges(EMPTY_COUNTS)
      return
    }

    request('/vip/vip_status.php', { user_id: Number(userId) })
      .then((data) => {
        this.setData({ isVip: !!(data && data.isVip) })
      })
      .catch(() => {})

    request('/user/get_profile.php', { user_id: Number(userId) })
      .then((u) => {
        if (!u) return
        try {
          const merged = Object.assign({}, user, {
            phone: u.phone || '',
            openid: u.openid || '',
            login_type: u.login_type || '',
            address: u.address || '',
            nickname: u.nickname || user.nickname,
            student_id: u.student_id || user.student_id,
            avatar: u.avatar || fallbackAvatar
          })
          wx.setStorageSync('userInfo', merged)
        } catch (e) {}
        displayImageUrl(assetUrl(u.avatar) || fallbackAvatar).then((avatar) => {
          this.setData({
            studentId: u.student_id || studentId,
            nickname: u.nickname || nickname,
            roleText: (u.student_id || studentId) ? '认证用户' : '普通用户',
            avatar
          })
        })
      })
      .catch(() => {})

    this.loadOrderCounts(Number(userId))
  },

  loadOrderCounts(userId) {
    request('/order/order_counts.php', { user_id: userId })
      .then((data) => {
        const counts = {
          rent: Object.assign({}, EMPTY_COUNTS.rent, (data && data.rent) || {}),
          lend: Object.assign({}, EMPTY_COUNTS.lend, (data && data.lend) || {})
        }
        this._orderCounts = counts
        this.applyOrderBadges(counts)
      })
      .catch(() => {})
  },

  applyOrderBadges(counts) {
    const tab = this.data.orderTab
    const base = tab === 'lend' ? ORDER_LEND : ORDER_RENT
    const map = tab === 'lend' ? counts.lend : counts.rent
    const sum = (obj) =>
      Object.keys(obj || {}).reduce((s, k) => s + (Number(obj[k]) || 0), 0)
    this.setData({
      orderStatuses: withBadges(base, map),
      rentTotal: sum(counts.rent),
      lendTotal: sum(counts.lend)
    })
  },

  ensureLogin() {
    if (this.data.loggedIn) return true
    wx.showModal({
      title: '需要登录',
      content: '登录后可查看个人中心完整功能',
      confirmText: '去登录',
      confirmColor: '#FF4768',
      success: (res) => {
        if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
      }
    })
    return false
  },

  onEditProfile() {
    if (!this.data.loggedIn) {
      wx.navigateTo({ url: '/pages/login/login' })
      return
    }
    wx.navigateTo({ url: '/pages/profile/profile' })
  },

  onUserArea() {
    this.onEditProfile()
  },

  onStudent() {
    if (!this.ensureLogin()) return
    const user = wx.getStorageSync('userInfo') || {}
    const hasSid = !!(this.data.studentId || user.student_id)
    const hasReal = !!(user.real_name)
    if (hasSid && hasReal) {
      wx.showModal({
        title: '已完成学号认证',
        content: '学号：' + (this.data.studentId || user.student_id) + '\n姓名：' + user.real_name,
        showCancel: false,
        confirmColor: '#FF4768'
      })
      return
    }
    wx.navigateTo({ url: '/pages/auth/student' })
  },

  onVip() {
    if (!this.ensureLogin()) return
    if (this.data.isVip) {
      wx.showModal({
        title: '会员权益',
        content: '· 租衣免押金（指定商品）\n· 驿站优先取衣\n· 生日礼券\n· 积分加倍\n\n当前状态：已开通',
        showCancel: false,
        confirmText: '知道了',
        confirmColor: '#FF4768'
      })
      return
    }
    wx.showModal({
      title: '开通校园会员',
      content: '演示开通：￥9.9/学期\n开通后可享免押、优先取衣等权益',
      confirmText: '确认开通',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        request('/vip/open_vip.php', {
          user_id: this.data.userId,
          plan: 'semester'
        })
          .then(() => {
            this.setData({ isVip: true })
            wx.showToast({ title: '开通成功', icon: 'success' })
          })
          .catch((err) => {
            wx.showToast({ title: err.message || '开通失败', icon: 'none' })
          })
      }
    })
  },

  onOrderTab(e) {
    const tab = e.currentTarget.dataset.tab
    if (!tab || tab === this.data.orderTab) return
    const counts = this._orderCounts || EMPTY_COUNTS
    const base = tab === 'lend' ? ORDER_LEND : ORDER_RENT
    const map = tab === 'lend' ? counts.lend : counts.rent
    this.setData({
      orderTab: tab,
      orderStatuses: withBadges(base, map)
    })
  },

  onAllOrders() {
    if (!this.ensureLogin()) return
    wx.navigateTo({
      url: '/pages/orders/orders?side=' + this.data.orderTab
    })
  },

  onOrderStatus(e) {
    if (!this.ensureLogin()) return
    const key = e.currentTarget.dataset.key || ''
    wx.navigateTo({
      url: '/pages/orders/orders?side=' + this.data.orderTab + '&status=' + key
    })
  },

  onMyPublish() {
    if (!this.ensureLogin()) return
    wx.navigateTo({ url: '/pages/myPublish/myPublish' })
  },

  onMyFav() {
    if (!this.ensureLogin()) return
    wx.navigateTo({ url: '/pages/favorites/favorites' })
  },

  onMyReviews() {
    if (!this.ensureLogin()) return
    wx.navigateTo({ url: '/pages/myReviews/myReviews' })
  },

  onAddress() {
    if (!this.ensureLogin()) return
    wx.navigateTo({ url: '/pages/address/address' })
  },

  onDeposit() {
    wx.navigateTo({ url: '/pages/rules/rules' })
  },

  onSettings() {
    wx.showActionSheet({
      itemList: ['清除本地缓存', '退出登录'],
      success: (res) => {
        if (res.tapIndex === 0) {
          try {
            ;['draft_message', 'campus_publish_drafts'].forEach((k) => wx.removeStorageSync(k))
          } catch (e) {}
          wx.showToast({ title: '已清除部分缓存', icon: 'none' })
        } else if (res.tapIndex === 1) {
          try {
            wx.removeStorageSync('userId')
            wx.removeStorageSync('userInfo')
            wx.removeStorageSync('isGuest')
          } catch (e) {}
          this.refreshUser()
          wx.showToast({ title: '已退出', icon: 'none' })
          setTimeout(() => {
            wx.navigateTo({ url: '/pages/login/login' })
          }, 400)
        }
      }
    })
  }
})
