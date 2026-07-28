<?php
// تأكد من تشغيل: composer install قبل تنفيذ هذا الملف أول مرة
require 'vendor/autoload.php';

try {
    // الاتصال بخادم Redis المحلي
    $redis = new Predis\Client('tcp://127.0.0.1:6379');

    // 1. الأقسام
    $categories = [
        '1' => '🥛 قسم الألبان والأجبان',
        '2' => '🍞 قسم المخبوزات',
        '3' => '🥫 قسم المعلبات'
    ];

    // 2. المنتجات
    $products = [
        '1' => [
            ['id' => 'p1', 'name' => 'حليب المراعي 1 لتر', 'price' => 2000, 'desc' => 'حليب بقري طازج'],
            ['id' => 'p2', 'name' => 'جبنة كيري', 'price' => 4500, 'desc' => 'علبة 12 قطعة']
        ],
        '2' => [
            ['id' => 'p3', 'name' => 'خبز صمون عراقي', 'price' => 1000, 'desc' => '8 قطع طازجة'],
            ['id' => 'p4', 'name' => 'كيك العائلة', 'price' => 3000, 'desc' => 'محشي بالشوكولاتة']
        ],
        '3' => [
            ['id' => 'p5', 'name' => 'تونة معلبة', 'price' => 2500, 'desc' => 'لحم تونة خفيف'],
            ['id' => 'p6', 'name' => 'معجون طماطم', 'price' => 1500, 'desc' => 'علبة 400 غرام']
        ]
    ];

    // حفظ في Redis
    $redis->set('supermarket_categories', json_encode($categories, JSON_UNESCAPED_UNICODE));
    $redis->set('supermarket_products', json_encode($products, JSON_UNESCAPED_UNICODE));

    echo "✅ تم تحديث بيانات السوبر ماركت بنجاح!\n";

} catch (Exception $e) {
    echo "❌ حدث خطأ: " . $e->getMessage() . "\n";
}
