// app.js
const { HOST, assetUrl } = require('./utils/request.js')

App({
  onLaunch() {
    console.log('[CAMPUS] 当前接口地址:', HOST)
    console.log('[CAMPUS] 示例图片:', assetUrl('/images/home/logo.png'))
    console.log('[CAMPUS] 真机 HTTP 图片走 net-image 组件 downloadFile 加载')
  }
})
