const { request, assetUrl, displayImageUrl, mapDisplayImages } = require('../../utils/request.js')

Page({
  data: {
    item: {},
    sizes: [],
    selectedSize: '',
    days: 1,
    totalPrice: 0,
    favorited: false,
    reviews: [],
    reviewCount: 0,
    avgScore: 0
  },
// 页面加载时获取路由传来的商品 id，无 id 则弹窗提示并终止执行，有 id 就保存 id 并请求商品详情
  onLoad(query) {
    const id = query.id ? Number(query.id) : 0
    if (!id) {
      wx.showToast({ title: '缺少商品信息', icon: 'none' })
      return
    }
    this._id = id
    this.loadDetail(id)
  },
// 加载商品详情接口，处理尺码、价格等数据更新页面，并行请求收藏状态和评论，成功或失败后关闭加载弹窗并给出对应提示
  loadDetail(id) {
    wx.showLoading({ title: '加载中' })
    request('/clothes/detail_clothes.php', { id })
      .then(async (item) => {
        const sizes = this.parseSizes(item && item.size_info)
        const days = 1
        const price = item && item.price ? Number(item.price) : 0
        if (item && item.cover) {
          item.cover = await displayImageUrl(assetUrl(item.cover))
        }
        this.setData({
          item: item || {},
          sizes,
          selectedSize: sizes[0] || '',
          days,
          totalPrice: price * days
        })
        return Promise.all([
          this.refreshFavState(id),
          this.loadReviews(id)
        ])
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
      .then(() => {
        wx.hideLoading()
      })
  },
// 传入商品 ID 请求评论接口，最多获取 20 条评论，成功则更新评论列表、评论总数、平均分，失败则清空评论相关数据
  loadReviews(id) {
    return request('/order/list_clothes_reviews.php', { clothes_id: id, limit: 20 })
      .then((data) => {
        const list = ((data && data.list) || []).map((item) =>
          Object.assign({}, item, { avatar: assetUrl(item.avatar) })
        )
        return mapDisplayImages(list, 'avatar').then((reviews) => ({
          reviews,
          reviewCount: (data && data.count) || 0,
          avgScore: (data && data.avgScore) || 0
        }))
      })
      .then(({ reviews, reviewCount, avgScore }) => {
        this.setData({ reviews, reviewCount, avgScore })
      })
      .catch(() => {
        this.setData({ reviews: [], reviewCount: 0, avgScore: 0 })
      })
  },
// 先读取本地用户信息，未登录 / 游客直接设为未收藏；登录后请求用户收藏列表，判断当前商品是否被收藏并更新页面状态，接口异常则默认未收藏
  refreshFavState(id) {
    const userId = Number(wx.getStorageSync('userId') || 0)
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      this.setData({ favorited: false })
      return Promise.resolve()
    }
    return request('/favorite/list_favorites.php', { user_id: userId })
      .then((data) => {
        const list = (data && data.list) || []
        const favorited = list.some((x) => Number(x.id) === Number(id))
        this.setData({ favorited })
      })
      .catch(() => {
        this.setData({ favorited: false })
      })
  },
// 接收尺码字符串，用多种分隔符分割、去除空格、筛除空值，返回干净的尺码数组，无数据则返回空数组
  parseSizes(sizeInfo) {
    if (!sizeInfo) return []
    return String(sizeInfo)
      .split(/[\/、,，\s]+/)
      .map((s) => s.trim())
      .filter(Boolean)
  },
// 选择尺码时更新选中尺码；增减租赁天数限制在 1-30 天内，更新天数后重新计算并保留两位小数的总价
  onSelectSize(e) {
    this.setData({ selectedSize: e.currentTarget.dataset.size || '' })
  },

  onMinusDay() {
    const days = Math.max(1, this.data.days - 1)
    this.updateDays(days)
  },

  onPlusDay() {
    const days = Math.min(30, this.data.days + 1)
    this.updateDays(days)
  },

  updateDays(days) {
    const price = Number(this.data.item.price || 0)
    this.setData({
      days,
      totalPrice: Math.round(price * days * 100) / 100
    })
  },
// 点击收藏先判断商品和防重复点击锁，游客 / 未登录弹出登录弹窗，登录后调用切换收藏接口，成功更新收藏状态与收藏数量并提示，失败弹出错误提示，最后解锁收藏点击
  onFavorite() {
    const item = this.data.item
    if (!item || !item.id || this._favLock) return
    const userId = Number(wx.getStorageSync('userId') || 0)
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showModal({
        title: '需要登录',
        content: '登录后才能收藏',
        confirmText: '去登录',
        confirmColor: '#FF4768',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
        }
      })
      return
    }
    this._favLock = true
    request('/favorite/toggle_favorite.php', {
      user_id: userId,
      clothes_id: item.id
    })
      .then((data) => {
        this.setData({
          favorited: !!(data && data.favorited),
          'item.fav_count': data.fav_count
        })
        wx.showToast({
          title: data.favorited ? '已收藏' : '已取消收藏',
          icon: 'none'
        })
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '收藏失败', icon: 'none' })
      })
      .then(() => {
        this._favLock = false
      })
  },
// 联系卖家：站内消息写入咨询草稿（消息页真实发送）；或复制演示微信号
  onContact() {
    const item = this.data.item || {}
    const title = item.title || '该商品'
    const seller = item.seller || '校园衣橱卖家'
    wx.showActionSheet({
      itemList: ['发送站内消息', '复制卖家微信号'],
      success: (res) => {
        if (res.tapIndex === 0) {
          const userId = wx.getStorageSync('userId')
          const isGuest = wx.getStorageSync('isGuest')
          if (!userId || isGuest) {
            wx.showModal({
              title: '需要登录',
              content: '登录后才能联系卖家',
              confirmText: '去登录',
              confirmColor: '#FF4768',
              success: (r) => {
                if (r.confirm) wx.navigateTo({ url: '/pages/login/login' })
              }
            })
            return
          }
          if (!item.id) {
            wx.showToast({ title: '缺少商品信息', icon: 'none' })
            return
          }
          wx.setStorageSync('draft_message', {
            clothesId: Number(item.id),
            title,
            seller,
            content: '你好，' + seller + '，我想咨询「' + title + '」的租赁细节'
          })
          wx.switchTab({ url: '/pages/message/message' })
        } else if (res.tapIndex === 1) {
          wx.setClipboardData({
            data: 'campus_' + seller,
            success: () => {
              wx.showToast({ title: '微信号已复制', icon: 'none' })
            }
          })
        }
      }
    })
  },
// 点击立即租用，先校验商品信息与尺码选择状态，未选尺码则弹窗提示终止流程
  onRent() {
    const { item, selectedSize, days, totalPrice, sizes } = this.data
    if (!item || !item.id) return
    if (sizes.length && !selectedSize) {
      wx.showToast({ title: '请先选择尺码', icon: 'none' })
      return
    }
    // 读取本地登录标识，未登录或游客则弹出登录提示弹窗，点击确认跳转登录页面
    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showModal({
        title: '需要登录',
        content: '登录后才能下单租用',
        confirmText: '去登录',
        confirmColor: '#FF4768',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
        }
      })
      return
    }
    // 弹出租用确认框展示商品、尺码、天数和总价，确认后调用创建订单接口，下单成功提示并跳转待付款订单页，失败弹出错误提
    wx.showModal({
      title: '确认租用',
      content:
        item.title +
        '\n尺码：' + (selectedSize || '均码') +
        '\n天数：' + days + '天' +
        '\n合计：￥' + totalPrice,
      confirmText: '确认下单',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        request('/order/create_order.php', {
          user_id: Number(userId),
          clothes_id: item.id,
          size: selectedSize || '均码',
          days,
          total_price: totalPrice
        })
          .then(() => {
            wx.showToast({ title: '下单成功', icon: 'success' })
            setTimeout(() => {
              wx.navigateTo({ url: '/pages/orders/orders?side=rent&status=pay' })
            }, 500)
          })
          .catch((err) => {
            wx.showToast({ title: err.message || '下单失败', icon: 'none' })
          })
      }
    })
  },
// 返回上一页，若返回失败则直接切换到首页标签页
  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/index/index' }) })
  }
})
