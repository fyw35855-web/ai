const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const { createClient } = require('redis');

// إعداد اتصال Redis
const redis = createClient({ url: 'redis://127.0.0.1:6379' });
redis.on('error', (err) => console.error('❌ Redis Client Error:', err));

// الاحتفاظ بحالة المستخدمين (الجلسات)
const userSessions = {};

// تهيئة عميل واتساب مع حفظ الجلسة محلياً
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: { args: ['--no-sandbox', '--disable-setuid-sandbox'] }
});

client.on('qr', (qr) => {
    // محاولة طباعة الباركود في الشاشة (للشاشات العريضة)
    qrcode.generate(qr, { small: true });

    // الحل السحري: إنشاء رابط صورة للباركود (للشاشات الصغيرة والتلفون)
    console.log('\n=========================================');
    console.log('📱 إذا كان الباركود مشوهاً، اضغط على الرابط التالي لفتحه كصورة واضحة:');
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent(qr)}`;
    console.log(qrUrl);
    console.log('=========================================\n');
});

client.on('ready', async () => {
    await redis.connect();
    console.log('✅ تم الاتصال بواتساب وبسيرفر Redis بنجاح! النظام جاهز.');
});

client.on('message', async (message) => {
    if (message.from.includes('@g.us')) return; // تجاهل الجروبات

    const sender = message.from;
    const text = message.body.trim();

    if (!userSessions[sender]) {
        userSessions[sender] = { step: 0, cart: [], selectedCategory: null };
    }

    const session = userSessions[sender];

    try {
        const categoriesRaw = await redis.get('supermarket_categories');
        const productsRaw = await redis.get('supermarket_products');
        const categories = JSON.parse(categoriesRaw || '{}');
        const products = JSON.parse(productsRaw || '{}');

        // العودة للرئيسية أو رسالة البداية
        if (text === '0' || text.toLowerCase() === 'مرحبا' || session.step === 0) {
            let reply = "🛒 *مرحباً بك في سوبر ماركتنا!*\n\nيرجى إرسال *رقم القسم* لعرض المنتجات:\n\n";
            for (const [key, name] of Object.entries(categories)) {
                reply += `*[ ${key} ]* - ${name}\n`;
            }
            reply += "\n0️⃣ للعودة للقائمة الرئيسية في أي وقت.";
            
            session.step = 1;
            await client.sendMessage(sender, reply);
            return;
        }

        // اختيار القسم
        if (session.step === 1) {
            if (categories[text]) {
                session.selectedCategory = text;
                const categoryProducts = products[text] || [];
                
                if (categoryProducts.length === 0) {
                    await client.sendMessage(sender, "⚠️ هذا القسم فارغ حالياً. أرسل 0 للعودة.");
                    return;
                }

                let reply = `📦 *${categories[text]}*\nيرجى إرسال *رقم المنتج* لإضافته للسلة:\n\n`;
                categoryProducts.forEach((prod, index) => {
                    reply += `*[ ${index + 1} ]* - ${prod.name}\n`;
                    reply += `     السعر: ${prod.price} دينار\n`;
                    reply += `     الوصف: ${prod.desc}\n\n`;
                });
                reply += "0️⃣ للعودة للأقسام.";

                session.step = 2;
                await client.sendMessage(sender, reply);
            } else {
                await client.sendMessage(sender, "⚠️ رقم القسم غير صحيح. أرسل رقماً صحيحاً أو 0 للعودة.");
            }
            return;
        }

        // اختيار المنتج وإضافته للسلة
        if (session.step === 2) {
            const categoryProducts = products[session.selectedCategory] || [];
            const selectedIndex = parseInt(text) - 1;

            if (categoryProducts[selectedIndex]) {
                const selectedProduct = categoryProducts[selectedIndex];
                session.cart.push(selectedProduct);

                let reply = `✅ تم إضافة *${selectedProduct.name}* إلى السلة.\n\n`;
                reply += "ماذا تود أن تفعل الآن؟\n";
                reply += "*[ 1 ]* الاستمرار بالتسوق (نفس القسم)\n";
                reply += "*[ 2 ]* العودة للأقسام الرئيسية\n";
                reply += "*[ 3 ]* 🛒 إنهاء الطلب وإرسال الفاتورة";

                session.step = 3;
                await client.sendMessage(sender, reply);
            } else {
                await client.sendMessage(sender, "⚠️ رقم المنتج غير صحيح. أرسل رقماً صحيحاً أو 0 للعودة.");
            }
            return;
        }

        // خيارات السلة
        if (session.step === 3) {
            if (text === '1') {
                message.body = session.selectedCategory;
                session.step = 1;
                client.emit('message', message); 
            } else if (text === '2') {
                session.step = 0;
                message.body = '0';
                client.emit('message', message);
            } else if (text === '3') {
                if (session.cart.length === 0) {
                    await client.sendMessage(sender, "سلتك فارغة! أرسل 0 للعودة للتسوق.");
                    return;
                }

                let total = 0;
                let receipt = "🧾 *فاتورة طلبك:*\n\n";
                session.cart.forEach(item => {
                    receipt += `- ${item.name} (${item.price} دينار)\n`;
                    total += item.price;
                });
                receipt += `\n💰 *المجموع الكلي: ${total} دينار*\n\n`;
                receipt += "سيتم تجهيز طلبك قريباً. شكراً لتسوقك معنا! 🎉";

                await client.sendMessage(sender, receipt);
                userSessions[sender] = { step: 0, cart: [], selectedCategory: null };
            } else {
                await client.sendMessage(sender, "⚠️ خيار غير صحيح. أرسل 1 أو 2 أو 3.");
            }
            return;
        }

    } catch (error) {
        console.error("❌ حدث خطأ:", error);
    }
});

client.initialize();
