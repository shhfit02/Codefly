
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(255) DEFAULT NULL,
  `level` enum('basic','block','advanced','custom') DEFAULT 'basic',
  `description` text DEFAULT NULL,
  `image_path` text DEFAULT NULL,
  `syllabus_path` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `locked` tinyint(1) DEFAULT 0,
  `total_students` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `modules` DISABLE KEYS */;
INSERT INTO `modules` VALUES (3,'Starter Mission: Fundamental of Programming','Understanding the basics','basic','Gradually introduce stick control, telemetry variables and core safety logic before autonomous routines','../Assets/uploads/module-covers/cover-307bce4b9a808736.png','',NULL,0,0,'2026-01-01 17:21:21'),(6,'Drone Block Builder','Visual logic','block','Create your own drone missions with easy drag-and-drop blocks.','../Assets/uploads/module-covers/cover-afd6faa06189660d.png','',NULL,0,0,'2026-01-05 15:15:27'),(8,'Advanced Drone Development','ESP32 flight lab','advanced','Theme: \"Build and Code Your Own Drone\". Goal: guide cadets through real ESP32 hardware programming, basic flight control, and autonomous decision logic','../Assets/uploads/module-covers/cover-a763978db8c85288.png','',NULL,0,0,'2026-01-07 07:07:55');
/*!40000 ALTER TABLE `modules` ENABLE KEYS */;
DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lessons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `focus` varchar(64) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `video_url` varchar(512) DEFAULT NULL,
  `what_you_learn` text DEFAULT NULL,
  `learning_outcomes` text DEFAULT NULL,
  `quiz_questions` text DEFAULT NULL,
  `status` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`focus`),
  UNIQUE KEY `focus` (`focus`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `fk_lessons_module` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `lessons` DISABLE KEYS */;
INSERT INTO `lessons` VALUES (20,8,'Meet the ESP32','microcontroller-basics','Learn how the ESP32 microcontroller works, explore pins, power rails, and built-in Wi-Fi - Duration: 15 min','15 min','2026-01-07 07:10:59','','','','','open'),(21,8,'Setting Up Arduino IDE','toolchain-setup','Install the ESP32 board package, upload the Blink sketch, and verify the serial upload process. Video: ESP32 Blink Tutorial.','20 min','2026-01-07 07:10:59','','','','','open'),(22,8,'Reading Sensors','sensor-io','Wire an ultrasonic sensor, capture distance readings, and stream data into the Serial Monitor. Video: ESP32 Ultrasonic Sensor Tutorial.','25 min','2026-01-07 07:10:59','','','','','open'),(23,8,'Controlling Motors','motor-control','Drive a DC motor with PWM, script spin and stop routines, and monitor current draw. Video: ESP32 Motor Control.','30 min','2026-01-07 07:10:59','','','','','open'),(24,8,'Wireless Commands','networking','Bring the ESP32 onto Wi-Fi, host a simple web button, and send a take-off command remotely. Video: ESP32 Web Server Control.','25 min','2026-01-07 07:10:59','','','','','open'),(25,8,'Combine Sensors + Motors','control-logic','Blend ultrasonic data with motor PWM to stop propulsion whenever distance falls below 50 cm. Video: ESP32 Obstacle Avoidance Demo.','35 min','2026-01-07 07:10:59','','','','','open'),(26,6,'Takeoff & Landing Safety','takeoff','Power-up checks, safe takeoff and landing blocks, and the green-flag event to wrap it together. - Duration: 3 min','3 min','2026-01-07 07:16:43','','','','','locked'),(27,6,'Navigation: Move & Turn','navigation','Forward, back, left, right, and turn blocks with distance/degree inputs and simple safety spacing.','18 min','2026-01-07 07:16:43','','','','','open'),(28,6,'Camera: Shots & Framing','camera','Use camera/take-photo blocks, align shots with yaw moves, and add pauses for clearer captures.','15 min','2026-01-07 07:16:43','','','','','open'),(29,6,'Flip & Trick Basics','flip','Trigger flips safely, set heights, and chain simple trick combos with waits.','16 min','2026-01-07 07:16:43','','','','','open'),(30,6,'Loops for Patterns','loops','Repeat paths for grids and orbits; tune counts and delays for smooth patterns.','18 min','2026-01-07 07:16:43','','','','','open'),(31,6,'Logic: If/Else Rules','logic','Use logic blocks to guard moves—battery checks, height limits, and obstacle flags.','18 min','2026-01-07 07:16:43','','','','','open'),(32,6,'Math for Missions','math','Scale distances, clamp speeds, and combine operators to calculate safe paths.','17 min','2026-01-07 07:16:43','','','','','open'),(33,6,'Variables & Memory','18-min','Store reusable values like cruise speed, photo count, and altitudes; reuse across moves.','18 min','2026-01-07 07:16:43','','','','','open'),(34,6,'Capstone: Full Mission','capstone','Combine takeoff, navigation, camera, flips, loops, logic, math, and variables into one mission.','22 min','2026-01-07 07:16:43','','','','','open'),(126,3,'What is Programming?','foundation','Animations explain that programming is simply giving clear instructions to computers, just like we guide friends.','2 min','2026-01-07 09:24:28','https://youtu.be/KJXd73u1g2c','Learn how programmers break down problems, why computers need exact steps, and how code lives everywhere from microwaves to movie apps.','Explain programming as giving exact instructions,\nName the inputs, logic, and outputs in a simple program,','[{\"question\":\"What is programming?\",\"choices\":[\"Playing games on a computer\",\"Giving instructions to a computer to perform tasks\",\"Fixing computer hardware\",\"Drawing pictures on a computer\"],\"correct_index\":1},{\"question\":\"Which of the following best describes a program?\",\"choices\":[\"A type of computer\",\"A set of instructions for a computer\",\"A computer screen\",\"An internet connection\"],\"correct_index\":1},{\"question\":\"Who creates programs for computers?\",\"choices\":[\"Computer\",\"Teacher\",\"Programmer\",\"Internet\"],\"correct_index\":2},{\"question\":\"Which device needs programming to work correctly?\",\"choices\":[\"Chair\",\"Book\",\"Smartphone\",\"Pencil\"],\"correct_index\":2},{\"question\":\"Programming is mainly used to make computers:\",\"choices\":[\"Think like humans\",\"Follow instructions correctly\",\"Sleep when not used\",\"Work without electricity\"],\"correct_index\":1},{\"question\":\"Which example shows programming in daily life?\",\"choices\":[\"Writing a story\",\"Following a recipe step by step\",\"Reading a book\",\"Watching television\"],\"correct_index\":1},{\"question\":\"What happens if instructions in a program are unclear?\",\"choices\":[\"The computer will guess\",\"The computer will ignore them\",\"The computer may not work as expected\",\"The computer will fix it automatically\"],\"correct_index\":2},{\"question\":\"Programming languages are used to:\",\"choices\":[\"Talk to humans\",\"Communicate with computers\",\"Draw pictures only\",\"Control electricity\"],\"correct_index\":1},{\"question\":\"Which of the following is NOT a purpose of programming?\",\"choices\":[\"Creating games\",\"Controlling robots or drones\",\"Solving problems\",\"Making computers heavier\"],\"correct_index\":3},{\"question\":\"Programming teaches students to:\",\"choices\":[\"Memorize only\",\"Think logically and solve problems\",\"Type faster\",\"Use social media\"],\"correct_index\":1}]','open'),(127,3,'Humans vs Computers','computer-thinking','Compare how people improvise while computers only follow exact steps. Discover why order matters.','3 min','2026-01-07 09:24:28','5tZyH2nCvzw?rel=0','Explore why computers need every detail spelled out and practice rewriting casual directions so a','Translate messy language into precise commands\nDescribe why computers need exact, step-by-step detail','','open'),(128,3,'Everyday Instructions','algorithms','Turn brushing teeth or making toast into an algorithm to see how daily routines become programs.','1 min','2026-01-07 09:24:28','https://youtu.be/r_Rsu6Av1Vk','Learn to break daily routines into numbered steps so someone else - or a computer - can repeat them without confusion.','Break everyday tasks into ordered instructions\nSpot the decision points hiding in chores\nShare steps that another person can follow without guessing.','','open'),(129,3,'Algorithms & Flowcharts','flowcharts','Sketch simple flowcharts using start/end, arrows, and decision diamonds to map a recipe.','10 min','2026-01-07 09:24:28','https://youtu.be/RwnY_mJ6ras','Learn the start/end, process, and decision symbols that map any algorithm into a visual plan.','Recognize core flowchart shapes and their meaning\nTranslate a written algorithm into a diagram','','open'),(130,3,'Variables Explained Simply','variables','See how boxes can store numbers, text, or states and why changing them changes your program.','5 min','2026-01-07 09:24:28','','See how variables capture info, how values change, and why constants and variables work together','Explain variables as containers that can change\nTell the difference between constants and variables.','','open');
/*!40000 ALTER TABLE `lessons` ENABLE KEYS */;
DROP TABLE IF EXISTS `lesson_quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lesson_quiz_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `choice1` varchar(255) NOT NULL,
  `choice2` varchar(255) NOT NULL,
  `choice3` varchar(255) NOT NULL,
  `choice4` varchar(255) NOT NULL,
  `correct_choice` tinyint(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lqq_lesson` (`lesson_id`),
  CONSTRAINT `fk_lqq_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `lesson_quiz_questions` DISABLE KEYS */;
INSERT INTO `lesson_quiz_questions` VALUES (25,126,'What is programming?','Playing games on a computer','Giving instructions to a computer to perform tasks','Fixing computer hardware','Drawing pictures on a computer',1,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(26,126,'Which of the following best describes a program?','A type of computer','A set of instructions for a computer','A computer screen','An internet connection',1,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(27,126,'Who creates programs for computers?','Computer','Teacher','Programmer','Internet',2,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(28,126,'Which device needs programming to work correctly?','Chair','Book','Smartphone','Pencil',2,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(29,126,'Programming is mainly used to make computers:','Think like humans','Follow instructions correctly','Sleep when not used','Work without electricity',1,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(30,126,'Which example shows programming in daily life?','Writing a story','Following a recipe step by step','Reading a book','Watching television',1,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(31,126,'What happens if instructions in a program are unclear?','The computer will guess','The computer will ignore them','The computer may not work as expected','The computer will fix it automatically',2,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(32,126,'Programming languages are used to:','Talk to humans','Communicate with computers','Draw pictures only','Control electricity',1,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(33,126,'Which of the following is NOT a purpose of programming?','Creating games','Controlling robots or drones','Solving problems','Making computers heavier',3,'2026-01-07 09:24:28','2026-01-07 09:24:28'),(34,126,'Programming teaches students to:','Memorize only','Think logically and solve problems','Type faster','Use social media',1,'2026-01-07 09:24:28','2026-01-07 09:24:28');
/*!40000 ALTER TABLE `lesson_quiz_questions` ENABLE KEYS */;
DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `options_json` text DEFAULT NULL,
  `correct_option` varchar(255) DEFAULT NULL,
  `xp` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_quizzes_lesson` (`lesson_id`),
  CONSTRAINT `fk_quizzes_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `quizzes` DISABLE KEYS */;
/*!40000 ALTER TABLE `quizzes` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

