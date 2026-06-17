<?php
/**
 * api.php — واجهة برمجية كاملة
 * جميع العمليات تمر عبر هذا الملف
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

require_once __DIR__ . '/includes/db.php';

// ─── دالات تنظيف مخصصة بديلة لمنع التضارب مع دالات النظام ────────
function cleanInt(mixed $v): ?int
{
    if ($v === null || $v === '') return null;
    $r = filter_var($v, FILTER_VALIDATE_INT);
    return $r !== false ? (int)$r : null;
}

function cleanStr(mixed $v, int $maxLen = 0): string
{
    $s = trim(stringable_value($v));
    if ($maxLen > 0 && mb_strlen($s) > $maxLen) {
        $s = mb_substr($s, 0, $maxLen);
    }
    return $s;
}

function stringable_value(mixed $v): string
{
    if (is_scalar($v) || (is_object($v) && method_exists($v, '__toString'))) {
        return (string)$v;
    }
    return '';
}

// ─── قراءة الطلب ────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = cleanStr($_GET['action'] ?? '', 100);

$body = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $body = $decoded;
    }
    foreach ($_POST as $k => $v) {
        if (!isset($body[$k])) $body[$k] = $v;
    }
}

// ─── توجيه الطلبات ──────────────────────────────────────────────
try {
    $pdo = db();
    switch ($action) {
        // قراءة
        case 'get_protocols':      getProtocols($pdo);      break;
        case 'get_protocol_tree':  getProtocolTree($pdo);   break;
        case 'get_question':       getQuestion($pdo);       break;
        case 'get_questions_list': getQuestionsList($pdo);  break;
        // كتابة
        case 'save_protocol':      saveProtocol($pdo,$body);  break;
        case 'save_question':      saveQuestion($pdo,$body);  break;
        case 'save_option':        saveOption($pdo,$body);    break;
        case 'set_entry_point':    setEntryPoint($pdo,$body); break;
        // حذف
        case 'delete_protocol':    deleteProtocol($pdo,$body); break;
        case 'delete_question':    deleteQuestion($pdo,$body); break;
        case 'delete_option':      deleteOption($pdo,$body);   break;
        // سجلات
        case 'save_session_log':   saveSessionLog($pdo,$body); break;
        default: jsonOut(false,null,"action غير معروف: $action",404);
    }
} catch (PDOException $e) {
    error_log('[API PDO] '.$e->getMessage());
    jsonOut(false,null,'خطأ في قاعدة البيانات',500);
} catch (Throwable $e) {
    error_log('[API] '.$e->getMessage());
    jsonOut(false,null,'خطأ في الخادم',500);
}

// ════════════════════════════════════════════════════════════════
// GET: جلب البروتوكولات
// ════════════════════════════════════════════════════════════════
function getProtocols(PDO $pdo): never
{
    $activeOnly = (int)($_GET['active_only'] ?? 1);
    $ageGroup   = cleanStr($_GET['age_group'] ?? '', 20);

    $sql = "SELECT id,code,title,description,priority_level,age_group,
                   color_code,icon,sort_order,is_active
            FROM protocols WHERE 1=1";
    $p = [];

    if ($activeOnly) { $sql .= " AND is_active=1"; }

    if ($ageGroup && in_array($ageGroup,['adult','pediatric','all'],true)) {
        $sql .= " AND (age_group=:ag OR age_group='all')";
        $p[':ag'] = $ageGroup;
    }
    $sql .= " ORDER BY sort_order ASC, id ASC";

    $s = $pdo->prepare($sql); $s->execute($p);
    jsonOut(true, $s->fetchAll());
}

// ════════════════════════════════════════════════════════════════
// GET: جلب شجرة بروتوكول كامل (البروتوكول + نقطة الدخول)
// ════════════════════════════════════════════════════════════════
function getProtocolTree(PDO $pdo): never
{
    $pid = cleanInt($_GET['protocol_id'] ?? null);
    if (!$pid) jsonOut(false,null,'protocol_id مطلوب',400);

    $s = $pdo->prepare("SELECT id,code,title,description,priority_level,color_code,icon
                         FROM protocols WHERE id=:id AND is_active=1 LIMIT 1");
    $s->execute([':id'=>$pid]);
    $proto = $s->fetch();
    if (!$proto) jsonOut(false,null,'البروتوكول غير موجود',404);

    // نقطة الدخول
    $s2 = $pdo->prepare("SELECT id,question_text,helper_text,is_mandatory,step_order,is_entry_point
                          FROM questions WHERE protocol_id=:pid AND is_entry_point=1 LIMIT 1");
    $s2->execute([':pid'=>$pid]);
    $entry = $s2->fetch();

    if (!$entry) {
        // fallback: أول سؤال بالترتيب
        $s3 = $pdo->prepare("SELECT id,question_text,helper_text,is_mandatory,step_order,is_entry_point
                              FROM questions WHERE protocol_id=:pid
                              ORDER BY step_order ASC, id ASC LIMIT 1");
        $s3->execute([':pid'=>$pid]);
        $entry = $s3->fetch();
    }

    if ($entry) {
        $entry['options'] = getOptions($pdo, (int)$entry['id']);
    }

    jsonOut(true, ['protocol'=>$proto, 'entry_question'=>$entry ?: null]);
}

// ════════════════════════════════════════════════════════════════
// GET: جلب سؤال واحد مع خياراته
// ════════════════════════════════════════════════════════════════
function getQuestion(PDO $pdo): never
{
    $qid = cleanInt($_GET['question_id'] ?? null);
    if (!$qid) jsonOut(false,null,'question_id مطلوب',400);

    $s = $pdo->prepare("SELECT q.id, q.protocol_id, q.question_text, q.helper_text,
                                q.is_mandatory, q.step_order, q.is_entry_point,
                                p.title AS protocol_title, p.color_code
                         FROM questions q
                         JOIN protocols p ON p.id=q.protocol_id
                         WHERE q.id=:id LIMIT 1");
    $s->execute([':id'=>$qid]);
    $q = $s->fetch();
    if (!$q) jsonOut(false,null,'السؤال غير موجود',404);

    $q['options'] = getOptions($pdo, $qid);
    jsonOut(true, $q);
}

// ════════════════════════════════════════════════════════════════
// GET: قائمة أسئلة بروتوكول (للـ Admin)
// ════════════════════════════════════════════════════════════════
function getQuestionsList(PDO $pdo): never
{
    $pid = cleanInt($_GET['protocol_id'] ?? null);
    if (!$pid) jsonOut(false,null,'protocol_id مطلوب',400);

    $s = $pdo->prepare("SELECT q.id, q.question_text, q.helper_text,
                                q.is_mandatory, q.step_order, q.is_entry_point,
                                COUNT(o.id) AS options_count
                         FROM questions q
                         LEFT JOIN question_options o ON o.question_id=q.id
                         WHERE q.protocol_id=:pid
                         GROUP BY q.id
                         ORDER BY q.step_order ASC, q.id ASC");
    $s->execute([':pid'=>$pid]);
    $rows = $s->fetchAll();

    foreach ($rows as &$r) {
        $r['options'] = getOptions($pdo, (int)$r['id']);
    }
    jsonOut(true, $rows);
}

// ════════════════════════════════════════════════════════════════
// POST: حفظ بروتوكول
// ════════════════════════════════════════════════════════════════
function saveProtocol(PDO $pdo, array $d): never
{
    $id       = cleanInt($d['id'] ?? null);
    $code     = cleanStr($d['code'] ?? '',20);
    $title    = cleanStr($d['title'] ?? '',255);
    $desc     = cleanStr($d['description'] ?? '',2000);
    $priority = cleanStr($d['priority_level'] ?? 'Delta', 20);
    $ag       = cleanStr($d['age_group'] ?? 'all', 20);
    $color    = cleanStr($d['color_code'] ?? '#E53E3E',7);
    $icon     = cleanStr($d['icon'] ?? '',50);
    $active   = isset($d['is_active']) ? (int)(bool)$d['is_active'] : 1;
    $sort     = cleanInt($d['sort_order'] ?? 0) ?? 0;

    if (!$code || !$title) jsonOut(false,null,'الرمز والعنوان إلزاميان',400);
    if (!in_array($priority,['Echo','Delta','Charlie','Bravo','Alpha'],true))
        jsonOut(false,null,'مستوى أولوية غير صحيح',400);
    if (!in_array($ag,['adult','pediatric','all'],true))
        jsonOut(false,null,'فئة عمرية غير صحيحة',400);

    if ($id) {
        $pdo->prepare("UPDATE protocols SET code=:c,title=:t,description=:d,priority_level=:pr,
                                age_group=:ag,color_code=:co,icon=:ic,is_active=:ac,sort_order=:so
                        WHERE id=:id")
            ->execute([':c'=>$code,':t'=>$title,':d'=>$desc,':pr'=>$priority,
                       ':ag'=>$ag,':co'=>$color,':ic'=>$icon,':ac'=>$active,':so'=>$sort,':id'=>$id]);
        jsonOut(true,['id'=>$id],'تم التحديث');
    } else {
        $pdo->prepare("INSERT INTO protocols (code,title,description,priority_level,age_group,color_code,icon,is_active,sort_order)
                        VALUES (:c,:t,:d,:pr,:ag,:co,:ic,:ac,:so)")
            ->execute([':c'=>$code,':t'=>$title,':d'=>$desc,':pr'=>$priority,
                       ':ag'=>$ag,':co'=>$color,':ic'=>$icon,':ac'=>$active,':so'=>$sort]);
        jsonOut(true,['id'=>(int)$pdo->lastInsertId()],'تم الإنشاء');
    }
}

// ════════════════════════════════════════════════════════════════
// POST: حفظ سؤال
// ════════════════════════════════════════════════════════════════
function saveQuestion(PDO $pdo, array $d): never
{
    $id       = cleanInt($d['id'] ?? null);
    $pid      = cleanInt($d['protocol_id'] ?? null);
    $text     = cleanStr($d['question_text'] ?? '',2000);
    $helper   = cleanStr($d['helper_text'] ?? '',2000);
    $mand     = isset($d['is_mandatory'])   ? (int)(bool)$d['is_mandatory']   : 1;
    $entry    = isset($d['is_entry_point']) ? (int)(bool)$d['is_entry_point'] : 0;
    $order    = cleanInt($d['step_order'] ?? 0) ?? 0;

    if (!$pid || !$text) jsonOut(false,null,'protocol_id ونص السؤال إلزاميان',400);

    // تحقق من وجود البروتوكول
    $chk = $pdo->prepare("SELECT id FROM protocols WHERE id=:id LIMIT 1");
    $chk->execute([':id'=>$pid]);
    if (!$chk->fetch()) jsonOut(false,null,'البروتوكول غير موجود',404);

    // إذا entry_point → أزل الإشارة من باقي أسئلة البروتوكول
    if ($entry) {
        $pdo->prepare("UPDATE questions SET is_entry_point=0 WHERE protocol_id=:pid")
            ->execute([':pid'=>$pid]);
    }

    if ($id) {
        $pdo->prepare("UPDATE questions SET question_text=:t,helper_text=:h,is_mandatory=:m,
                        step_order=:o,is_entry_point=:e WHERE id=:id AND protocol_id=:pid")
            ->execute([':t'=>$text,':h'=>$helper,':m'=>$mand,':o'=>$order,':e'=>$entry,
                       ':id'=>$id,':pid'=>$pid]);
        jsonOut(true,['id'=>$id],'تم التحديث');
    } else {
        $pdo->prepare("INSERT INTO questions (protocol_id,question_text,helper_text,is_mandatory,step_order,is_entry_point)
                        VALUES (:pid,:t,:h,:m,:o,:e)")
            ->execute([':pid'=>$pid,':t'=>$text,':h'=>$helper,':m'=>$mand,':o'=>$order,':e'=>$entry]);
        jsonOut(true,['id'=>(int)$pdo->lastInsertId()],'تم الإنشاء');
    }
}

// ════════════════════════════════════════════════════════════════
// POST: حفظ خيار
// ════════════════════════════════════════════════════════════════
function saveOption(PDO $pdo, array $d): never
{
    $id          = cleanInt($d['id'] ?? null);
    $qid         = cleanInt($d['question_id'] ?? null);
    $text        = cleanStr($d['option_text'] ?? '',500);
    $action      = cleanStr($d['action_type'] ?? 'next_question', 50);
    $nextQid     = cleanInt($d['next_question_id'] ?? null);
    $instruction = cleanStr($d['instruction_text'] ?? '',5000);
    $impact      = cleanStr($d['impact_text'] ?? '',500);
    $setPriority = cleanStr($d['set_priority'] ?? '', 20);
    $colorHint   = cleanStr($d['color_hint'] ?? '', 20);
    $sortOrder   = cleanInt($d['sort_order'] ?? 0) ?? 0;

    if (!$qid || !$text) jsonOut(false,null,'question_id ونص الخيار إلزاميان',400);
    if (!in_array($action,['next_question','end_with_instruction'],true))
        jsonOut(false,null,'action_type غير صحيح',400);
    if ($action==='next_question' && !$nextQid)
        jsonOut(false,null,'يجب تحديد السؤال التالي',400);
    if ($action==='end_with_instruction' && !$instruction)
        jsonOut(false,null,'تعليمات الإنهاء إلزامية',400);

    $validPr = ['Echo','Delta','Charlie','Bravo','Alpha',''];
    if (!in_array($setPriority,$validPr,true)) $setPriority='';

    $params = [
        ':qid'  => $qid,
        ':text' => $text,
        ':act'  => $action,
        ':nqid' => ($action==='next_question') ? $nextQid : null,
        ':ins'  => ($action==='end_with_instruction') ? $instruction : null,
        ':imp'  => $impact ?: null,
        ':sp'   => $setPriority ?: null,
        ':ch'   => $colorHint ?: null,
        ':so'   => $sortOrder,
    ];

    if ($id) {
        $params[':id'] = $id;
        $pdo->prepare("UPDATE question_options SET question_id=:qid,option_text=:text,
                        action_type=:act,next_question_id=:nqid,instruction_text=:ins,
                        impact_text=:imp,set_priority=:sp,color_hint=:ch,sort_order=:so
                        WHERE id=:id")
            ->execute($params);
        jsonOut(true,['id'=>$id],'تم التحديث');
    } else {
        $pdo->prepare("INSERT INTO question_options
                        (question_id,option_text,action_type,next_question_id,instruction_text,
                         impact_text,set_priority,color_hint,sort_order)
                        VALUES (:qid,:text,:act,:nqid,:ins,:imp,:sp,:ch,:so)")
            ->execute($params);
        jsonOut(true,['id'=>(int)$pdo->lastInsertId()],'تم الإنشاء');
    }
}

// ════════════════════════════════════════════════════════════════
// POST: تحديد نقطة دخول
// ════════════════════════════════════════════════════════════════
function setEntryPoint(PDO $pdo, array $d): never
{
    $qid = cleanInt($d['question_id'] ?? null);
    $pid = cleanInt($d['protocol_id'] ?? null);
    if (!$qid || !$pid) jsonOut(false,null,'المعرّفات مطلوبة',400);

    $pdo->prepare("UPDATE questions SET is_entry_point=0 WHERE protocol_id=:pid")
        ->execute([':pid'=>$pid]);
    $pdo->prepare("UPDATE questions SET is_entry_point=1 WHERE id=:id AND protocol_id=:pid")
        ->execute([':id'=>$qid,':pid'=>$pid]);

    jsonOut(true,null,'تم تحديد نقطة الدخول');
}

// ════════════════════════════════════════════════════════════════
// POST: حذف
// ════════════════════════════════════════════════════════════════
function deleteProtocol(PDO $pdo, array $d): never
{
    $id = cleanInt($d['id'] ?? null);
    if (!$id) jsonOut(false,null,'id مطلوب',400);
    $s = $pdo->prepare("DELETE FROM protocols WHERE id=:id");
    $s->execute([':id'=>$id]);
    if (!$s->rowCount()) jsonOut(false,null,'البروتوكول غير موجود',404);
    jsonOut(true,null,'تم الحذف');
}

function deleteQuestion(PDO $pdo, array $d): never
{
    $id = cleanInt($d['id'] ?? null);
    if (!$id) jsonOut(false,null,'id مطلوب',400);
    $s = $pdo->prepare("DELETE FROM questions WHERE id=:id");
    $s->execute([':id'=>$id]);
    if (!$s->rowCount()) jsonOut(false,null,'السؤال غير موجود',404);
    jsonOut(true,null,'تم الحذف');
}

function deleteOption(PDO $pdo, array $d): never
{
    $id = cleanInt($d['id'] ?? null);
    if (!$id) jsonOut(false,null,'id مطلوب',400);
    $s = $pdo->prepare("DELETE FROM question_options WHERE id=:id");
    $s->execute([':id'=>$id]);
    if (!$s->rowCount()) jsonOut(false,null,'الخيار غير موجود',404);
    jsonOut(true,null,'تم الحذف');
}

// ════════════════════════════════════════════════════════════════
// POST: حفظ سجل جلسة
// ════════════════════════════════════════════════════════════════
function saveSessionLog(PDO $pdo, array $d): never
{
    $uuid  = cleanStr($d['session_uuid'] ?? '',36);
    $pid   = cleanInt($d['protocol_id'] ?? null);
    $op    = cleanStr($d['operator_id'] ?? '',50);
    $fp    = cleanStr($d['final_priority'] ?? '', 20);
    $fi    = cleanStr($d['final_instruction'] ?? '',5000);
    $aj    = isset($d['answers_json']) ? json_encode($d['answers_json'],JSON_UNESCAPED_UNICODE) : null;
    $st    = cleanStr($d['status'] ?? 'completed', 20);

    if (!$uuid || !$pid) jsonOut(false,null,'uuid و protocol_id إلزاميان',400);
    if (!in_array($st,['in_progress','completed','abandoned'],true)) $st='completed';

    $pdo->prepare("INSERT INTO session_logs
                    (session_uuid,protocol_id,operator_id,final_priority,final_instruction,
                     answers_json,status,completed_at)
                   VALUES (:uuid,:pid,:op,:fp,:fi,:aj,:st,NOW())
                   ON DUPLICATE KEY UPDATE
                    status=VALUES(status), final_priority=VALUES(final_priority),
                    final_instruction=VALUES(final_instruction),
                    answers_json=VALUES(answers_json), completed_at=NOW()")
        ->execute([':uuid'=>$uuid,':pid'=>$pid,':op'=>$op?:null,':fp'=>$fp?:null,
                   ':fi'=>$fi?:null,':aj'=>$aj,':st'=>$st]);

    jsonOut(true,['session_uuid'=>$uuid],'تم الحفظ');
}

// ════════════════════════════════════════════════════════════════
// Helper: جلب خيارات سؤال
// ════════════════════════════════════════════════════════════════
function getOptions(PDO $pdo, int $qid): array
{
    $s = $pdo->prepare("SELECT id,option_text,action_type,next_question_id,
                               instruction_text,impact_text,set_priority,color_hint,sort_order
                         FROM question_options WHERE question_id=:qid
                         ORDER BY sort_order ASC, id ASC");
    $s->execute([':qid'=>$qid]);
    return $s->fetchAll();
}