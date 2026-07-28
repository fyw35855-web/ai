<?php
$output = '';
$status_msg = '';

// التحقق مما إذا تم الضغط على زر "تحديث الآن"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_update'])) {
    
    // تحديد مسار المجلد الحالي لضمان تنفيذ الأمر في المكان الصحيح
    $project_dir = __DIR__;
    
    // تنفيذ الأمر مع سحب الأخطاء إن وجدت (2>&1)
    $command = "cd {$project_dir} && git pull origin main 2>&1";
    $output = shell_exec($command);
    
    $status_msg = "✅ تمت العملية بنجاح! راجع التفاصيل في الأسفل.";
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث النظام من GitHub</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            padding: 40px 20px;
            margin: 0;
            display: flex;
            justify-content: center;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
        }
        h2 {
            margin-top: 0;
            color: #1a73e8;
            border-bottom: 2px solid #e8eaed;
            padding-bottom: 15px;
        }
        .btn {
            background-color: #1a73e8;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: block;
            width: 100%;
            font-weight: bold;
        }
        .btn:hover {
            background-color: #1557b0;
        }
        .terminal {
            background-color: #202124;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            direction: ltr;
            text-align: left;
            font-family: 'Courier New', Courier, monospace;
            overflow-x: auto;
            margin-top: 20px;
            line-height: 1.5;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            background-color: #e6f4ea;
            color: #137333;
            border-radius: 5px;
            font-weight: bold;
        }
        .notes {
            margin-top: 20px;
            font-size: 14px;
            color: #5f6368;
            background: #f8f9fa;
            padding: 10px;
            border-right: 4px solid #fbbc04;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>🔄 لوحة تحديث النظام (GitHub)</h2>
        
        <p>اضغط على الزر أدناه لسحب أحدث التعديلات من مستودعك على كيت هاب (ai.git) وتطبيقها على السيرفر.</p>

        <form method="POST">
            <button type="submit" name="run_update" class="btn">🚀 بدء تحديث النظام الآن</button>
        </form>

        <?php if ($status_msg): ?>
            <div class="status">
                <?php echo $status_msg; ?>
            </div>
            
            <div class="terminal">
                <pre><?php echo htmlspecialchars($output); ?></pre>
            </div>

            <div class="notes">
                <strong>⚠️ ملاحظات هامة بعد التحديث:</strong>
                <ul>
                    <li>إذا قمت بتعديل المنتجات (ملف <code>admin.php</code>)، يجب تشغيله عبر موجه الأوامر: <code dir="ltr">php admin.php</code></li>
                    <li>إذا قمت بتعديل كود البوت (ملف <code>bot.js</code>)، يجب إيقافه وإعادة تشغيله من جديد: <code dir="ltr">node bot.js</code></li>
                </ul>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
