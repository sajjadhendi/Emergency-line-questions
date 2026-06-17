-- ============================================================
-- قاعدة بيانات: نظام بروتوكولات الطوارئ الديناميكي
-- Dynamic Emergency Protocols Matrix
-- ============================================================

CREATE DATABASE IF NOT EXISTS `emergency_matrix_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `emergency_matrix_db`;

-- ─── جدول البروتوكولات ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `protocols` (
  `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(20)       NOT NULL,
  `title`          VARCHAR(255)      NOT NULL,
  `description`    TEXT                  NULL,
  `priority_level` ENUM('Echo','Delta','Charlie','Bravo','Alpha') NOT NULL DEFAULT 'Delta',
  `age_group`      ENUM('adult','pediatric','all')                NOT NULL DEFAULT 'all',
  `color_code`     VARCHAR(7)        NOT NULL DEFAULT '#E53E3E',
  `icon`           VARCHAR(50)           NULL,
  `is_active`      TINYINT(1)        NOT NULL DEFAULT 1,
  `sort_order`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`),
  KEY `idx_active_sort` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── جدول الأسئلة ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `questions` (
  `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `protocol_id`    INT UNSIGNED      NOT NULL,
  `question_text`  TEXT              NOT NULL,
  `helper_text`    TEXT                  NULL,
  `is_mandatory`   TINYINT(1)        NOT NULL DEFAULT 1,
  `step_order`     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_entry_point` TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_protocol_order` (`protocol_id`,`step_order`),
  KEY `idx_entry`          (`protocol_id`,`is_entry_point`),
  CONSTRAINT `fk_q_protocol` FOREIGN KEY (`protocol_id`)
    REFERENCES `protocols`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── جدول خيارات الأسئلة ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `question_options` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id`      INT UNSIGNED NOT NULL,
  `option_text`      VARCHAR(500) NOT NULL,
  `action_type`      ENUM('next_question','end_with_instruction') NOT NULL DEFAULT 'next_question',
  `next_question_id` INT UNSIGNED     NULL,
  `instruction_text` TEXT             NULL,
  `impact_text`      VARCHAR(500)     NULL,
  `set_priority`     ENUM('Echo','Delta','Charlie','Bravo','Alpha') NULL,
  `color_hint`       VARCHAR(7)       NULL,
  `sort_order`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question`      (`question_id`,`sort_order`),
  KEY `idx_next_question` (`next_question_id`),
  CONSTRAINT `fk_opt_question` FOREIGN KEY (`question_id`)
    REFERENCES `questions`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opt_next` FOREIGN KEY (`next_question_id`)
    REFERENCES `questions`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── جدول سجلات الجلسات ──────────────────────────────────
CREATE TABLE IF NOT EXISTS `session_logs` (
  `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_uuid`      CHAR(36)        NOT NULL,
  `protocol_id`       INT UNSIGNED    NOT NULL,
  `operator_id`       VARCHAR(50)         NULL,
  `started_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`      TIMESTAMP           NULL,
  `final_priority`    ENUM('Echo','Delta','Charlie','Bravo','Alpha') NULL,
  `final_instruction` TEXT                NULL,
  `answers_json`      JSON                NULL,
  `status`            ENUM('in_progress','completed','abandoned') NOT NULL DEFAULT 'in_progress',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_uuid` (`session_uuid`),
  KEY `idx_protocol_date` (`protocol_id`,`started_at`),
  CONSTRAINT `fk_log_protocol` FOREIGN KEY (`protocol_id`)
    REFERENCES `protocols`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- بيانات تجريبية
-- ============================================================

INSERT INTO `protocols` (`code`,`title`,`description`,`priority_level`,`age_group`,`color_code`,`icon`,`sort_order`) VALUES
('CARD-01','ألم الصدر / توقف القلب','بروتوكول حالات ألم الصدر وتوقف القلب للبالغين','Echo','adult','#E53E3E','fa-heart-pulse',1),
('RESP-01','صعوبة التنفس','بروتوكول حالات صعوبة التنفس وضيق الصدر','Delta','all','#DD6B20','fa-lungs',2),
('TRMA-01','إصابات الحوادث','بروتوكول إصابات الحوادث والصدمات الجسدية','Charlie','all','#D69E2E','fa-car-burst',3);

-- أسئلة CARD-01
INSERT INTO `questions` (`protocol_id`,`question_text`,`helper_text`,`is_mandatory`,`step_order`,`is_entry_point`) VALUES
(1,'هل المريض واعٍ ويستجيب؟','اسأل المتصل: هل يمكنه التحدث معك الآن؟',1,1,1),
(1,'هل يتنفس المريض بشكل طبيعي؟','التنفس الطبيعي = 12-20 نفساً في الدقيقة',1,2,0),
(1,'هل ألم الصدر ينتشر للذراع أو الفك؟','هذا مؤشر خطر مرتفع لنوبة قلبية حادة',1,3,0),
(1,'هل يعاني من تعرق بارد أو غثيان؟','أعراض مصاحبة مهمة لتحديد شدة الحالة',0,4,0),
(1,'هل لديه تاريخ مرضي لأمراض القلب؟','اسأل عن: جلطات سابقة - عمليات قلب - أدوية قلب',0,5,0);

-- خيارات السؤال 1
INSERT INTO `question_options` (`question_id`,`option_text`,`action_type`,`next_question_id`,`instruction_text`,`impact_text`,`set_priority`,`sort_order`) VALUES
(1,'نعم، واعٍ ومستجيب','next_question',2,NULL,'المريض واعٍ - المتابعة مع بروتوكول ألم الصدر',NULL,1),
(1,'لا، فاقد الوعي','end_with_instruction',NULL,'🚨 إجراء فوري - الحالة حرجة\n\n1. أرسل وحدة إسعاف Echo فوراً\n2. وجّه المتصل لبدء CPR إذا لم يكن يتنفس\n3. اطلب مساندة الفريق الطبي المتقدم ALS\n4. ابق على الخط حتى وصول المساعدة','فقدان الوعي - بروتوكول الإنعاش مُفعَّل','Echo',2);

-- خيارات السؤال 2
INSERT INTO `question_options` (`question_id`,`option_text`,`action_type`,`next_question_id`,`instruction_text`,`impact_text`,`set_priority`,`sort_order`) VALUES
(2,'نعم، يتنفس بشكل طبيعي','next_question',3,NULL,'التنفس طبيعي - الاستمرار في التقييم',NULL,1),
(2,'لا، التنفس صعب أو متقطع','end_with_instruction',NULL,'🚨 صعوبة تنفس مع ألم الصدر\n\n1. إرسال وحدة Echo مع دعم التنفس\n2. أجلس المريض في وضع نصف جلوس\n3. لا تتركه يمشي أو يبذل مجهوداً\n4. جهّز AED عند الوصول','صعوبة تنفس حادة - أولوية قصوى','Echo',2),
(2,'التنفس سريع وضحل','next_question',3,NULL,'تنفس سريع - قد يكون قلقاً أو بداية نوبة',NULL,3);

-- خيارات السؤال 3
INSERT INTO `question_options` (`question_id`,`option_text`,`action_type`,`next_question_id`,`instruction_text`,`impact_text`,`set_priority`,`sort_order`) VALUES
(3,'نعم، ينتشر للذراع الأيسر والفك','next_question',4,NULL,'انتشار الألم - مؤشر قوي لنوبة قلبية','Charlie',1),
(3,'الألم محدود في الصدر فقط','next_question',4,NULL,'ألم محدود - قد يكون تشنجاً عضلياً',NULL,2),
(3,'لا يوجد انتشار للألم','next_question',5,NULL,'لا انتشار - استمرار التقييم',NULL,3);

-- خيارات السؤال 4
INSERT INTO `question_options` (`question_id`,`option_text`,`action_type`,`next_question_id`,`instruction_text`,`impact_text`,`set_priority`,`sort_order`) VALUES
(4,'نعم، تعرق بارد وغثيان','end_with_instruction',NULL,'⚠️ تشخيص أولي: نوبة قلبية مشتبه بها\n\n1. إرسال وحدة Charlie فوراً\n2. أعطِ المريض أسبرين 325mg إذا لم يكن لديه حساسية\n3. أجلسه في وضع مريح\n4. لا طعام ولا شراب\n5. مراقبة مستمرة حتى وصول الطاقم','نوبة قلبية: ألم + انتشار + تعرق + غثيان','Charlie',1),
(4,'تعرق فقط بدون غثيان','next_question',5,NULL,'تعرق مع ألم - مراقبة',NULL,2),
(4,'لا توجد أعراض إضافية','next_question',5,NULL,'بدون أعراض إضافية',NULL,3);

-- خيارات السؤال 5
INSERT INTO `question_options` (`question_id`,`option_text`,`action_type`,`next_question_id`,`instruction_text`,`impact_text`,`set_priority`,`sort_order`) VALUES
(5,'نعم، لديه تاريخ أمراض قلب','end_with_instruction',NULL,'⚠️ مريض قلب معروف مع ألم صدر جديد\n\n1. إرسال وحدة Charlie/Delta حسب الحالة\n2. اسأل عن الأدوية الحالية وآخر جرعة\n3. اسأل عن حبوب النيتروجليسرين\n4. إذا وُجدت - جرعة تحت اللسان والانتظار 5 دقائق\n5. ابق على الاتصال','مريض قلب معروف - نوبة جديدة','Delta',1),
(5,'لا، لا يوجد تاريخ قلبي','end_with_instruction',NULL,'ℹ️ ألم صدر بدون تاريخ قلبي\n\n1. إرسال وحدة Delta للتقييم\n2. احتمالات: حموضة معدية - التهاب الجنب - قلق - تشنج عضلي\n3. مراقبة العلامات الحيوية أثناء الانتظار\n4. إذا تدهورت الحالة - أعد الاتصال فوراً','ألم صدر بدون تاريخ قلبي - تقييم ميداني','Delta',2);
