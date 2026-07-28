<?php
require 'vendor/autoload.php';

$success_msg = '';
$error_msg = '';

try {
    // الاتصال بخادم Redis
    $redis = new Predis\Client('tcp://127.0.0.1:6379');

    // جلب البيانات الحالية أو إنشاء مصفوفات فارغة إذا لم تكن موجودة
    $categories = json_decode($redis->get('supermarket_categories') ?? '{}', true);
    $products = json_decode($redis->get('supermarket_products') ?? '{}', true);

    // معالجة طلب إضافة قسم جديد
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
        $cat_name = trim($_POST['category_name']);
        if (!empty($cat_name)) {
            // إنشاء رقم تسلسلي جديد للقسم (مثلا: إذا كان أعلى رقم 3، الجديد يصير 4)
            $new_cat_id = count($categories) > 0 ? max(array_keys($categories)) + 1 : 1;
            $categories[$new_cat_id] = $cat_name;
            
            // تهيئة مصفوفة منتجات فارغة لهذا القسم
            $products[$new_cat_id] = []; 
            
            // الحفظ في Redis
            $redis->set('supermarket_categories', json_encode($categories, JSON_UNESCAPED_UNICODE));
            $redis->set('supermarket_products', json_encode($products, JSON_UNESCAPED_UNICODE));
            
            $success_msg = "✅ تم إضافة القسم بنجاح!";
        } else {
            $error_msg = "⚠️ يرجى كتابة اسم القسم.";
        }
    }

    // معالجة طلب إضافة منتج جديد
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
        $cat_id = $_POST['category_id'];
        $prod_name = trim($_POST['product_name']);
        $prod_price = trim($_POST['product_price']);
        $prod_desc = trim($_POST['product_desc']);

        if (!empty($cat_id) && !empty($prod_name) && !empty($prod_price)) {
            // توليد ID عشوائي للمنتج
            $new_prod_id = 'p_' . time(); 
            
            // إضافة المنتج للقسم المحدد
            $products[$cat_id][] = [
                'id' => $new_prod_id,
                'name' => $prod_name,
                'price' => (int)$prod_price,
                'desc' => $prod_desc
            ];

            // الحفظ في Redis
            $redis->set('supermarket_products', json_encode($products, JSON_UNESCAPED_UNICODE));
            
            $success_msg = "✅ تم إضافة المنتج بنجاح!";
        } else {
            $error_msg = "⚠️ يرجى تعبئة جميع بيانات المنتج الأساسية (الاسم، السعر، القسم).";
        }
    }

} catch (Exception $e) {
    $error_msg = "❌ خطأ في الاتصال بقاعدة البيانات Redis: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة السوبر ماركت</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #2980b9; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .section { margin-bottom: 40px; padding-bottom: 20px; border-bottom: 1px dashed #ccc; }
        .product-list { margin-top: 10px; background: #f9f9f9; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <h1>🛒 لوحة تحكم السوبر ماركت (تطبيق واتساب)</h1>

    <?php if ($success_msg): ?> <div class="alert alert-success"><?php echo $success_msg; ?></div> <?php endif; ?>
    <?php if ($error_msg): ?> <div class="alert alert-error"><?php echo $error_msg; ?></div> <?php endif; ?>

    <div class="section">
        <h2>➕ إضافة قسم جديد</h2>
        <form method="POST">
            <div class="form-group">
                <label>اسم القسم (مثال: قسم المشروبات):</label>
                <input type="text" name="category_name" required placeholder="اكتب اسم القسم هنا...">
            </div>
            <button type="submit" name="add_category">حفظ القسم</button>
        </form>
    </div>

    <div class="section">
        <h2>📦 إضافة منتج جديد</h2>
        <?php if (empty($categories)): ?>
            <p style="color: red;">⚠️ يجب إضافة قسم واحد على الأقل قبل إضافة المنتجات.</p>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>اختر القسم:</label>
                    <select name="category_id" required>
                        <option value="">-- اختر القسم --</option>
                        <?php foreach ($categories as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>اسم المنتج:</label>
                    <input type="text" name="product_name" required placeholder="مثال: بيبسي عائلي">
                </div>
                <div class="form-group">
                    <label>السعر (بالدينار):</label>
                    <input type="number" name="product_price" required placeholder="مثال: 1500">
                </div>
                <div class="form-group">
                    <label>الوصف (اختياري):</label>
                    <input type="text" name="product_desc" placeholder="مثال: مشروب غازي 2.25 لتر">
                </div>
                <button type="submit" name="add_product">حفظ المنتج</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>📊 الأقسام والمنتجات المتوفرة حالياً</h2>
        <?php if (!empty($categories)): ?>
            <ul>
                <?php foreach ($categories as $cat_id => $cat_name): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($cat_name); ?></strong>
                        <?php if (!empty($products[$cat_id])): ?>
                            <div class="product-list">
                                <ul>
                                    <?php foreach ($products[$cat_id] as $prod): ?>
                                        <li><?php echo htmlspecialchars($prod['name']); ?> - <strong><?php echo number_format($prod['price']); ?> دينار</strong> 
                                        <br><small style="color: gray;"><?php echo htmlspecialchars($prod['desc']); ?></small></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php else: ?>
                            <div class="product-list"><span style="color: gray;">لا توجد منتجات في هذا القسم حتى الآن.</span></div>
                        <?php endif; ?>
                    </li>
                    <hr>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>لا توجد بيانات حالياً. ابدأ بإضافة قسم جديد!</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
