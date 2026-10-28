const { request, assetUrl, displayImage, mapDisplayImages } = require('../../utils/request.js')

const CAMPUS_LIST = ['大学城校区', '五山校区', '国际校区', '南校区']
const CAMPUS_KEY = 'campusName'

Page({
  data: {
    campus: '大学城校区',
    searchIcon: '',
    moreIcon: '',
    logo: '',
    banners: [],
    calendar: [],
    calendarCount: 0,
    hotList: [],
    leftList: [],
    rightList: []
  },

  onLoad() {
    this.loadCampus()
    this.loadStaticImages()
    this.loadHome()
  },

  onShow() {
    this.loadCampus()
  },

  loadStaticImages() {
    const bannerPaths = [
      '/images/home/banner.png',
      '/images/home/banner2.png',
      '/images/home/banner3.png'
    ]
    Promise.all([
      displayImage('/images/home/logo.png'),
      displayImage('/images/home/search.png'),
      displayImage('/images/home/more.png'),
      ...bannerPaths.map((p) => displayImage(p))
    ]).then(([logo, searchIcon, moreIcon, ...banners]) => {
      this.setData({ logo, searchIcon, moreIcon, banners })
    })
  },

  loadCampus() {
    let campus = ''
    try {
      campus = wx.getStorageSync(CAMPUS_KEY) || ''
    } catch (e) {}
    if (!campus) campus = '大学城校区'
    this.setData({ campus })

    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) return

    request('/user/get_profile.php', { user_id: Number(userId) })
      .then((u) => {
        if (u && u.campus) {
          this.setData({ campus: u.campus })
          try {
            wx.setStorageSync(CAMPUS_KEY, u.campus)
          } catch (e) {}
        }
      })
      .catch(() => {})
  },

  onCampus() {
    wx.showActionSheet({
      itemList: CAMPUS_LIST,
      success: (res) => {
        const campus = CAMPUS_LIST[res.tapIndex]
        if (!campus) return
        this.setData({ campus })
        try {
          wx.setStorageSync(CAMPUS_KEY, campus)
        } catch (e) {}

        const userId = wx.getStorageSync('userId')
        const isGuest = wx.getStorageSync('isGuest')
        if (!userId || isGuest) {
          wx.showToast({ title: '已切换到' + campus, icon: 'none' })
          return
        }
        request('/user/update_campus.php', {
          user_id: Number(userId),
          campus
        })
          .then((user) => {
            try {
              const info = wx.getStorageSync('userInfo') || {}
              info.campus = (user && user.campus) || campus
              wx.setStorageSync('userInfo', info)
            } catch (e) {}
            wx.showToast({ title: '已切换到' + campus, icon: 'none' })
          })
          .catch(() => {
            wx.showToast({ title: '已切换（本地）', icon: 'none' })
          })
      }
    })
  },

  onPullDownRefresh() {
    this.loadHome().finally(() => {
      wx.stopPullDownRefresh()
    })
  },

  loadHome() {
    if (this._loading) return Promise.resolve()
    this._loading = true

    return Promise.all([
      request('/clothes/list_clothes.php', { tag: 'calendar' }),
      request('/clothes/list_clothes.php', { tag: 'hot' }),
      request('/clothes/list_clothes.php', { tag: 'recommend' })
    ])
      .then(async (results) => {
        const calendarRaw = results[0] || []
        const hotRaw = results[1] || []
        const recommendRaw = results[2] || []

        const calendar = await mapDisplayImages(
          calendarRaw.map((item) => ({
            id: item.id,
            date: item.date_label || '',
            name: item.title,
            cover: assetUrl(item.cover)
          })),
          'cover'
        )
        const hotList = await mapDisplayImages(
          hotRaw.slice(0, 3).map((item) =>
            Object.assign({}, item, { cover: assetUrl(item.cover) })
          ),
          'cover'
        )
        const recommend = await mapDisplayImages(
          recommendRaw.map((item) =>
            Object.assign({}, item, { cover: assetUrl(item.cover) })
          ),
          'cover'
        )

        const leftList = []
        const rightList = []
        recommend.forEach((item, i) => {
          if (i % 2 === 0) {
            rightList.push(item)
          } else {
            leftList.push(item)
          }
        })

        this.setData({
          calendar,
          calendarCount: calendar.length,
          hotList,
          leftList,
          rightList
        })
      })
      .catch((err) => {
        wx.showToast({ title: (err && err.message) || '首页加载失败', icon: 'none' })
      })
      .then(() => {
        this._loading = false
      })
  },

  onSearch() {
    wx.navigateTo({ url: '/pages/search/search' })
  },

  onBuyerShow() {
    wx.navigateTo({ url: '/pages/buyershow/buyershow' })
  },

  onTapGoods(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id })
  }
})
