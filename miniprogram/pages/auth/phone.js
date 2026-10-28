const { afterLogin } = require('../../utils/auth.js')
const { request } = require('../../utils/request.js')

Page({
  // 手机号登录页的初始数据
  data: { phone: '', code: '', codeText: '获取验证码', counting: false },
  // 返回上一页，手机号输入，验证码输入
  onBack() { wx.navigateBack() },
  onPhone(e) { this.setData({ phone: e.detail.value }) },
  onCode(e) { this.setData({ code: e.detail.value }) },
  // 倒计时，检查格式
  onSendCode() {
    if (this.data.counting) return
    if (!/^1\d{10}$/.test(this.data.phone)) {
      wx.showToast({ title: '请输入正确手机号', icon: 'none' })
      return
    }
    // 假装发了验证码，按钮进入 60 秒冷却，到点再能点。
    wx.showToast({ title: '验证码已发送(演示)', icon: 'none' })
    let n = 60
    this._alive = true
    this.setData({ counting: true, codeText: n + 's' })
    if (this._timer) clearInterval(this._timer)
    this._timer = setInterval(() => {
      if (!this._alive) {
        clearInterval(this._timer)
        return
      }
      n -= 1
      if (n <= 0) {
        clearInterval(this._timer)
        this._timer = null
        this.setData({ counting: false, codeText: '获取验证码' })
      } else {
        this.setData({ codeText: n + 's' })
      }
    }, 1000)
  },
  // 这是页面的生命周期函数：用户离开这一页时自动执行（返回、跳走都会触发）。
  onUnload() {
    this._alive = false
    if (this._timer) {
      clearInterval(this._timer)
      this._timer = null
    }
  },

  /** 手机号登录 → 有则登录，无则自动写入 users（不必单独注册）
   * 校验 → 请求 login_phone.php → 成功走 afterLogin，失败弹错误。演示验证码是 123456。
   */
  onSubmit() {
    const { phone, code } = this.data
    if (!/^1\d{10}$/.test(phone) || !code) {
      wx.showToast({ title: '请填写手机号和验证码', icon: 'none' })
      return
    }
    wx.showLoading({ title: '登录中' })
    request('/user/login_phone.php', { phone, code })
      .then((user) => {
        wx.hideLoading()
        afterLogin(user)
      })
      .catch((err) => {
        wx.hideLoading()
        wx.showToast({ title: err.message || '登录失败', icon: 'none' })
      })
  }
})
