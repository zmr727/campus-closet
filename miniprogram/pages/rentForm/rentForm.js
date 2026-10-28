const { request, BASE_URL } = require('../../utils/request.js')

const CATEGORIES = [
  '新中式', '汉服', '明制', '民国风', '设计师款', '学院风',
  '品牌女装', '洛丽塔', 'cos服', '主题服装', '旅游拍照', '职业装'
]
const DRAFT_KEY = 'campus_publish_drafts'

function todayStr() {
  const d = new Date()
  const m = d.getMonth() + 1
  const day = d.getDate()
  return d.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (day < 10 ? '0' + day : day)
}

function addDays(base, n) {
  const d = new Date(base.replace(/-/g, '/'))
  d.setDate(d.getDate() + n)
  const m = d.getMonth() + 1
  const day = d.getDate()
  return d.getFullYear() + '-' + (m < 10 ? '0' + m : m) + '-' + (day < 10 ? '0' + day : day)
}

Page({
  data: {
    navTitle: '上架租赁信息',
    isEdit: false,
    editId: 0,
    title: '',
    coverLocal: '',
    coverPath: '',
    category: '',
    size_info: '',
    price: '',
    intro: '',
    available_from: todayStr(),
    available_to: addDays(todayStr(), 30),
    categories: CATEGORIES,
    showCate: false
  },

  onLoad(query) {
    if (query.id) {
      this.setData({
        isEdit: true,
        editId: Number(query.id),
        navTitle: '编辑发布信息'
      })
      this.loadClothes(query.id)
      return
    }
    if (query.draftId) {
      this.loadDraft(query.draftId)
    }
  },

  loadClothes(id) {
    wx.showLoading({ title: '加载中' })
    request('/clothes/detail_clothes.php', { id: Number(id) })
      .then((item) => {
        wx.hideLoading()
        if (!item) return
        this.setData({
          title: item.title || '',
          coverLocal: item.cover || '',
          coverPath: item.cover || '',
          category: item.category || '',
          size_info: item.size_info || '',
          price: item.price ? String(item.price) : '',
          intro: item.intro || '',
          available_from: item.available_from || todayStr(),
          available_to: item.available_to || addDays(todayStr(), 30)
        })
      })
      .catch((err) => {
        wx.hideLoading()
        wx.showToast({ title: err.message || '加载失败', icon: 'none' })
      })
  },

  loadDraft(draftId) {
    const list = wx.getStorageSync(DRAFT_KEY) || []
    const item = list.find((d) => String(d.id) === String(draftId))
    if (!item) return
    this._draftId = item.id
    this.setData({
      title: item.title || '',
      coverLocal: item.coverLocal || item.cover || '',
      coverPath: item.cover || '',
      category: item.category || '',
      size_info: item.size_info || '',
      price: item.price ? String(item.price) : '',
      intro: item.intro || '',
      available_from: item.available_from || todayStr(),
      available_to: item.available_to || addDays(todayStr(), 30)
    })
  },

  onTitle(e) { this.setData({ title: e.detail.value }) },
  onSize(e) { this.setData({ size_info: e.detail.value }) },
  onPrice(e) { this.setData({ price: e.detail.value }) },
  onIntro(e) { this.setData({ intro: e.detail.value }) },
  onFrom(e) {
    const available_from = e.detail.value
    let available_to = this.data.available_to
    if (available_to < available_from) available_to = available_from
    this.setData({ available_from, available_to })
  },
  onTo(e) {
    let available_to = e.detail.value
    if (available_to < this.data.available_from) {
      wx.showToast({ title: '结束日期不能早于开始', icon: 'none' })
      available_to = this.data.available_from
    }
    this.setData({ available_to })
  },

  onPickCategory() {
    const that = this
    wx.showActionSheet({
      itemList: ['新中式', '汉服', '明制', '民国风', '设计师款', '更多分类'],
      success(res) {
        if (res.tapIndex === 5) {
          wx.showActionSheet({
            itemList: ['学院风', '品牌女装', '洛丽塔', 'cos服', '主题服装', '更多'],
            success(r2) {
              if (r2.tapIndex === 5) {
                wx.showActionSheet({
                  itemList: ['旅游拍照', '职业装'],
                  success(r3) {
                    that.setData({ category: r3.tapIndex === 0 ? '旅游拍照' : '职业装' })
                  }
                })
              } else {
                const more = ['学院风', '品牌女装', '洛丽塔', 'cos服', '主题服装']
                that.setData({ category: more[r2.tapIndex] })
              }
            }
          })
        } else {
          const first = ['新中式', '汉服', '明制', '民国风', '设计师款']
          that.setData({ category: first[res.tapIndex] })
        }
      }
    })
  },

  onChooseCover() {
    const that = this
    wx.chooseImage({
      count: 1,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success(res) {
        const path = res.tempFilePaths[0]
        that.setData({ coverLocal: path })
        that.uploadCover(path)
      }
    })
  },

  uploadCover(filePath) {
    wx.showLoading({ title: '上传中' })
    return new Promise((resolve, reject) => {
      wx.uploadFile({
        url: BASE_URL + '/upload_cover.php',
        filePath,
        name: 'file',
        success: (res) => {
          try {
            const body = JSON.parse(res.data || '{}')
            if (body.code === 0 && body.data) {
              this.setData({ coverPath: body.data.url || body.data.path })
              resolve(body.data)
            } else {
              reject(new Error(body.msg || '上传失败'))
            }
          } catch (e) {
            reject(new Error('上传返回异常'))
          }
        },
        fail: () => reject(new Error('无法连接服务器上传'))
      })
    })
      .catch((err) => {
        wx.showToast({ title: err.message || '上传失败', icon: 'none' })
      })
      .then(() => {
        wx.hideLoading()
      })
  },

  buildPayload() {
    const user = wx.getStorageSync('userInfo') || {}
    const userId = Number(wx.getStorageSync('userId') || 0)
    return {
      title: (this.data.title || '').trim(),
      cover: this.data.coverPath || this.data.coverLocal,
      price: Number(this.data.price || 0),
      category: this.data.category,
      size_info: (this.data.size_info || '').trim(),
      intro: (this.data.intro || '').trim(),
      seller: user.nickname || user.nickName || '校园用户',
      user_id: userId || 0,
      tag: 'cate',
      available_from: this.data.available_from,
      available_to: this.data.available_to
    }
  },

  onSaveDraft() {
    const payload = this.buildPayload()
    if (!payload.title && !payload.cover) {
      wx.showToast({ title: '先填写一点内容再存草稿', icon: 'none' })
      return
    }
    let list = wx.getStorageSync(DRAFT_KEY) || []
    if (!Array.isArray(list)) list = []
    const draft = Object.assign({}, payload, {
      id: this._draftId || Date.now(),
      coverLocal: this.data.coverLocal,
      type: 'rent',
      updatedAt: Date.now()
    })
    const idx = list.findIndex((d) => d.id === draft.id)
    if (idx >= 0) list[idx] = draft
    else list.unshift(draft)
    wx.setStorageSync(DRAFT_KEY, list)
    this._draftId = draft.id
    wx.showToast({ title: '已存草稿', icon: 'success' })
  },

  onSubmit() {
    const payload = this.buildPayload()
    if (!payload.title) {
      wx.showToast({ title: '请填写名称', icon: 'none' })
      return
    }
    if (!payload.cover) {
      wx.showToast({ title: '请上传封面', icon: 'none' })
      return
    }
    if (!payload.category) {
      wx.showToast({ title: '请选择分类', icon: 'none' })
      return
    }
    if (!(payload.price > 0)) {
      wx.showToast({ title: '请填写租金', icon: 'none' })
      return
    }

    const userId = wx.getStorageSync('userId')
    const isGuest = wx.getStorageSync('isGuest')
    if (!userId || isGuest) {
      wx.showModal({
        title: '需要登录',
        content: '请先登录账号',
        confirmText: '去登录',
        confirmColor: '#FF4768',
        success: (res) => {
          if (res.confirm) wx.navigateTo({ url: '/pages/login/login' })
        }
      })
      return
    }

    const isEdit = this.data.isEdit
    const api = isEdit ? '/clothes/update_clothes.php' : '/clothes/create_clothes.php'
    if (isEdit) {
      payload.id = this.data.editId
    }

    wx.showLoading({ title: isEdit ? '保存中' : '发布中' })
    request(api, payload)
      .then((data) => {
        if (!isEdit && this._draftId) {
          let list = wx.getStorageSync(DRAFT_KEY) || []
          list = list.filter((d) => d.id !== this._draftId)
          wx.setStorageSync(DRAFT_KEY, list)
        }
        wx.showToast({ title: isEdit ? '已保存' : '发布成功', icon: 'success' })
        setTimeout(() => {
          if (isEdit) {
            wx.navigateBack()
          } else {
            wx.navigateTo({ url: '/pages/detail/detail?id=' + data.id })
          }
        }, 500)
      })
      .catch((err) => {
        wx.showToast({ title: err.message || (isEdit ? '保存失败' : '发布失败'), icon: 'none' })
      })
      .then(() => {
        wx.hideLoading()
      })
  },

  onBack() {
    wx.navigateBack()
  }
})
