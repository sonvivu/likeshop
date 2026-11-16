<template>
    <view
        :style="
            'background-image: url(' +
            $getImageUri('/static/common/image/default/login_bg.png') +
            ')'
        "
    >
        <!-- #ifndef  H5 -->
        <u-sticky offset-top="0" custom-nav-height="0" bg-color="transparent">
            <u-navbar
                :auto-back="true"
                :title="$t('注册账号')"
                :title-style="{fontWeight:'bold'}"
                :fixed="false"
                bg-color="transparent"
            ></u-navbar>
        </u-sticky>
        <!-- #endif -->
        <view class="register-container">
            <view class="input-container">
                <view class="xxl bold">{{ $t('注册新账号') }}</view>

                <view class="input row" style="padding: 15rpx">
                    <u-input
                        v-model="mobile"
                        :placeholder="$t('请输入手机号码')"
                        type="number"
                        style="width: 100%" border="none" />
                </view>

                <view class="input row" style="padding: 15rpx" v-if="appConfig.register_setting">
                    <u-input
                        v-model="captchaCode"
                        style="flex: 1"
                        :placeholder="$t('请输入图形验证码')"
                        border="none"
                    />
                    <image
                        class="captcha-img"
                        :src="captchaImg"
                        mode="aspectFit"
                        @click="getCaptchaImg"
                    />
                </view>

                <view class="input row" style="padding: 15rpx" v-if="appConfig.register_setting">
                    <u-input
                        v-model="smsCode"
                        style="flex: 1; width: 100%"
                        :placeholder="$t('请输入验证码')"
                        border="none"
                    />
                    <view class="row">
                        <view
                            class="row-center sms-btn sm br60"
                            :style="{ color: mobile.length == 11 ? 'red' : 'gray' }"
                            @click="sendSmsFun()"
                        >
                            <view v-show="canSendSms">{{ $t('获取验证码') }}</view>
                                                        <u-count-down ref="countDown" :time="time * 1000" :auto-start="false" v-show="!canSendSms" format="ss秒" @finish="countDownFinish()">
                                <template #default="{days, hours, minutes, seconds}"><text style="color:#FF2C3C">{{ String(seconds).padStart(2, '0') }}秒</text></template>
                            </u-count-down>
                        </view>
                    </view>

                    <!-- <view class="input-label row md normal">{$t('短信验证码')} </view>
          <input v-model="smsCode" style="width: 3.8rem" placeholder="请输入" />
          <button
            class="bd-primary sm primary br60 row-center"
            @click="sendSmsFun()"
          >

            <view v-show="canSendSms" class="sm">{$t('获取验证码')}</view>
                        <u-count-down ref="countDown" :time="time * 1000" :auto-start="false" v-show="!canSendSms" format="ss秒" @finish="countDownFinish()">
                <template #default="{days, hours, minutes, seconds}"><text style="color:#FF2C3C">{{ String(seconds).padStart(2, '0') }}秒</text></template>
            </u-count-down>
          </button> -->
                </view>
                <view class="input row" style="padding: 15rpx">
                    <u-input
                        style="width: 100%"
                        type="password"
                        v-model="password"
                        :placeholder="$t('请输入密码')" border="none" />
                </view>
                <view class="input row" style="padding: 15rpx">
                    <u-input
                        style="width: 100%"
                        type="password"
                        v-model="passwordConfirm"
                        :placeholder="$t('请在次输入密码')" border="none" />
                </view>
            </view>
            <view class="mt20">
                <u-checkbox v-model:checked="isAgree" shape="circle">
                    <view class="sm row">
                        {$t('已阅读并同意')}
                        <navigator
                            @tap.stop=""
                            class="primary"
                            hover-class="none"
                            url="/bundle/pages/server_explan/server_explan?type=0"
                            >{{ $t('《服务协议》') }}</navigator
                        >
                        {$t('和')}
                        <navigator
                            @tap.stop=""
                            class="primary"
                            hover-class="none"
                            url="/bundle/pages/server_explan/server_explan?type=1"
                            >{{ $t('《隐私协议》') }}</navigator
                        >
                    </view>
                </u-checkbox>
            </view>

            <button
                class="btn white bg-primary row-center"
                @click="registerFun"
                :class="{ inactive }"
            >
                {$t('注册')}
            </button>
        </view>
        <!-- 阅读协议弹框 -->
        <u-modal
            :show="showModel"
            show-cancel-button
            @confirm=";(isAgree = true), (showModel = false)"
            @cancel="showModel = false"
            confirm-color="#FF2C3C"
        >
            <view class="comfirm-box">
                <view> {{ $t('请先阅读并同意') }} </view>
                <view class="flex row-center">
                    <navigator
                        class="primary"
                        hover-class="none"
                        url="/bundle/pages/server_explan/server_explan?type=0"
                    >
                        <view class="agreement">{{ $t('《服务协议》') }}</view>
                    </navigator>
                    {$t('和')}
                    <navigator
                        class="primary"
                        hover-class="none"
                        url="/bundle/pages/server_explan/server_explan?type=1"
                    >
                        <view class="agreement">{{ $t('《隐私协议》') }}</view>
                    </navigator>
                </view>
            </view>
        </u-modal>
    </view>
</template>

<script>
import { register, sendSms, getCaptcha } from '@/api/app.js'
import { SMSType } from '@/utils/type.js'
import { mapMutations, mapGetters } from 'vuex'
import { client } from '@/utils/tools'

export default {
    name: 'register',
    created() {},
    data() {
        return {
            isAgree: false,
            mobile: '',
            smsCode: '',
            password: '',
            passwordConfirm: '',
            canSendSms: true,
            time: 59,
            primaryColor: '#FF2C3C',
            showModel: false,
            captchaCode: '',
            captchaImg: '',
            captchaKey: ''
        }
    },
    onLoad() {
        console.log(this.appConfig)
        if (this.appConfig.register_setting) {
            this.getCaptchaImg()
        }
    },
    computed: {
        ...mapGetters(['appConfig']),

        inactive() {
            if (this.appConfig.register_setting) {
                if (
                    this.mobile.length !== 11 ||
                    !this.smsCode ||
                    !this.password ||
                    !this.passwordConfirm
                ) {
                    return true
                } else {
                    return false
                }
            } else {
                if (this.mobile.length !== 11 || !this.password || !this.passwordConfirm) {
                    return true
                } else {
                    return false
                }
            }
        }
    },
    methods: {
        changeChecked() {
            this.isAgree = !this.isAgree
        },
        registerFun() {
            let { isAgree, mobile, password, smsCode, passwordConfirm } = this
            if (!mobile) {
                this.$toast({ title: this.$t('请填写手机号') })
                return
            }
            if (!password) {
                this.$toast({ title: this.$t('请设置密码') })
                return
            }
            if (password != passwordConfirm) {
                this.$toast({ title: this.$t('两次密码输入不一致') })
                return
            }
            if (!isAgree) {
                // this.$toast({ title: "请阅读并同意《服务协议》《隐私协议》" });
                this.showModel = true
                return
            }
            let data = {
                mobile: mobile,
                password: password,
                code: smsCode,
                client: client
            }
            register(data).then((res) => {
                if (res.code == 1) {
                    this.$toast({ title: res.msg })
                    //  跳转到登录页
                    setTimeout(() => {
                        uni.navigateBack()
                    }, 1000)
                }
            })
        },

        countDownFinish() {
            this.canSendSms = true
        },
        async getCaptchaImg() {
            try {
                const res = await getCaptcha()
                if (res.code == 1) {
                    this.captchaImg = res.data.image || ''
                    this.captchaKey = res.data.key || ''
                }
            } catch (error) {
                this.captchaImg = ''
                this.captchaKey = ''
            }
        },

        sendSmsFun() {
            if (this.mobile.length !== 11) {
                this.$toast({
                    title: this.$t('请填写正确的手机号信息')
                })
                return
            }
            if (this.canSendSms == false) {
                return
            }
            if (!this.captchaCode) {
                this.$toast({
                    title: this.$t('请输入图形验证码')
                })
                return
            }
            if (!this.captchaKey) {
                this.$toast({
                    title: this.$t('图形验证码已失效，请重新获取')
                })
                this.getCaptchaImg()
                return
            }

            sendSms({
                mobile: this.mobile,
                key: SMSType.REGISTER,
                captcha_key: this.captchaKey,
                captcha: this.captchaCode
            }).then((res) => {
                if (res.code == 1) {
                    this.canSendSms = false
                    this.$toast(res.msg)
                    this.$refs.countDown.start()
                } else {
                    this.getCaptchaImg()
                    this.captchaCode = ''
                }
            }).catch(() => {
                this.getCaptchaImg()
                this.captchaCode = ''
            })
        }
    }
}
</script>

<style lang="scss">
.register-container {
    min-height: 100vh;
    padding: 40px 20px 0;
    .input-container {
        .input {
            height: 100rpx;
            border-radius: 10rpx;
            width: 670rpx;
            border: $ls-solid-border;
            margin-top: 30rpx;
        }
        .input-item {
            padding: 0 10px;
            height: 44px;
            margin-bottom: 15px;
            border-bottom: 1px solid #d7d7d7;
            .input-label {
                width: 90px;
                font-size: 15px;
                flex: none;
            }
            input {
                flex: 1;
                font-size: 15px;
            }
            .bd-primary {
                height: 29px;
                width: 88px;
                flex: none;
                border: 1px solid $ls-color-primary;
                cursor: pointer;
                .seconds {
                    color: $ls-color-primary;
                }
            }
        }
    }
    .btn {
        background-color: #ff2c3c;
        width: 100%;
        margin-top: 80rpx;
        height: 100rpx;
    }
}
.comfirm-box {
    text-align: center;
    padding: 60rpx 0 70rpx 0;
}
.agreement {
    color: $ls-color-primary;
}
.inactive {
    opacity: 0.5;
}
.captcha-img {
    width: 210rpx;
    height: 90rpx;
    margin-left: 20rpx;
}
</style>
