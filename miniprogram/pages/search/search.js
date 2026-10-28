const { request, assetUrl, displayImage, mapDisplayImages } = require('../../utils/request.js')

Page({
  data: {
    keyword: '',
    searchIcon: '',
    list: [],
    searched: false
  },
  onLoad(query) {
    displayImage('/images/home/search.png').then((searchIcon) => this.setData({ searchIcon }))
    if (query.keyword) {
      this.setData({ keyword: query.keyword })
      this.doSearch()
    }
  },// 搜索框输入内容变化时执行
  onInput(e) {
    this.setData({ keyword: e.detail.value })
  },

  onSearch() {
    this.doSearch()
  },
// 关键词，请求列表，收尾
  doSearch() {
    const keyword = (this.data.keyword || '').trim()
    if (!keyword) {
      wx.showToast({ title: '请输入关键词', icon: 'none' })
      return
    }
    wx.showLoading({ title: '搜索中' })
    request('/clothes/list_clothes.php', { tag: 'all', keyword })
      .then((list) => {
        const mapped = (list || []).map((item) =>
          Object.assign({}, item, { cover: assetUrl(item.cover) })
        )
        return mapDisplayImages(mapped, 'cover')
      })
      .then((mapped) => {
        this.setData({ list: mapped, searched: true })
      })
      .catch((err) => {
        wx.showToast({ title: err.message || '搜索失败', icon: 'none' })
      })
      .finally(() => {
        wx.hideLoading()
      })
  },
// 点详情/返回
  onTapItem(e) {
    const id = e.currentTarget.dataset.id
    wx.navigateTo({ url: '/pages/detail/detail?id=' + id })
  },

  onBack() {
    wx.navigateBack({ fail: () => wx.switchTab({ url: '/pages/index/index' }) })
  }
})
