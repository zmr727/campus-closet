const cache = {}

function useDirectSrc(src) {
  if (!src) return true
  // 本地包内路径、微信临时文件、HTTPS 可直接用
  if (/^(wxfile:|cloud:\/\/)/i.test(src)) return true
  if (/^https:\/\//i.test(src)) return true
  if (/^\//.test(src)) return true
  // HTTP 局域网图：模拟器/真机都走 downloadFile（基础库 3.17+ 禁止 image 直接用 HTTP）
  return false
}

Component({
  properties: {
    src: { type: String, value: '', observer: 'loadSrc' },
    mode: { type: String, value: 'scaleToFill' },
    lazyLoad: { type: Boolean, value: false }
  },

  data: {
    displaySrc: ''
  },

  lifetimes: {
    attached() {
      this.loadSrc(this.properties.src)
    }
  },

  methods: {
    loadSrc(src) {
      if (!src) {
        this.setData({ displaySrc: '' })
        return
      }
      if (useDirectSrc(src)) {
        this.setData({ displaySrc: src })
        return
      }
      if (cache[src]) {
        this.setData({ displaySrc: cache[src] })
        return
      }
      wx.downloadFile({
        url: src,
        success: (res) => {
          if (res.statusCode === 200 && res.tempFilePath) {
            cache[src] = res.tempFilePath
            this.setData({ displaySrc: res.tempFilePath })
          } else {
            this.setData({ displaySrc: src })
          }
        },
        fail: () => {
          this.setData({ displaySrc: src })
        }
      })
    },

    onTap(e) {
      this.triggerEvent('tap', e.detail)
    },

    onError(e) {
      this.triggerEvent('error', e.detail)
    }
  }
})
