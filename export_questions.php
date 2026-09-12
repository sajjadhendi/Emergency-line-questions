<?php
require_once 'includes/db.php'; // ملف الاتصال بقاعدة البيانات الخاص بك

// إرسال الترويسات لفتح الملف وتحميله كملف إكسل/CSV يدعم اللغة العربية
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=emergency_matrix_questions_export.csv');

// فتح منفذ الإخراج
$output = fopen('php://output', 'w');

// إضافة UTF-8 BOM لكي يتعرف برنامج Excel على الحروف العربية بشكل صحيح تماماً دون رموز غريبة
fwrite($output, "\xEF\xBB\xBF");

// كتابة صف العناوين (الأعمدة الرئيسية)
fputcsv($output, [
    'ت',
    'اسم البروتوكول',
    'رمز البروتوكول',
    'معرف السؤال',
    'نص السؤال',
    'النص المساعد',
    'نقطة دخول؟',
    'خيار الإجابة',
    'نوع الإجراء',
    'الأولوية',
    'التعليمات / الأثر النهائي'
]);

// جلب كافة البروتوكولات والأسئلة والخيارات مرتبة من قاعدة البيانات
$sql = "
    SELECT 
        p.title AS protocol_title,
        p.code AS protocol_code,
        q.id AS question_id,
        q.question_text,
        q.helper_text,
        q.is_entry_point,
        qo.option_text,
        qo.action_type,
        qo.set_priority,
        COALESCE(qo.instruction_text, qo.impact_text) AS instruction_or_impact
    FROM questions q
    JOIN protocols p ON q.protocol_id = p.id
    LEFT JOIN question_options qo ON qo.question_id = q.id
    ORDER BY p.id, q.step_order, qo.sort_order
";

try {
    $stmt = Database::get()->query($sql);
    $rows = $stmt.fetchAll(PDO::FETCH_ASSOC); // أو fetchAll حسب إعدادات الـ PDO لديك
    
    $counter = 1;
    foreach ($rows as $row) {
        fputcsv($output, [
            $counter++,
            $row['protocol_title'],
            $row['protocol_code'],
            $row['question_id'],
            $row['question_text'],
            $row['helper_text'],
            $row['is_entry_point'] ? 'نعم' : 'كلا',
            $row['option_text'],
            $row['action_type'],
            $row['set_priority'],
            $row['instruction_or_impact']
        ]);
    }
} catch (Exception $e) {
    fputcsv($output, ['خطأ أثناء جلب البيانات: ' . $e->getMessage()]);
}

fclose($output);
exit;
