/**
 * 接口根地址（自动切换）
 * - 开发者工具模拟器：127.0.0.1
 * - 真机调试：下面 REAL_DEVICE_HOST（ipconfig 看 WLAN IPv4）
 *
 * 真机调试必做：
 * 1. 手机与电脑同一 WiFi
 * 2. 开发者工具 → 详情 → 本地设置 → 勾选「不校验合法域名…」
 * 3. XAMPP Apache 已启动；手机浏览器能打开 REAL_DEVICE_HOST
 */
// 真机调试时改成你电脑的局域网 IP（ipconfig 查看 WLAN IPv4）
const REAL_DEVICE_HOST = 'http://192.168.1.100'

function resolveHost() {
  try {
    const sys = wx.getSystemInfoSync()
    // 真机（含真机调试）必须用局域网 IP；127.0.0.1 在手机上指向手机自身
    if (sys.platform === 'ios' || sys.platform === 'android' || sys.platform === 'ohos') {
      return REAL_DEVICE_HOST
    }
    if (sys.platform === 'devtools') {
      return 'http://127.0.0.1'
    }
  } catch (e) {}
  return REAL_DEVICE_HOST
}

let _host = null
function getHost() {
  if (!_host) _host = resolveHost()
  return _host
}

function getBaseUrl() {
  return getHost() + '/miniprogram/campus_api'
}

function getStaticBase() {
  return getBaseUrl() + '/static'
}

/** 日历抠图：透明 png（学士服黑衣保留白底 jpg，更清晰） */
const CUTOUT_RE = /\/images\/home\/(cal\d+|new_hanfu|new_mamianskirt)\.(png|jpg)(\?|$)/i

/** 本地 /images/... → 服务器 static；已是 http 则原样返回（并纠正旧 IP / png→jpg） */
function assetUrl(path) {
  if (!path) return ''
  // 本地临时文件、微信文件路径不要改
  if (/^(wxfile|http:\/\/tmp|https:\/\/tmp)/i.test(path)) return path

  const host = getHost()
  const staticBase = getStaticBase()

  if (/^https?:\/\//i.test(path)) {
    // 把 127.0.0.1 / 旧局域网 IP 统一成当前 HOST
    path = path.replace(/^https?:\/\/(127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+)(?=[:/]|$)/i, host)
  } else if (path.indexOf('/miniprogram/') === 0) {
    path = host + path
  } else if (path.indexOf('/images/') === 0) {
    path = staticBase + path
  } else {
    return path
  }

  // 日历衣服抠图用透明 png
  if (CUTOUT_RE.test(path)) {
    return path.replace(/\.(png|jpg)(\?|$)/i, '.png$2')
  }

  // 其它大图多为 jpg；头像预设 / 小图标仍是 png
  if (path.indexOf('/campus_api/static/images/') !== -1) {
    if (!/\/images\/(avatar\/a\d+|home\/search|home\/more)\.png(\?|$)/i.test(path)) {
      path = path.replace(/\.png(\?|$)/i, '.jpg$1')
    }
  }
  return path
}

const _imgCache = {}

/** HTTP 图片先 downloadFile 再显示（基础库 3.17+ image 不支持 HTTP） */
function resolveImageSrc(url) {
  if (!url) return Promise.resolve('')
  if (!/^http:\/\//i.test(url)) return Promise.resolve(url)
  if (_imgCache[url]) return Promise.resolve(_imgCache[url])
  return new Promise((resolve) => {
    wx.downloadFile({
      url,
      success(res) {
        if (res.statusCode === 200 && res.tempFilePath) {
          _imgCache[url] = res.tempFilePath
          resolve(res.tempFilePath)
        } else {
          resolve(url)
        }
      },
      fail() {
        resolve(url)
      }
    })
  })
}

/** /images/... 路径 → 可显示的本地临时路径 */
function displayImage(path) {
  return resolveImageSrc(assetUrl(path))
}

/** 已是完整 URL 或 assetUrl 结果 → 可显示路径 */
function displayImageUrl(url) {
  if (!url) return Promise.resolve('')
  if (/^https:\/\//i.test(url)) return Promise.resolve(url)
  if (/^http:\/\//i.test(url)) return resolveImageSrc(url)
  if (url.indexOf('/images/') === 0) return displayImage(url)
  return Promise.resolve(url)
}

/** 批量解析列表里的图片字段（cover / avatar 等） */
function mapDisplayImages(list, field) {
  field = field || 'cover'
  return Promise.all(
    (list || []).map((item) =>
      displayImageUrl(item[field]).then((val) =>
        Object.assign({}, item, { [field]: val })
      )
    )
  )
}

// 封装好的统一发请求函数，各页面都通过它调 PHP。
function request(path, data, method) {
  return new Promise((resolve, reject) => {
    wx.request({
      url: getBaseUrl() + path,
      method: method || 'POST',
      header: { 'content-type': 'application/json' },
      data: data || {},
      success(res) {
        const body = res.data || {}
        if (body.code === 0) {
          resolve(body.data)
        } else {
          reject(new Error(body.msg || '请求失败'))
        }
      },
      fail() {
        reject(new Error('无法连接服务器，请确认 XAMPP 已启动且地址正确'))
      }
    })
  })
}

module.exports = {
  get HOST() { return getHost() },
  get BASE_URL() { return getBaseUrl() },
  get STATIC_BASE() { return getStaticBase() },
  assetUrl,
  resolveImageSrc,
  displayImage,
  displayImageUrl,
  mapDisplayImages,
  request
}
