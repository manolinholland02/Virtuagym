-- Clubs
CREATE TABLE `Clubs` (
  `club_id` BINARY(16) NOT NULL,
  `club_name` VARCHAR(120),
  `city` VARCHAR(100),
  PRIMARY KEY (`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Members
CREATE TABLE `Members` (
  `member_id` BINARY(16) NOT NULL,
  `club_id` BINARY(16) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `birthday` DATE NULL,
  `start_date` DATE NOT NULL,
  `active_status` TINYINT(1) NOT NULL,
  PRIMARY KEY (`member_id`),
  KEY `idx_members_club` (`club_id`),
  CONSTRAINT `fk_members_club`
    FOREIGN KEY (`club_id`) REFERENCES `Clubs`(`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CheckIns
CREATE TABLE `CheckIns` (
  `checkin_id` BINARY(16) NOT NULL,
  `member_id` BINARY(16) NOT NULL,
  `club_id` BINARY(16) NOT NULL,
  `timestamp` DATETIME(3) NOT NULL,
  PRIMARY KEY (`checkin_id`),
  KEY `idx_checkins_member_time` (`member_id`, `timestamp`),
  KEY `idx_checkins_club_time`   (`club_id`, `timestamp`),
  CONSTRAINT `fk_checkins_member`
    FOREIGN KEY (`member_id`) REFERENCES `Members`(`member_id`),
  CONSTRAINT `fk_checkins_club`
    FOREIGN KEY (`club_id`) REFERENCES `Clubs`(`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Exercises  (identifying relationship to Clubs via club_id in PK)
CREATE TABLE `Exercises` (
  `club_id` BINARY(16) NOT NULL,
  `exercise_id` BINARY(16) NOT NULL,
  `name` VARCHAR(160),
  `target_muscles` JSON,
  `body_parts` JSON,
  `equipments` JSON,
  PRIMARY KEY (`club_id`, `exercise_id`),
  CONSTRAINT `fk_exercises_club`
    FOREIGN KEY (`club_id`) REFERENCES `Clubs`(`club_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ExerciseInstances
CREATE TABLE `ExerciseInstances` (
  `instance_id` BINARY(16) NOT NULL,
  `club_id` BINARY(16) NOT NULL,
  `exercise_id` BINARY(16) NOT NULL,
  `member_id` BINARY(16) NOT NULL,
  `timestamp` DATETIME(3) NOT NULL,
  PRIMARY KEY (`instance_id`),
  KEY `idx_exinst_member_time` (`member_id`, `timestamp`),
  KEY `idx_exinst_club_time`   (`club_id`, `timestamp`),
  KEY `FK1` (`club_id`, `exercise_id`),
  KEY `FK2` (`member_id`),
  CONSTRAINT `fk_exinst_member`
    FOREIGN KEY (`member_id`) REFERENCES `Members`(`member_id`),
  CONSTRAINT `fk_exinst_club`
    FOREIGN KEY (`club_id`) REFERENCES `Clubs`(`club_id`),
  CONSTRAINT `fk_exinst_exercise`
    FOREIGN KEY (`club_id`, `exercise_id`) REFERENCES `Exercises`(`club_id`, `exercise_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscriptions
CREATE TABLE `Subscriptions` (
  `subscription_id` BINARY(16) NOT NULL,
  `member_id` BINARY(16) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `plan_type` VARCHAR(50),        
  `price` DECIMAL(10,2),
  PRIMARY KEY (`subscription_id`),
  KEY `idx_subs_member_start` (`member_id`, `start_date`),
  CONSTRAINT `fk_subs_member`
    FOREIGN KEY (`member_id`) REFERENCES `Members`(`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
