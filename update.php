<?php
// هذا الملف يقوم بتشغيل أمر Git Pull برمجياً للتحديث اليدوي
echo "<html dir='rtl'><body style='font-family: Arial; padding: 20px;'>";
echo "<h2>🔄 جاري سحب التحديثات من GitHub...</h2>";

// تنفيذ أمر سحب التحديثات
$output = shell_exec('git pull origin main 2>&1');

// عرض النتيجة
echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 5px; direction: ltr; text-align: left;'>$output</pre>";
echo "<h3>✅ تمت العملية! (لا تنسَ إعادة تشغيل البوت إذا كان التعديل في ملف bot.js)</h3>";
echo "</body></html>";
?>
