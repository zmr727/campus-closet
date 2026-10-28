const DRAFT_KEY = 'campus_publish_drafts'
const { assetUrl, displayImage, displayImageUrl } = require('../../utils/request.js')

Page({
  data: {
    list: [],
    fallbackCover: ''
  },

  onLoad() {
    displayImage('/images/home/rec.png').then((fallbackCover) => {
      this.setData({ fallbackCover })
      this.loadList()
    })
  },

  onShow() {
    if (this.data.fallbackCover) this.loadList()
  },

  loadList() {
    let list = wx.getStorageSync(DRAFT_KEY) || []
    if (!Array.isArray(list)) list = []
    const fallback = this.data.fallbackCover
    Promise.all(
      list.map((item) => {
        const d = new Date(item.updatedAt || Date.now())
        const m = d.getMonth() + 1
        const day = d.getDate()
        const hh = d.getHours()
        const mm = d.getMinutes()
        const coverSrc = item.coverLocal || assetUrl(item.cover) || fallback
        return displayImageUrl(coverSrc).then((cover) =>
          Object.assign({}, item, {
            cover,
            timeText:
              m + '-' + day + ' ' +
              (hh < 10 ? '0' + hh : hh) + ':' +
              (mm < 10 ? '0' + mm : mm)
          })
        )
      })
    ).then((resolved) => {
      this.setData({ list: resolved })
    })
  },

  onEdit(e) {
    const id = e.currentTarget.dataset.id
    const type = e.currentTarget.dataset.type
    if (type === 'show') {
      wx.navigateTo({ url: '/pages/showForm/showForm?draftId=' + id })
    } else {
      wx.navigateTo({ url: '/pages/rentForm/rentForm?draftId=' + id })
    }
  },

  onDelete(e) {
    const id = e.currentTarget.dataset.id
    wx.showModal({
      title: '删除草稿',
      content: '确定删除这篇草稿吗？',
      confirmColor: '#FF4768',
      success: (res) => {
        if (!res.confirm) return
        let list = wx.getStorageSync(DRAFT_KEY) || []
        list = list.filter((d) => String(d.id) !== String(id))
        wx.setStorageSync(DRAFT_KEY, list)
        this.loadList()
      }
    })
  },

  onBack() {
    wx.navigateBack()
  }
})
