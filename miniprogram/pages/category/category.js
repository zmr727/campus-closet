const { request, assetUrl, displayImage, mapDisplayImages } = require('../../utils/request.js')

const CATEGORIES = [
  '全部分类',
  '新中式',
  '汉服',
  '明制',
  '民国风',
  '设计师款',
  '学院风',
  '品牌女装',
  '洛丽塔',
  'cos服',
  '主题服装',
  '旅游拍照',
  '职业装'
]

function todayStr() {
  const d = new Date()
  const m = d.getMonth() + 1
  const day = d.getDate()
  return d.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (day < 10 ? '0' + day : day)
}

function calcDays(start, end) {
  const a = new Date(start.replace(/-/g, '/'))
  const b = new Date(end.replace(/-/g, '/'))
  const diff = Math.round((b - a) / 86400000)
  return Math.max(1, diff + 1)
}

Page({
  data: {
    keyword: '',
    searchIcon: '',
    category: '',
    categoryLabel: '全部分类',
    categories: CATEGORIES,
    startDate: todayStr(),
    endDate: todayStr(),
    days: 1,
    tempStart: todayStr(),
    tempEnd: todayStr(),
    tempDays: 1,
    list: [],
    showCate: false,
    showRent: false
  },
// 刷新，输入关键词，搜索
  onShow() {
    displayImage('/images/home/search.png').then((searchIcon) => this.setData({ searchIcon }))
    this.loadList()
  },

  onKeyword(e) {
    this.setData({ keyword: e.detail.value })
  },

  onSearch() {
    this.loadList()
  },

  onPickCategory() {
    this.setData({ showCate: true, showRent: false })
  },

  onCloseCate() {
    this.setData({ showCate: false })
  },
// 点抽屉里的某一个时，执行的内容
  onSelectCate(e) {
    const label = e.currentTarget.dataset.name || '全部分类'
    this.setData({
      showCate: false,
      categoryLabel: label,
      category: label === '全部分类' ? '' : label
    })
    this.loadList()
  },

  onOpenRent() {
    const { startDate, endDate, days } = this.data
    this.setData({
      showRent: true,
      showCate: false,
      tempStart: startDate,
      tempEnd: endDate,
      tempDays: days
    })
  },

  onCloseRent() {
    this.setData({ showRent: false })
  },

  onTempStart(e) {
    const tempStart = e.detail.value
    let tempEnd = this.data.tempEnd
    if (tempEnd < tempStart) tempEnd = tempStart
    this.setData({
      tempStart,
      tempEnd,
      tempDays: calcDays(tempStart, tempEnd)
    })
  },

  onTempEnd(e) {
    let tempEnd = e.detail.value
    const tempStart = this.data.tempStart
    if (tempEnd < tempStart) {
      wx.showToast({ title: '结束日期不能早于开始日期', icon: 'none' })
      tempEnd = tempStart
    }
    this.setData({
      tempEnd,
      tempDays: calcDays(tempStart, tempEnd)
    })
  },

  onConfirmRent() {
    const { tempStart, tempEnd, tempDays } = this.data
    this.setData({
      showRent: false,
      startDate: tempStart,
      endDate: tempEnd,
      days: tempDays
    })
    this.loadList()
  },

  loadList() {
    const { keyword, category, startDate, endDate } = this.data
    wx.showLoading({ title: '加载中', mask: true })
    request('/clothes/list_clothes.php', {
      tag: 'cate',
      category: category || '',
      keyword: (keyword || '').trim(),
      rent_start: startDate,
      rent_end: endDate
    })
    // 分类页请求成功整理的列表
      .then((raw) => {
        const list = (raw || []).map((item) => {
          const size = item.size_info || '均码'
          const first = String(size).split(/[\/、,，\s]+/).filter(Boolean)[0] || '均码'
          return Object.assign({}, item, {
            size_show: first,
            cover: assetUrl(item.cover)
          })
        })
        return mapDisplayImages(list, 'cover')
      })
      .then((list) => {
        this.setData({ list })
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
      .then(() => {
        wx.hideLoading()
      })
  },
// 点击某一个具体的,跳转详情页
  onTapItem(e) {
    const id = e.currentTarget.dataset.id
    if (!id) return
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id })
  },

  noop() {}
})
