<?php
require_once 'includes/db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=emergency_matrix_tree_export.csv');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

// أعمدة مخصصة لتتبع شجرة القرارات بوضوح
fputcsv($output, [
    'ت',
    'اسم البروتوكول',
    'رقم السؤال (ID)',
    'نص السؤال',
    'خيارات الإجابة',
    'نوع الإجراء',
    'رقم السؤال التالي (Next ID)',
    'الأولوية (Priority)',
    'التعليمات أو الأثر النهائي'
]);

$sql = "
    SELECT 
        p.title AS protocol_title,
        q.id AS question_id,
        q.question_text,
        qo.option_text,
        qo.action_type,
        qo.next_question_id,
        qo.set_priority,
        COALESCE(qo.instruction_text, qo.impact_text) AS instruction_or_impact
    FROM questions q
    JOIN protocols p ON q.protocol_id = p.id
    LEFT JOIN question_options qo ON qo.question_id = q.id
    ORDER BY p.id, q.step_order, qo.sort_order
";

try {
    $stmt = Database::get()->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $counter = 1;
    foreach ($rows as $row) {
        fputcsv($output, [
            $counter++,
            $row['protocol_title'],
            $row['question_id'],
            $row['question_text'],
            $row['option_text'],
            $row['action_type'] === 'next_question' ? 'انتقال لسؤال آخر' : 'إنهاء وإعطاء تعليمات',
            $row['next_question_id'] ? $row['next_question_id'] : 'نهاية المسار',
            $row['set_priority'],
            $row['instruction_or_impact']
        ]);
    }
} catch (Exception $e) {
    fputcsv($output, ['خطأ: ' . $e->getMessage()]);
}

fclose($output);
exit;
