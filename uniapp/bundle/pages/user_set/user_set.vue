<template>
    <view class="user-profile-container mt10">
        <view class="user-profile">
            <view class="user-avatar-box column-center">
                <button
                    class="column column-center"
                    hover-class="none"
                    open-type="chooseAvatar"
                    @chooseavatar="onChooseAvatar"
                    @click="onChooseAvatar"
                >
                    <image
                        class="user-avatar"
                        :src="
                            userInfo.avatar != ''
                                ? userInfo.avatar
                                : '../../static/images/default_avatar.png'
                        "
                    >
                    </image>
                    <view class="muted xs">{{ $t('点击修改头像') }}</view>
                </button>
            </view>
            <view class="row-info row bdb-line">
                <view class="label md">ID</view>
                <view class="md row" style="flex: 1">{{ userInfo.sn }}</view>
            </view>
            <view class="row-info row bdb-line" @click="changeName">
                <view class="label md">{{ $t('昵称') }}</view>
                <view class="md row" style="flex: 1">{{ userInfo.nickname }}</view>
                <u-icon name="arrow-right" />
            </view>
            <view class="row-info row bdb-line" @click="changeSex()">
                <view class="label md">{{ $t('性别') }}</view>
                <view class="md row" :class="userInfo.sex == 0 ? 'muted' : ''" style="flex: 1">
                    {{ userInfo.sex == 0 ? $t('未设置') : userInfo.sex == 1 ? $t('男') : $t('女') }}
                </view>
                <u-icon name="arrow-right" />
            </view>
            <view class="row-info row bdb-line">
                <view class="label md">{{ $t('手机') }}</view>
                <view class="md row" :class="{ muted: !userInfo.mobile }" style="flex: 1">
                    {{ userInfo.mobile ? userInfo.mobile : $t('未绑定') }}
                </view>

                <view class="bd-btn br60 row-center" @click="showModifyMobile">
                    {{ userInfo.mobile ? $t('更换手机号') : $t('绑定手机号') }}
                </view>

                <!-- #ifdef MP-WEIXIN -->
                <!-- <button
                    class="bd-btn br60 row-center"
                    size="sm"
                    open-type="getPhoneNumber"
                    @getphonenumber="getPhoneNumber"
                >
                    {{ userInfo.mobile ? '更换手机号' : '绑定手机号' }}
                </button> -->
                <!-- #endif -->
            </view>
            <view class="row-info row bdb-line">
                <view class="label md">{{ $t('注册时间') }} </view>
                <view class="md row" style="flex: 1">{{ userInfo.create_time }}</view>
            </view>
            <view class="row-info row bdb-line">
                <view class="label md">{{ $t('语言') }}</view>
                <view class="md row" style="flex: 1">
                    <view :class="curLocale === 'vi' ? 'primary' : 'muted'" @click="changeLocale('vi')">Tiếng Việt</view>
                    <view class="muted" style="margin: 0 16rpx">/</view>
                    <view :class="curLocale === 'zh' ? 'primary' : 'muted'" @click="changeLocale('zh')">中文</view>
                </view>
            </view>
        </view>
        <u-popup type="center" closeable v-model:show="showMobile" mode="center" round="14">
            <view class="modify-container column-center bg-white" v-show="showMobile">
                <view class="title xl">{{ userInfo.mobile ? $t('更换手机号') : $t('绑定手机号') }}</view>
                <view class="modify-row row" v-if="userInfo.mobile">
                    <view style="width: 56px; border-right: 1px solid #e5e5e5">+86</view>
                    <view style="margin-left: 15px">{{ userInfo.mobile }}</view>
                </view>
                <view class="modify-row row" v-else>
                    <view style="width: 71px">{{ $t('手机号') }}</view>
                    <input v-model="new_mobile" :placeholder="$t('请输入绑定手机号')" />
                </view>
                <view class="modify-row row">
                    <view style="width: 71px">{{ $t('验证码') }}</view>
                    <input
                        v-model="smsCode"
                        style="padding-left: 5px; width: 130px"
                        :placeholder="$t('请输入验证码')"
                    />
                    <view class="send-code-btn nr row-center" @click="$sendSms">
                        <u-code
                            :keep-running="true"
                            ref="uCode"
                            @change="codeChange"
                            unique-key="page-b"
                        >
                        </u-code>
                        <view class="xs">{{ tips }}</view>
                    </view>
                </view>
                <view class="modify-row row" v-if="userInfo.mobile">
                    <view style="width: 71px">{{ $t('新手机号') }}</view>
                    <input v-model="new_mobile" :placeholder="$t('请输入新的手机号码')" />
                </view>
                <view class="primary mt10"
                    >{{
                        userInfo.mobile ? $t('更改') : $t('绑定')
                    }}手机号码成功后，您的账号将会变更为该设置号码</view
                >
                <view class="btn bg-primary white row-center" @click="$changeUserMobile">{{ $t('确定') }}</view>
            </view>
        </u-popup>

        <!-- 昵称修改组件 -->
        <u-popup
            v-model:show="showNickName"
            :closeable="true"
            :close-on-click-overlay="false"
            mode="center"
            round="14"
        >
            <view
                class="modify-container column-center bg-white"
                style="width: 70vw; padding: 24rpx"
            >
                <view class="title xl">{{ $t('修改用户名') }}</view>
                <form @submit="changeNameConfirm">
                    <u-form-item :label="$t('新昵称')" :labelWidth="120">
                        <input
                            style="height: 60rpx"
                            class="nr"
                            :value="userInfo.nickname"
                            name="nickname"
                            type="nickname"
                            :placeholder="$t('请输入新的昵称')"
                        />
                    </u-form-item>
                    <button class="btn bg-primary white row-center" form-type="submit">{{ $t('确定') }}</button>
                </form>
            </view>
        </u-popup>
        <u-popup v-model:show="showPwd" closeable mode="center" round="14">
            <view class="modify-container column-center bg-white" v-show="showPwd">
                <view class="title xl">{{ $t('设置密码') }}</view>
                <view class="modify-row row">
                    <view style="width: 56px; border-right: 1px solid #e5e5e5">+86</view>
                    <view style="margin-left: 15px">{{ userInfo.mobile }}</view>
                </view>
                <view class="modify-row row">
                    <view style="width: 142rpx">{{ $t('图形验证码') }}</view>
                    <input
                        v-model="captchaCode"
                        style="padding-left: 10rpx; width: 130rpx"
                        :placeholder="$t('请输入')"
                    />
                    <image
                        class="captcha-img"
                        :src="captchaImg"
                        mode="aspectFit"
                        @click="getCaptchaImg"
                    />
                </view>
                <view class="modify-row row">
                    <view style="width: 142rpx">{{ $t('验证码') }}</view>
                    <input
                        v-model="smsCode"
                        style="padding-left: 10rpx; width: 260rpx"
                        :placeholder="$t('请输入验证码')"
                    />
                    <view class="send-code-btn nr row-center" @click="$sendSms">
                        <u-code
                            :keep-running="true"
                            ref="uCode"
                            @change="codeChange"
                            unique-key="page-a"
                        >
                        </u-code>
                        <view class="xs">{{ tips }}</view>
                    </view>
                </view>
                <view class="modify-row row">
                    <view style="width: 71px">{{ $t('设置密码') }}</view>
                    <input type="password" v-model="pwd" :placeholder="$t('请输入新密码')" />
                </view>
                <view class="modify-row row">
                    <view style="width: 71px">{{ $t('确认密码') }}</view>
                    <input type="password" v-model="comfirmPwd" :placeholder="$t('再次输入新密码确认')" />
                </view>
                <view class="btn bg-primary white row-center" @click="$forgetPwd">{{ $t('确定') }}</view>
            </view>
        </u-popup>
        <u-picker
            v-model:show="showPicker"
            :columns="[sexList]"
            :default-index="[0]"
            @confirm="onConfirm"
        />
    </view>
</template>

<script>
import { userLogout, getUserInfo, changeUserMobile, setUserInfo, setWechatInfo } from '@/api/user'
import { version } from '@/config/app'
import { sendSms, forgetPwd, getCaptcha } from '@/api/app'
import { SMSType } from '@/utils/type'
import { mapState, mapGetters } from 'vuex'
import { uploadFile, isWeixinClient, trottle } from '@/utils/tools'
import { getLocale, setLocale } from '@/utils/i18n'
import { getWxCode, getUserProfile } from '@/utils/login'

const FieldType = {
    NONE: '',
    SEX: 'sex',
    NICKNAME: 'nickname',
    AVATAR: 'avatar',
    MOBILE: 'mobile'
}
export default {
    name: 'userProfile',
    data() {
        return {
            version: version,
            fileList: [],
            userInfo: {},
            new_mobile: '',
            smsCode: '',
            newNickname: '',
            sexList: [this.$t('男'), this.$t('女')],
            curLocale: getLocale(),
            fieldType: FieldType.NONE,
            showPicker: false,
            showMobile: false,
            showPwd: false,
            showNickName: false,
            tips: '',
            canSendSms: true,
            pwd: '',
            comfirmPwd: '',
            smsType: SMSType.FINDPWD,
            code: '',
            captchaCode: '',
            captchaImg: '',
            captchaKey: ''
        }
    },
    methods: {
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
        codeChange(text) {
            this.tips = text
        },
        onChooseAvatar(e) {
            this.fieldType = FieldType.AVATAR
            // #ifndef MP-WEIXIN
            // 此为uView的跳转方法，详见"文档-JS"部分，也可以用uni的uni.navigateTo
            uni.$u.route({
                // 关于此路径，请见下方"注意事项"
                url: '/pages/avatar_cropper/avatar_cropper',
                // 内部已设置以下默认参数值，可不传这些参数
                params: {
                    // 输出图片宽度，高等于宽，单位px
                    destWidth: 300,
                    // 裁剪框宽度，高等于宽，单位px
                    rectWidth: 200,
                    // 输出的图片类型，如果'png'类型发现裁剪的图片太大，改成"jpg"即可
                    fileType: 'jpg'
                }
            })
            // #endif
            // #ifdef MP-WEIXIN
            if (e.detail.avatarUrl) {
                console.log(e.detail.avatarUrl)
                this.uploadImage(e.detail.avatarUrl)
            }
            // #endif
        },
        // 修改用户昵称
        async changeNameConfirm(e) {
            this.fieldType = FieldType.NICKNAME
            this.newNickname = e.detail.value.nickname
            if (!this.newNickname)
                return this.$toast({
                    title: this.$t('请输入新的昵称')
                })
            await this.$setUserInfo(this.newNickname)
            this.showNickName = false
        },

        // end
        logout() {
            //  退出登录
            userLogout({
                token: this.token
            }).then((res) => {
                if (res.code == 1) {
                    this.$store.commit('LOGOUT')
                    this.$toast({
                        title: this.$t('退出成功')
                    })
                    setTimeout(() => {
                        uni.redirectTo({
                            url: '/pages/login/login'
                        })
                    }, 500)
                }
            })
        },
        goToExplain(value) {
            uni.navigateTo({
                url: '/bundle/pages/server_explan/server_explan?type=' + value
            })
        },
        goLicense() {
            uni.navigateTo({
                url: '/bundle/pages/license/license'
            })
        },

        toSetPayPwd() {
            if (!this.userInfo.mobile)
                return this.$toast({
                    title: this.$t('请先设置手机号')
                })
            uni.navigateTo({
                url: '/bundle/pages/set_pay_pwd/set_pay_pwd'
            })
        },
        // 发送短信
        $sendSms(type) {
            if (!this.canSendSms) return
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
                mobile: this.userInfo.mobile || this.new_mobile,
                key: this.smsType,
                captcha_key: this.captchaKey,
                captcha: this.captchaCode
            }).then((res) => {
                if (res.code == 1) {
                    this.$toast({
                        title: res.msg
                    })
                    this.$refs.uCode.start()
                } else {
                    this.getCaptchaImg()
                    this.captchaCode = ''
                }
            }).catch(() => {
                this.getCaptchaImg()
                this.captchaCode = ''
            })
        },
        $getUserInfo() {
            getUserInfo().then((res) => {
                if (res.code == 1) {
                    this.userInfo = res.data
                }
            })
        },
        // 更换手机号
        showModifyMobile() {
            uni.navigateTo({ url: '/bundle/pages/bind_phone/bind_phone' })
            // this.smsCode = ''
            // this.new_mobile = ''
            // this.showMobile = true
            // this.smsType = this.userInfo.mobile ? SMSType.CHANGE_MOBILE : SMSType.BIND
        },
        $changeUserMobile() {
            if (!this.smsCode) {
                this.$toast({
                    title: this.$t('请输入验证码')
                })
                return
            }
            if (!this.new_mobile) {
                this.$toast({
                    title: this.$t('请输入新的手机号码')
                })
                return
            }
            changeUserMobile({
                mobile: this.userInfo.mobile,
                new_mobile: this.new_mobile,
                code: this.smsCode,
                action: this.userInfo.mobile ? 'change' : ''
            }).then((res) => {
                if (res.code == 1) {
                    this.showMobile = false
                    this.$toast({
                        title: res.msg
                    })
                    this.$getUserInfo()
                }
            })
        },
        // end
        // 修改用户信息
        async $setUserInfo(value) {
            const res = await setUserInfo({
                field: this.fieldType,
                value: value
            })
            if (res.code == 1) {
                this.$toast({
                    title: res.msg
                })
                this.$getUserInfo()
            }
        },
        // end
        timeChange(timestamp) {
            console.log(timestamp, 'timestamp')
        },
        onConfirm(e) {
            this.$setUserInfo(e.indexs[0] + 1)
            this.showPicker = false
        },
        changeSex(e) {
            this.showPicker = true
            this.fieldType = FieldType.SEX
        },
        changeLocale(locale) {
            this.curLocale = setLocale(locale)
        },

        // 定时器完成
        countDownFinish() {
            this.canSendSms = true
        },
        // 修改密码
        showPwdPop() {
            if (!this.userInfo.mobile) {
                this.$toast({
                    title: this.$t('请绑定手机后再设置密码')
                })
                return
            }
            this.smsCode = ''
            this.smsType = SMSType.FINDPWD
            this.showPwd = true
            this.getCaptchaImg()
        },
        $forgetPwd() {
            let { smsCode, pwd, comfirmPwd } = this
            if (!smsCode) {
                this.$toast({
                    title: this.$t('请填写短信验证码')
                })
                return
            }
            if (!pwd) {
                this.$toast({
                    title: this.$t('请输入新密码')
                })
                return
            }
            if (!comfirmPwd) {
                this.$toast({
                    title: this.$t('再次输入新密码确认')
                })
                return
            }
            if (pwd != comfirmPwd) {
                this.$toast({
                    title: this.$t('两次密码输入不一致')
                })
                return
            }
            let data = {
                mobile: this.userInfo.mobile,
                code: smsCode,
                password: pwd,
                repassword: comfirmPwd
            }
            forgetPwd(data).then((res) => {
                if (res.code == 1) {
                    this.showPwd = false
                    this.$toast({
                        title: this.$t('设置密码成功')
                    })
                    this.$getUserInfo()
                }
            })
        },
        // end
        // 修改昵称
        changeName() {
            this.fieldType = FieldType.NICKNAME
            this.newNickname = ''
            this.showNickName = true
        },
        // end

        async getPhoneNumber(e) {
            const { encryptedData, iv } = e.detail
            let data = {
                code: this.code,
                encrypted_data: encryptedData,
                iv
            }
            this.fieldType = FieldType.MOBILE
            if (encryptedData) {
                this.$changeUserMobileMP(data)
            }
        },
        $changeUserMobileMP(data) {
            changeUserMobile(data).then((res) => {
                if (res.code == 1) {
                    this.$toast({
                        title: res.msg
                    })
                    this.$getUserInfo()
                }
                // #ifdef MP-WEIXIN
                getWxCode().then((res) => {
                    this.code = res
                })
                // #endif
            })
        },
        uploadImage(path) {
            uni.showLoading({
                title: this.$t('正在上传中...'),
                mask: true
            })
            uploadFile(path)
                .then((res) => {
                    uni.hideLoading()
                    this.$setUserInfo(res.url)
                })
                .catch(() => {
                    uni.hideLoading()
                    this.$toast({
                        title: this.$t('上传失败')
                    })
                })
        }
    },
    onLoad() {
        this.$getUserInfo()
        // #ifdef MP-WEIXIN
        getWxCode().then((res) => {
            this.code = res
        })
        // #endif
        // 监听从裁剪页发布的事件，获得裁剪结果
        uni.$on('uAvatarCropper', (path) => {
            console.log(path)
            this.uploadImage(path)
        })
        this.getUserProfile = trottle(this.getUserProfile, 500, this)
    },
    onUnload() {
        uni.$off('uAvatarCropper')
    },
    computed: {
        ...mapState(['token']),
        ...mapGetters(['appConfig'])
    }
}
</script>

<style lang="scss">
.user-profile-container {
    .user-profile {
        border-top-left-radius: 28rpx;
        border-top-right-radius: 28rpx;

        .user-avatar-box {
            padding: 30rpx;
            background-color: white;
            border-top-left-radius: 28rpx;
            border-top-right-radius: 28rpx;

            .user-avatar {
                width: 120rpx;
                height: 120rpx;
                border-radius: 50%;
            }
        }

        .row-info {
            padding: 30rpx 20rpx;
            background-color: white;

            .label {
                width: 180rpx;
            }

            .bd-btn {
                padding: 8rpx 24rpx;
                border: 1px solid $ls-color-primary;
                color: $ls-color-primary;
            }
        }

        .license {
            margin-top: 80rpx;
            color: #a7a7a7;
            text-align: center;
        }

        .bdb-line {
            border-bottom: 1rpx solid #e5e5e5;
        }

        .save-btn {
            margin-top: 40rpx;
            height: 88rpx;
            margin-left: 54rpx;
            margin-right: 54rpx;
            border-radius: 10rpx;
            box-sizing: border-box;
        }

        .updata-btn {
            margin: 0 30rpx;
            margin-top: 40rpx;
            background-color: #0cc267;
        }
    }

    .modify-container {
        padding-left: 30rpx;
        padding-right: 30rpx;
        padding-bottom: 30rpx;
        width: 580rpx;
        border-radius: 30rpx;
        background-color: $ls-color-white;

        .title {
            padding: 26rpx 0rpx;
        }

        .modify-row {
            padding: 32rpx 0rpx;
            width: 100%;
            border-bottom: 1rpx solid #e5e5e5;

            .send-code-btn {
                border: 1px solid $ls-color-primary;
                width: 184rpx;
                height: 62rpx;
                color: $ls-color-primary;
            }
            .captcha-img {
                width: 210rpx;
                height: 90rpx;
                margin-left: 20rpx;
            }
        }

        .btn {
            height: 80rpx;
            padding: 0 180rpx;
            border-radius: 20rpx;
            margin-top: 60rpx;
        }
    }
}
</style>
