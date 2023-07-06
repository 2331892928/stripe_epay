#stripe易支付插件
此插件为易支付插件。为stripe支付，stripe官网：https://stripe.com

支持：微信支付/支付宝支付

将stripe上传到易支付网站：/plugins/下

镜像站：默认空为官方链接，由于stripe国外，防止用户无法访问(不是你机器访问，是用户访问，用户需要访问链接前往支付地址)，你可以反代或镜像站：https://checkout.stripe.com 也可以自定义域;便不用反代，见：https://stripe.com/docs/payments/checkout/custom-domains

产品ID：在stripe后台添加产品，产品价格为：0.01，货币随意，一次性，只能添加一种价格并设置默认价格。添加完成后进入产品即可看见产品id

汇率：默认1.08。易只支持人民币，所以需要转换货币汇率。这里输入1人民币=多少你的产品货币。比如你的产品货币是HKD，那么1人民币=1.08港币，这里填1.08

更多使用教程：https://blog.ymypay.cn/index.php/2023/07/06/ypay_stripe/