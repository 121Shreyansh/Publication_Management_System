-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: pub
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `book_chapter_details`
--

DROP TABLE IF EXISTS `book_chapter_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_chapter_details` (
  `book_chapter_id` int NOT NULL,
  `book_title` varchar(250) DEFAULT NULL,
  `chapter_title` varchar(250) NOT NULL,
  `pub_date` date DEFAULT NULL,
  `isbn_no` varchar(20) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`book_chapter_id`),
  CONSTRAINT `book_chapter_details_ibfk_1` FOREIGN KEY (`book_chapter_id`) REFERENCES `publication_author` (`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_chapter_details`
--

LOCK TABLES `book_chapter_details` WRITE;
/*!40000 ALTER TABLE `book_chapter_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_chapter_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `book_chapter_publication_details`
--

DROP TABLE IF EXISTS `book_chapter_publication_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `book_chapter_publication_details` (
  `book_chapter_publication_id` int NOT NULL,
  `book_chapter_publication_title` varchar(250) NOT NULL,
  `book_chapter_title` varchar(250) DEFAULT NULL,
  `book_chapter_publication_date` date DEFAULT NULL,
  `book_chapter_publisher` varchar(50) DEFAULT NULL,
  `publication_volume_no` int DEFAULT NULL,
  `publication_issue_no` int DEFAULT NULL,
  `published_city` varchar(50) DEFAULT NULL,
  `published_country` varchar(30) DEFAULT NULL,
  `publication_doi` varchar(100) DEFAULT NULL,
  `first_page_no` int DEFAULT NULL,
  `last_page_no` int DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`book_chapter_publication_id`),
  CONSTRAINT `book_chapter_publication_details_ibfk_1` FOREIGN KEY (`book_chapter_publication_id`) REFERENCES `publication_author` (`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `book_chapter_publication_details`
--

LOCK TABLES `book_chapter_publication_details` WRITE;
/*!40000 ALTER TABLE `book_chapter_publication_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `book_chapter_publication_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `co_author_requests`
--

DROP TABLE IF EXISTS `co_author_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `co_author_requests` (
  `request_id` int NOT NULL AUTO_INCREMENT,
  `requester_id` int NOT NULL,
  `requested_id` varchar(30) NOT NULL,
  `publication_id` int NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Pending',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` datetime DEFAULT NULL,
  PRIMARY KEY (`request_id`),
  KEY `idx_requester` (`requester_id`),
  KEY `idx_publication` (`publication_id`),
  CONSTRAINT `co_author_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `co_author_requests_ibfk_2` FOREIGN KEY (`publication_id`) REFERENCES `publication_author` (`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `co_author_requests`
--

LOCK TABLES `co_author_requests` WRITE;
/*!40000 ALTER TABLE `co_author_requests` DISABLE KEYS */;
INSERT INTO `co_author_requests` VALUES (1,2,'5',1,'Pending','2026-03-18 03:52:45',NULL),(2,7,'5',2,'Pending','2026-03-18 05:11:19',NULL);
/*!40000 ALTER TABLE `co_author_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conference_publication_details`
--

DROP TABLE IF EXISTS `conference_publication_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conference_publication_details` (
  `conference_publication_id` int NOT NULL,
  `conference_publication_title` varchar(250) NOT NULL,
  `conference_title` varchar(250) DEFAULT NULL,
  `conference_start_date` date DEFAULT NULL,
  `conference_end_date` date DEFAULT NULL,
  `conference_city` varchar(50) DEFAULT NULL,
  `conference_country` varchar(30) DEFAULT NULL,
  `conference_publisher` varchar(50) DEFAULT NULL,
  `publication_doi` varchar(100) DEFAULT NULL,
  `conference_location` varchar(255) DEFAULT NULL,
  `first_page_no` int DEFAULT NULL,
  `last_page_no` int DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`conference_publication_id`),
  CONSTRAINT `conference_publication_details_ibfk_1` FOREIGN KEY (`conference_publication_id`) REFERENCES `publication_author` (`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conference_publication_details`
--

LOCK TABLES `conference_publication_details` WRITE;
/*!40000 ALTER TABLE `conference_publication_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `conference_publication_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department`
--

DROP TABLE IF EXISTS `department`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `department` (
  `department_id` varchar(5) NOT NULL,
  `department_name` varchar(50) DEFAULT NULL,
  `department_phone` bigint DEFAULT NULL,
  `department_email` varchar(50) DEFAULT NULL,
  `department_hod` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department`
--

LOCK TABLES `department` WRITE;
/*!40000 ALTER TABLE `department` DISABLE KEYS */;
INSERT INTO `department` VALUES ('AE','Aerospace Engineering',NULL,NULL,NULL),('AR','Architecture',NULL,NULL,NULL),('BT','Biotechnology',NULL,NULL,NULL),('CE','Civil Engineering',NULL,NULL,NULL),('CH','Chemical Engineering',NULL,NULL,NULL),('CH2','Chemistry',NULL,NULL,NULL),('CST','Comp Science & Technology',NULL,NULL,NULL),('ECE','Electronics & Communication Engineering',NULL,NULL,NULL),('EE','Electrical Engineering',NULL,NULL,NULL),('EN','Energy Studies',NULL,NULL,NULL),('HUM','Humanities & Social Sciences',NULL,NULL,NULL),('IE','Industrial Engineering',NULL,NULL,NULL),('IT','Information Technology',NULL,NULL,NULL),('MA','Mathematics',NULL,NULL,NULL),('MCA','Master of Computer Applications',NULL,NULL,NULL),('ME','Mechanical Engineering',NULL,NULL,NULL),('MGT','Management Studies',NULL,NULL,NULL),('MSC','Mining Engineering',NULL,NULL,NULL),('MT','Metallurgical & Material Engineering',NULL,NULL,NULL),('PH','Physics',NULL,NULL,NULL);
/*!40000 ALTER TABLE `department` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_info`
--

DROP TABLE IF EXISTS `employee_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_info` (
  `emp_id` int NOT NULL,
  `emp_type` varchar(10) NOT NULL,
  `department_id` varchar(5) DEFAULT NULL,
  `gender` varchar(10) NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `middle_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(20) DEFAULT NULL,
  `emp_designation` varchar(50) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `mobile_no_1` bigint NOT NULL,
  `mobile_no_2` bigint DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `pin` int DEFAULT NULL,
  `country` varchar(20) DEFAULT NULL,
  `date_join` date NOT NULL,
  `date_leave` date DEFAULT NULL,
  `highest_degree` varchar(20) DEFAULT NULL,
  `highest_degree_univ` varchar(50) DEFAULT NULL,
  `highest_degree_date` date DEFAULT NULL,
  `area_specialization` varchar(100) DEFAULT NULL,
  `pan` varchar(20) DEFAULT NULL,
  `draft_saved` int DEFAULT '0',
  PRIMARY KEY (`emp_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `employee_info_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `employee_info_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_info`
--

LOCK TABLES `employee_info` WRITE;
/*!40000 ALTER TABLE `employee_info` DISABLE KEYS */;
INSERT INTO `employee_info` VALUES (2,'Employee','CST','Male','Sreenjoy',NULL,'Chakrabarty','Assistant Professor','2005-03-05',9875488188,NULL,'sreenjoy2005@gmail.com','34 Purbachal Bidhan Road','Kolkata','West Bengal',700078,'India','2026-03-09',NULL,'Ph.D','IIEST','2026-03-20','Machine Learning',NULL,1),(5,'Employee','MCA','Male','Trishanu',NULL,'Ghosh','Assistant Professor','2004-12-16',9875488188,NULL,'tghosh@gmail.com','Mandirtala Road','Howrah','West Bengal',711102,'India','2026-03-11',NULL,'M.Tech','IIEST',NULL,'Machine Learning','CYPK243949234',1),(7,'Employee','CST','Male','Sreenjoy',NULL,'Chakrabarty','Assistant Professor','2005-03-05',9875488188,NULL,'sreenjoy2005@gmail.com','34 Purbachal Bidhan Road','Kolkata','West Bengal',700078,'India','2026-03-04',NULL,'Ph.D','IIEST','2026-03-12','Machine Learning','CYPK243949234',1),(8,'Employee','EE','Other','Sourav',NULL,'Pal','Assistant Professor','2005-03-15',9875488188,NULL,'abcd@gmail.com',NULL,NULL,NULL,NULL,NULL,'2026-03-18',NULL,'Ph.D',NULL,NULL,NULL,NULL,1),(9,'Employee','AR','Male','Tapoprabha',NULL,'Paul','Professor',NULL,8017654780,NULL,'tapo555@gmail.com',NULL,NULL,NULL,NULL,NULL,'2026-04-01',NULL,NULL,NULL,NULL,NULL,NULL,0);
/*!40000 ALTER TABLE `employee_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hod`
--

DROP TABLE IF EXISTS `hod`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hod` (
  `hod_id` int NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `middle_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(20) DEFAULT NULL,
  `designation` varchar(25) DEFAULT NULL,
  `mobile_no` bigint DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `department_name` varchar(50) DEFAULT NULL,
  `doj` date DEFAULT NULL,
  `dol` date DEFAULT NULL,
  PRIMARY KEY (`hod_id`),
  CONSTRAINT `hod_ibfk_1` FOREIGN KEY (`hod_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hod`
--

LOCK TABLES `hod` WRITE;
/*!40000 ALTER TABLE `hod` DISABLE KEYS */;
/*!40000 ALTER TABLE `hod` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `index`
--

DROP TABLE IF EXISTS `index`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `index` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `change_password` int DEFAULT '0',
  `profile_completed` tinyint(1) DEFAULT '0',
  `last_changed_password_date` date DEFAULT NULL,
  `last_changed_password_time` time DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_user_name` (`user_name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `index`
--

LOCK TABLES `index` WRITE;
/*!40000 ALTER TABLE `index` DISABLE KEYS */;
INSERT INTO `index` VALUES (2,'sreenjoy','$2y$10$gN3sTzzf6abozl0esZd/EuWo/XDTdDJUz2aykEDxiNj0U.yFnQFZG','Employee',1,1,NULL,NULL),(3,'stu2024001','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Student-UG',1,1,NULL,NULL),(4,'2024CSB002','$2y$10$ZX678qARRxpW5XHPEz1EU.C1C6ZulE6qWyJ5Z/7nhB0xkA4x4dVO2','Student-UG',1,1,NULL,NULL),(5,'trishanu','$2y$10$/W6rkyTE.sm59ybE2OamOu5xby7hHrEkgP8.A0erRnyZBdYcHGuj2','Employee',1,1,'2026-03-17','19:35:15'),(6,'hod_admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','HOD',1,1,NULL,NULL),(7,'johndoe','$2y$10$ovpvtfgm.p.TwirpMmd05ur1lCw3SQlHRYu2Zrq9yRtuv.oXVBEm6','Employee',1,1,'2026-03-18','05:08:43'),(8,'sourav','$2y$10$ykU0N.KcFkCbny9H2VcIx.mckuNGq0iP8zVywIOytj69Or2VCw0jS','Employee',1,1,'2026-03-18','09:17:39'),(9,'tapo','$2y$10$gQEYaoe05s21rrF4YTdJv.VZYma8oHtsNo4rfe1BPl0gUMNRXPmg6','Employee',0,0,NULL,NULL),(10,'swarnava','$2y$10$jefLy7ihjlgp/CZSFLhGYue4pH6.2stF8SHFzc5P/0z5SdirLp5VG','Student-UG',1,1,'2026-04-16','18:29:24');
/*!40000 ALTER TABLE `index` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_assignment`
--

DROP TABLE IF EXISTS `job_assignment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_assignment` (
  `assignment_id` int NOT NULL AUTO_INCREMENT,
  `job_assign_type_id` int DEFAULT NULL,
  `assigned_by` int DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `assigned_for` int DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `job_assignment_start_date` date DEFAULT NULL,
  `job_assignment_due_date` date DEFAULT NULL,
  `job_assignment_status` varchar(20) DEFAULT 'Pending',
  PRIMARY KEY (`assignment_id`),
  KEY `job_assign_type_id` (`job_assign_type_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `assigned_to` (`assigned_to`),
  KEY `assigned_for` (`assigned_for`),
  CONSTRAINT `job_assignment_ibfk_1` FOREIGN KEY (`job_assign_type_id`) REFERENCES `job_details` (`job_type_id`) ON DELETE SET NULL,
  CONSTRAINT `job_assignment_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `employee_info` (`emp_id`) ON DELETE SET NULL,
  CONSTRAINT `job_assignment_ibfk_3` FOREIGN KEY (`assigned_to`) REFERENCES `employee_info` (`emp_id`) ON DELETE SET NULL,
  CONSTRAINT `job_assignment_ibfk_4` FOREIGN KEY (`assigned_for`) REFERENCES `student_info` (`student_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_assignment`
--

LOCK TABLES `job_assignment` WRITE;
/*!40000 ALTER TABLE `job_assignment` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_assignment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_completion_log`
--

DROP TABLE IF EXISTS `job_completion_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_completion_log` (
  `job_completion_log_id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int DEFAULT NULL,
  `done_by` int DEFAULT NULL,
  `done_for` int DEFAULT NULL,
  `job_completion_status` varchar(20) DEFAULT NULL,
  `job_completion_date` date DEFAULT NULL,
  `job_completion_time` time DEFAULT NULL,
  PRIMARY KEY (`job_completion_log_id`),
  KEY `assignment_id` (`assignment_id`),
  KEY `done_by` (`done_by`),
  KEY `done_for` (`done_for`),
  CONSTRAINT `job_completion_log_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `job_assignment` (`assignment_id`) ON DELETE SET NULL,
  CONSTRAINT `job_completion_log_ibfk_2` FOREIGN KEY (`done_by`) REFERENCES `employee_info` (`emp_id`) ON DELETE SET NULL,
  CONSTRAINT `job_completion_log_ibfk_3` FOREIGN KEY (`done_for`) REFERENCES `student_info` (`student_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_completion_log`
--

LOCK TABLES `job_completion_log` WRITE;
/*!40000 ALTER TABLE `job_completion_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_completion_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_details`
--

DROP TABLE IF EXISTS `job_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_details` (
  `job_type_id` int NOT NULL AUTO_INCREMENT,
  `job_name` varchar(100) NOT NULL,
  PRIMARY KEY (`job_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_details`
--

LOCK TABLES `job_details` WRITE;
/*!40000 ALTER TABLE `job_details` DISABLE KEYS */;
INSERT INTO `job_details` VALUES (1,'Student ID and Password Generation'),(2,'Student Record Verification'),(3,'Student Marks Verification'),(4,'Student Marks Upload'),(5,'Subject Pooling');
/*!40000 ALTER TABLE `job_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_publication_details`
--

DROP TABLE IF EXISTS `journal_publication_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `journal_publication_details` (
  `journal_publication_id` int NOT NULL,
  `journal_publication_title` varchar(250) NOT NULL,
  `journal_title` varchar(250) DEFAULT NULL,
  `journal_publication_date` date DEFAULT NULL,
  `journal_publisher` varchar(50) DEFAULT NULL,
  `publication_volume_no` int DEFAULT NULL,
  `publication_issue_no` int DEFAULT NULL,
  `published_city` varchar(50) DEFAULT NULL,
  `published_country` varchar(30) DEFAULT NULL,
  `publication_doi` varchar(100) DEFAULT NULL,
  `first_page_no` int DEFAULT NULL,
  `last_page_no` int DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`journal_publication_id`),
  CONSTRAINT `journal_publication_details_ibfk_1` FOREIGN KEY (`journal_publication_id`) REFERENCES `publication_author` (`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_publication_details`
--

LOCK TABLES `journal_publication_details` WRITE;
/*!40000 ALTER TABLE `journal_publication_details` DISABLE KEYS */;
INSERT INTO `journal_publication_details` VALUES (1,'ABCD',NULL,'2026-03-12',NULL,NULL,NULL,NULL,NULL,'10.1189',NULL,NULL,NULL),(2,'ABCDEF',NULL,'2026-03-11',NULL,NULL,NULL,NULL,NULL,'10.11',NULL,NULL,NULL);
/*!40000 ALTER TABLE `journal_publication_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marksheet`
--

DROP TABLE IF EXISTS `marksheet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marksheet` (
  `enrollment_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_code` varchar(32) COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `semester` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `letter_grade` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_points` decimal(8,2) NOT NULL,
  PRIMARY KEY (`enrollment_no`,`subject_code`),
  KEY `idx_semester` (`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marksheet`
--

LOCK TABLES `marksheet` WRITE;
/*!40000 ALTER TABLE `marksheet` DISABLE KEYS */;
/*!40000 ALTER TABLE `marksheet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `marksheet_registration`
--

DROP TABLE IF EXISTS `marksheet_registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marksheet_registration` (
  `enrollment_no` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_amount` decimal(12,2) DEFAULT NULL,
  `fee_date` date DEFAULT NULL,
  `txn_id` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_json` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`enrollment_no`,`semester`),
  KEY `idx_semester` (`semester`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marksheet_registration`
--

LOCK TABLES `marksheet_registration` WRITE;
/*!40000 ALTER TABLE `marksheet_registration` DISABLE KEYS */;
/*!40000 ALTER TABLE `marksheet_registration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `newspaper_publication_details`
--

DROP TABLE IF EXISTS `newspaper_publication_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newspaper_publication_details` (
  `newspaper_pub_id` int NOT NULL,
  `newspaper_title` varchar(250) DEFAULT NULL,
  `article_title` varchar(250) NOT NULL,
  `pub_date` date DEFAULT NULL,
  `page_no` varchar(20) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`newspaper_pub_id`),
  CONSTRAINT `newspaper_publication_details_ibfk_1` FOREIGN KEY (`newspaper_pub_id`) REFERENCES `publication_author` (`publication_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `newspaper_publication_details`
--

LOCK TABLES `newspaper_publication_details` WRITE;
/*!40000 ALTER TABLE `newspaper_publication_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `newspaper_publication_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outside_author`
--

DROP TABLE IF EXISTS `outside_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outside_author` (
  `os_author_id` varchar(30) NOT NULL,
  `gender` varchar(10) NOT NULL DEFAULT 'Other',
  `first_name` varchar(20) NOT NULL,
  `middle_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(20) DEFAULT NULL,
  `mobile_no` bigint DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `institute_name` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `pin` int DEFAULT NULL,
  `country` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`os_author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outside_author`
--

LOCK TABLES `outside_author` WRITE;
/*!40000 ALTER TABLE `outside_author` DISABLE KEYS */;
/*!40000 ALTER TABLE `outside_author` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `outside_investigator`
--

DROP TABLE IF EXISTS `outside_investigator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outside_investigator` (
  `os_investigator_id` varchar(30) NOT NULL,
  `gender` varchar(10) NOT NULL DEFAULT 'Other',
  `first_name` varchar(20) NOT NULL,
  `middle_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(20) DEFAULT NULL,
  `mobile_no` bigint NOT NULL,
  `phone_no` bigint DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `institute_name` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `pin` int DEFAULT NULL,
  `country` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`os_investigator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `outside_investigator`
--

LOCK TABLES `outside_investigator` WRITE;
/*!40000 ALTER TABLE `outside_investigator` DISABLE KEYS */;
/*!40000 ALTER TABLE `outside_investigator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `publication_author`
--

DROP TABLE IF EXISTS `publication_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `publication_author` (
  `publication_id` int NOT NULL AUTO_INCREMENT,
  `author_id` int NOT NULL,
  `author_type` varchar(30) NOT NULL DEFAULT 'Main',
  `publication_type` varchar(30) NOT NULL,
  `publication_status` varchar(30) NOT NULL DEFAULT 'National',
  `verification_flag` int DEFAULT '0',
  PRIMARY KEY (`publication_id`),
  KEY `author_id` (`author_id`),
  CONSTRAINT `publication_author_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `publication_author`
--

LOCK TABLES `publication_author` WRITE;
/*!40000 ALTER TABLE `publication_author` DISABLE KEYS */;
INSERT INTO `publication_author` VALUES (1,2,'Main','Journal','National',1),(2,7,'Main','Journal','National',1);
/*!40000 ALTER TABLE `publication_author` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `r_and_c_details`
--

DROP TABLE IF EXISTS `r_and_c_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `r_and_c_details` (
  `rc_id` int NOT NULL,
  `rc_title` varchar(250) NOT NULL,
  `funding_agency` varchar(250) NOT NULL,
  `rc_duration_month` int NOT NULL,
  `rc_start_date` date DEFAULT NULL,
  `rc_end_date` date DEFAULT NULL,
  `rc_sanctioned_amount` bigint DEFAULT NULL,
  `rc_sanction_no` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`rc_id`),
  CONSTRAINT `r_and_c_details_ibfk_1` FOREIGN KEY (`rc_id`) REFERENCES `rc_and_investigator` (`rc_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `r_and_c_details`
--

LOCK TABLES `r_and_c_details` WRITE;
/*!40000 ALTER TABLE `r_and_c_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `r_and_c_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_questions`
--

DROP TABLE IF EXISTS `security_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_questions` (
  `security_questions_id` int NOT NULL AUTO_INCREMENT,
  `question` varchar(255) NOT NULL,
  PRIMARY KEY (`security_questions_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_questions`
--

LOCK TABLES `security_questions` WRITE;
/*!40000 ALTER TABLE `security_questions` DISABLE KEYS */;
INSERT INTO `security_questions` VALUES (1,'What city were you born in?'),(2,'What is your mother\'s maiden name?'),(3,'What was your first pet name?'),(4,'What was the name of your favorite childhood book?'),(5,'What was the first sports team you supported?'),(6,'What was the first game you loved playing?'),(7,'What was the name of your first best friend?'),(8,'What was the name of the first teacher you liked the most?'),(9,'What was the first subject you enjoyed in school?'),(10,'What was your favorite place to visit during childhood?'),(11,'What was the name of the first place you visited outside your hometown?');
/*!40000 ALTER TABLE `security_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semester_registration`
--

DROP TABLE IF EXISTS `semester_registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `semester_registration` (
  `student_id` int NOT NULL,
  `semester` int NOT NULL,
  `sem_reg_date` date DEFAULT NULL,
  `verification_flag` bigint DEFAULT '0',
  PRIMARY KEY (`student_id`),
  CONSTRAINT `semester_registration_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semester_registration`
--

LOCK TABLES `semester_registration` WRITE;
/*!40000 ALTER TABLE `semester_registration` DISABLE KEYS */;
INSERT INTO `semester_registration` VALUES (3,1,'2026-03-17',0),(4,4,'2026-03-17',0),(10,1,'2026-03-31',0);
/*!40000 ALTER TABLE `semester_registration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_info`
--

DROP TABLE IF EXISTS `student_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_info` (
  `student_id` int NOT NULL,
  `student_type` varchar(10) NOT NULL,
  `department_id` varchar(5) DEFAULT NULL,
  `gender` varchar(10) NOT NULL,
  `first_name` varchar(20) NOT NULL,
  `middle_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `mobile_no` bigint DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `address` varchar(50) DEFAULT NULL,
  `city` varchar(20) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `pin` varchar(10) DEFAULT NULL,
  `country` varchar(20) DEFAULT NULL,
  `guardian_first_name` varchar(20) NOT NULL,
  `guardian_middle_name` varchar(20) DEFAULT NULL,
  `guardian_last_name` varchar(20) DEFAULT NULL,
  `guardain_mobile_no` bigint DEFAULT NULL,
  `guardian_email` varchar(50) DEFAULT NULL,
  `enrolment_date` date DEFAULT NULL,
  `blood_group` varchar(5) DEFAULT NULL,
  `registration_no` varchar(30) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `category_of_phd` varchar(30) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Ongoing',
  `degree_awarded_date` date DEFAULT NULL,
  `verification_flag` bigint DEFAULT '0',
  `verification_status` varchar(20) DEFAULT 'Pending',
  `draft_saved` int DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `verification_date` date DEFAULT NULL,
  PRIMARY KEY (`student_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `student_info_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `student_info_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `department` (`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_info`
--

LOCK TABLES `student_info` WRITE;
/*!40000 ALTER TABLE `student_info` DISABLE KEYS */;
INSERT INTO `student_info` VALUES (3,'UG','CST','Male','Demo',NULL,'Student',NULL,NULL,'demo@iiest.ac.in',NULL,NULL,NULL,NULL,NULL,'Demo Guardian',NULL,NULL,NULL,NULL,NULL,NULL,'2024-CSTB-001',NULL,NULL,'Ongoing',NULL,1,'Verified',NULL,1,'2026-03-17'),(4,'UG','CST','Male','Arghadip','','Som','2006-05-25',123456789,'arghadipsom2006@gmail.com','ABCD Bankura Road','Bankura',NULL,'700078',NULL,'Ribhu',NULL,'Som',8017654780,'ribhu2005@gmail.com',NULL,'B+',NULL,NULL,NULL,'active',NULL,1,'Pending',NULL,NULL,'2026-03-17'),(10,'UG','AE','Male','Swarnava',NULL,'Dutta',NULL,9875488188,'xyz2005@gmail.com','34 Purbachal Road','Kolkata','West Bengal','700078',NULL,'Tapo ',NULL,'Paul',1234567890,NULL,'2024-03-05','B+','2024AMB001',NULL,NULL,'Ongoing',NULL,1,'Pending',1,6,'2026-04-01');
/*!40000 ALTER TABLE `student_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_marks`
--

DROP TABLE IF EXISTS `student_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_marks` (
  `student_id` int NOT NULL,
  `subject_code` varchar(10) NOT NULL,
  `academic_year` int NOT NULL DEFAULT '0',
  `subject_marks` int DEFAULT NULL,
  `subject_grade_points` int DEFAULT NULL,
  `verification_flag` bigint DEFAULT '0',
  PRIMARY KEY (`student_id`,`subject_code`,`academic_year`),
  CONSTRAINT `student_marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `student_marks_ibfk_2` FOREIGN KEY (`student_id`, `subject_code`) REFERENCES `subject_enrollement` (`student_id`, `subject_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_marks`
--

LOCK TABLES `student_marks` WRITE;
/*!40000 ALTER TABLE `student_marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_enrollement`
--

DROP TABLE IF EXISTS `subject_enrollement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_enrollement` (
  `student_id` int NOT NULL,
  `subject_code` varchar(10) NOT NULL,
  `academic_year` int DEFAULT NULL,
  `session` varchar(10) DEFAULT NULL,
  `verification_flag` bigint DEFAULT '0',
  PRIMARY KEY (`student_id`,`subject_code`),
  KEY `subject_code` (`subject_code`),
  CONSTRAINT `subject_enrollement_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `subject_enrollement_ibfk_2` FOREIGN KEY (`subject_code`) REFERENCES `subjects_pool` (`subject_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_enrollement`
--

LOCK TABLES `subject_enrollement` WRITE;
/*!40000 ALTER TABLE `subject_enrollement` DISABLE KEYS */;
/*!40000 ALTER TABLE `subject_enrollement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects_offered`
--

DROP TABLE IF EXISTS `subjects_offered`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects_offered` (
  `subject_code` varchar(10) NOT NULL,
  `year_of_introduction` date NOT NULL,
  `subject_semester` int NOT NULL,
  `taught_in` varchar(10) NOT NULL,
  `academic_year` int DEFAULT NULL,
  `session` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`subject_code`,`year_of_introduction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects_offered`
--

LOCK TABLES `subjects_offered` WRITE;
/*!40000 ALTER TABLE `subjects_offered` DISABLE KEYS */;
/*!40000 ALTER TABLE `subjects_offered` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects_pool`
--

DROP TABLE IF EXISTS `subjects_pool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects_pool` (
  `subject_code` varchar(10) NOT NULL,
  `year_of_introduction` date NOT NULL,
  `subject_name` varchar(50) DEFAULT NULL,
  `subject_type` varchar(20) DEFAULT NULL,
  `subject_semester` int NOT NULL,
  `taught_in` varchar(10) NOT NULL,
  `credit` int DEFAULT NULL,
  `lecture_hours` int DEFAULT NULL,
  `tutorial_hours` int DEFAULT NULL,
  `practical_hours` int DEFAULT NULL,
  PRIMARY KEY (`subject_code`,`year_of_introduction`),
  UNIQUE KEY `uq_subject_code` (`subject_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects_pool`
--

LOCK TABLES `subjects_pool` WRITE;
/*!40000 ALTER TABLE `subjects_pool` DISABLE KEYS */;
INSERT INTO `subjects_pool` VALUES ('CE101','2023-01-01','Mathematics I','Theory',1,'CE',4,3,1,0),('CE102','2023-01-01','Engineering Physics','Theory',1,'CE',4,3,1,0),('CE103','2023-01-01','Engineering Chemistry','Theory',1,'CE',3,3,0,0),('CE104','2023-01-01','English for Communication','Theory',1,'CE',2,2,0,0),('CE105','2023-01-01','Engineering Drawing','Theory',1,'CE',3,2,1,0),('CE191','2023-01-01','Physics Lab','Sessional',1,'CE',1,0,0,3),('CE192','2023-01-01','Chemistry Lab','Sessional',1,'CE',1,0,0,3),('CE193','2023-01-01','Drawing Sessional','Sessional',1,'CE',2,0,0,3),('CE201','2023-01-01','Mathematics II','Theory',2,'CE',4,3,1,0),('CE202','2023-01-01','Building Materials & Construction','Theory',2,'CE',3,3,0,0),('CE203','2023-01-01','Engineering Mechanics','Theory',2,'CE',4,3,1,0),('CE204','2023-01-01','Surveying I','Theory',2,'CE',3,3,0,0),('CE205','2023-01-01','Fluid Mechanics I','Theory',2,'CE',3,3,0,0),('CE291','2023-01-01','Building Materials Lab','Sessional',2,'CE',1,0,0,3),('CE292','2023-01-01','Surveying Lab I','Sessional',2,'CE',2,0,0,3),('CE293','2023-01-01','Fluid Mechanics Lab I','Sessional',2,'CE',1,0,0,3),('CE301','2023-01-01','Mathematics III','Theory',3,'CE',3,3,0,0),('CE302','2023-01-01','Mechanics of Solids','Theory',3,'CE',4,3,1,0),('CE303','2023-01-01','Fluid Mechanics II','Theory',3,'CE',4,3,1,0),('CE304','2023-01-01','Surveying II','Theory',3,'CE',3,3,0,0),('CE305','2023-01-01','Soil Mechanics','Theory',3,'CE',4,3,1,0),('CE391','2023-01-01','Mechanics of Solids Lab','Sessional',3,'CE',2,0,0,3),('CE392','2023-01-01','Fluid Mechanics Lab II','Sessional',3,'CE',2,0,0,3),('CE393','2023-01-01','Soil Mechanics Lab','Sessional',3,'CE',1,0,0,3),('CE401','2023-01-01','Structural Analysis I','Theory',4,'CE',4,3,1,0),('CE402','2023-01-01','Foundation Engineering','Theory',4,'CE',4,3,1,0),('CE403','2023-01-01','Transportation Engineering I','Theory',4,'CE',3,3,0,0),('CE404','2023-01-01','Water Resources Engineering','Theory',4,'CE',4,3,1,0),('CE405','2023-01-01','Environmental Engineering I','Theory',4,'CE',3,3,0,0),('CE491','2023-01-01','Structural Analysis Lab','Sessional',4,'CE',2,0,0,3),('CE492','2023-01-01','Foundation Engg Lab','Sessional',4,'CE',2,0,0,3),('CE493','2023-01-01','Surveying Lab II','Sessional',4,'CE',1,0,0,3),('CE501','2023-01-01','Structural Analysis II','Theory',5,'CE',4,3,1,0),('CE502','2023-01-01','Design of Steel Structures','Theory',5,'CE',4,3,1,0),('CE503','2023-01-01','Environmental Engineering II','Theory',5,'CE',3,3,0,0),('CE504','2023-01-01','Elective I','Theory',5,'CE',3,3,0,0),('CE505','2023-01-01','Elective II','Theory',5,'CE',3,3,0,0),('CE591','2023-01-01','Concrete Technology Lab','Sessional',5,'CE',2,0,0,3),('CE592','2023-01-01','Environmental Engg Lab','Sessional',5,'CE',2,0,0,3),('CE593','2023-01-01','Mini Project I','Sessional',5,'CE',2,0,0,3),('CE601','2023-01-01','Design of RCC Structures','Theory',6,'CE',4,3,1,0),('CE602','2023-01-01','Transportation Engineering II','Theory',6,'CE',3,3,0,0),('CE603','2023-01-01','Elective III','Theory',6,'CE',3,3,0,0),('CE604','2023-01-01','Elective IV','Theory',6,'CE',3,3,0,0),('CE605','2023-01-01','Open Elective I','Theory',6,'CE',3,3,0,0),('CE691','2023-01-01','RCC Lab','Sessional',6,'CE',2,0,0,3),('CE692','2023-01-01','Mini Project II','Sessional',6,'CE',2,0,0,3),('CE693','2023-01-01','Seminar','Sessional',6,'CE',1,0,0,0),('CE701','2023-01-01','Elective V','Theory',7,'CE',3,3,0,0),('CE702','2023-01-01','Open Elective II','Theory',7,'CE',3,3,0,0),('CE791','2023-01-01','Project Work Phase I','Sessional',7,'CE',6,0,0,12),('CE801','2023-01-01','Elective VI','Theory',8,'CE',3,3,0,0),('CE891','2023-01-01','Project Work Phase II','Sessional',8,'CE',10,0,0,20),('CE892','2023-01-01','Comprehensive Viva','Sessional',8,'CE',2,0,0,0),('CST101','2023-01-01','Mathematics I','Theory',1,'CST',4,3,1,0),('CST102','2023-01-01','Engineering Physics','Theory',1,'CST',4,3,1,0),('CST103','2023-01-01','Engineering Chemistry','Theory',1,'CST',3,3,0,0),('CST104','2023-01-01','English for Communication','Theory',1,'CST',2,2,0,0),('CST105','2023-01-01','Basics of Electrical Engineering','Theory',1,'CST',3,3,0,0),('CST191','2023-01-01','Engineering Physics Lab','Sessional',1,'CST',1,0,0,3),('CST192','2023-01-01','Engineering Chemistry Lab','Sessional',1,'CST',1,0,0,3),('CST193','2023-01-01','Workshop Practice','Sessional',1,'CST',1,0,0,3),('CST201','2023-01-01','Mathematics II','Theory',2,'CST',4,3,1,0),('CST202','2023-01-01','Data Structures','Theory',2,'CST',4,3,1,0),('CST203','2023-01-01','Digital Electronics','Theory',2,'CST',4,3,1,0),('CST204','2023-01-01','Object Oriented Programming','Theory',2,'CST',3,3,0,0),('CST205','2023-01-01','Discrete Mathematics','Theory',2,'CST',3,3,0,0),('CST291','2023-01-01','Data Structures Lab','Sessional',2,'CST',2,0,0,3),('CST292','2023-01-01','OOP Lab (Java/C++)','Sessional',2,'CST',2,0,0,3),('CST293','2023-01-01','Digital Electronics Lab','Sessional',2,'CST',1,0,0,3),('CST301','2023-01-01','Mathematics III (Probability & Stats)','Theory',3,'CST',4,3,1,0),('CST302','2023-01-01','Algorithms','Theory',3,'CST',4,3,1,0),('CST303','2023-01-01','Computer Organization & Architecture','Theory',3,'CST',4,3,1,0),('CST304','2023-01-01','Operating Systems','Theory',3,'CST',4,3,1,0),('CST305','2023-01-01','Database Management Systems','Theory',3,'CST',3,3,0,0),('CST391','2023-01-01','Algorithms Lab','Sessional',3,'CST',2,0,0,3),('CST392','2023-01-01','OS Lab','Sessional',3,'CST',2,0,0,3),('CST393','2023-01-01','DBMS Lab','Sessional',3,'CST',1,0,0,3),('CST401','2023-01-01','Computer Networks','Theory',4,'CST',4,3,1,0),('CST402','2023-01-01','Theory of Computation','Theory',4,'CST',4,3,1,0),('CST403','2023-01-01','Software Engineering','Theory',4,'CST',3,3,0,0),('CST404','2023-01-01','Microprocessors & Interfacing','Theory',4,'CST',4,3,1,0),('CST405','2023-01-01','Numerical Methods','Theory',4,'CST',3,3,0,0),('CST491','2023-01-01','Networks Lab','Sessional',4,'CST',2,0,0,3),('CST492','2023-01-01','Microprocessors Lab','Sessional',4,'CST',2,0,0,3),('CST493','2023-01-01','Software Engineering Lab','Sessional',4,'CST',1,0,0,3),('CST501','2023-01-01','Compiler Design','Theory',5,'CST',4,3,1,0),('CST502','2023-01-01','Artificial Intelligence','Theory',5,'CST',4,3,1,0),('CST503','2023-01-01','Information Security','Theory',5,'CST',3,3,0,0),('CST504','2023-01-01','Elective I','Theory',5,'CST',3,3,0,0),('CST505','2023-01-01','Elective II','Theory',5,'CST',3,3,0,0),('CST591','2023-01-01','Compiler Lab','Sessional',5,'CST',2,0,0,3),('CST592','2023-01-01','AI Lab','Sessional',5,'CST',2,0,0,3),('CST593','2023-01-01','Mini Project I','Sessional',5,'CST',2,0,0,3),('CST601','2023-01-01','Machine Learning','Theory',6,'CST',4,3,1,0),('CST602','2023-01-01','Distributed Systems','Theory',6,'CST',3,3,0,0),('CST603','2023-01-01','Elective III','Theory',6,'CST',3,3,0,0),('CST604','2023-01-01','Elective IV','Theory',6,'CST',3,3,0,0),('CST605','2023-01-01','Open Elective I','Theory',6,'CST',3,3,0,0),('CST691','2023-01-01','ML Lab','Sessional',6,'CST',2,0,0,3),('CST692','2023-01-01','Mini Project II','Sessional',6,'CST',2,0,0,3),('CST693','2023-01-01','Seminar','Sessional',6,'CST',1,0,0,0),('CST701','2023-01-01','Big Data Analytics','Theory',7,'CST',3,3,0,0),('CST702','2023-01-01','Cloud Computing','Theory',7,'CST',3,3,0,0),('CST703','2023-01-01','Elective V','Theory',7,'CST',3,3,0,0),('CST704','2023-01-01','Open Elective II','Theory',7,'CST',3,3,0,0),('CST791','2023-01-01','Project Work Phase I','Sessional',7,'CST',6,0,0,12),('CST801','2023-01-01','Elective VI','Theory',8,'CST',3,3,0,0),('CST891','2023-01-01','Project Work Phase II','Sessional',8,'CST',10,0,0,20),('CST892','2023-01-01','Comprehensive Viva','Sessional',8,'CST',2,0,0,0),('ECE101','2023-01-01','Mathematics I','Theory',1,'ECE',4,3,1,0),('ECE102','2023-01-01','Engineering Physics','Theory',1,'ECE',4,3,1,0),('ECE103','2023-01-01','Engineering Chemistry','Theory',1,'ECE',3,3,0,0),('ECE104','2023-01-01','English for Communication','Theory',1,'ECE',2,2,0,0),('ECE105','2023-01-01','Basic Electrical Engineering','Theory',1,'ECE',3,3,0,0),('ECE191','2023-01-01','Physics Lab','Sessional',1,'ECE',1,0,0,3),('ECE192','2023-01-01','Chemistry Lab','Sessional',1,'ECE',1,0,0,3),('ECE193','2023-01-01','Workshop Practice','Sessional',1,'ECE',1,0,0,3),('ECE201','2023-01-01','Mathematics II','Theory',2,'ECE',4,3,1,0),('ECE202','2023-01-01','Circuit Theory','Theory',2,'ECE',4,3,1,0),('ECE203','2023-01-01','Electronic Devices & Circuits','Theory',2,'ECE',4,3,1,0),('ECE204','2023-01-01','Digital Electronics','Theory',2,'ECE',3,3,0,0),('ECE205','2023-01-01','Programming in C','Theory',2,'ECE',3,3,0,0),('ECE291','2023-01-01','Circuits Lab','Sessional',2,'ECE',2,0,0,3),('ECE292','2023-01-01','Electronic Devices Lab','Sessional',2,'ECE',2,0,0,3),('ECE293','2023-01-01','Digital Electronics Lab','Sessional',2,'ECE',1,0,0,3),('ECE301','2023-01-01','Mathematics III (Signals & Systems)','Theory',3,'ECE',4,3,1,0),('ECE302','2023-01-01','Analog Communication','Theory',3,'ECE',4,3,1,0),('ECE303','2023-01-01','Electromagnetic Fields & Waves','Theory',3,'ECE',4,3,1,0),('ECE304','2023-01-01','Microprocessors','Theory',3,'ECE',4,3,1,0),('ECE305','2023-01-01','Network Theory','Theory',3,'ECE',3,3,0,0),('ECE391','2023-01-01','Communication Lab I','Sessional',3,'ECE',2,0,0,3),('ECE392','2023-01-01','Microprocessors Lab','Sessional',3,'ECE',2,0,0,3),('ECE393','2023-01-01','Network Lab','Sessional',3,'ECE',1,0,0,3),('ECE401','2023-01-01','Digital Communication','Theory',4,'ECE',4,3,1,0),('ECE402','2023-01-01','VLSI Design','Theory',4,'ECE',4,3,1,0),('ECE403','2023-01-01','Antenna & Wave Propagation','Theory',4,'ECE',4,3,1,0),('ECE404','2023-01-01','Control Systems','Theory',4,'ECE',3,3,0,0),('ECE405','2023-01-01','Digital Signal Processing','Theory',4,'ECE',4,3,1,0),('ECE491','2023-01-01','VLSI Lab','Sessional',4,'ECE',2,0,0,3),('ECE492','2023-01-01','DSP Lab','Sessional',4,'ECE',2,0,0,3),('ECE493','2023-01-01','Communication Lab II','Sessional',4,'ECE',1,0,0,3),('ECE501','2023-01-01','Wireless Communication','Theory',5,'ECE',4,3,1,0),('ECE502','2023-01-01','Microwave Engineering','Theory',5,'ECE',4,3,1,0),('ECE503','2023-01-01','Embedded Systems','Theory',5,'ECE',3,3,0,0),('ECE504','2023-01-01','Elective I','Theory',5,'ECE',3,3,0,0),('ECE505','2023-01-01','Elective II','Theory',5,'ECE',3,3,0,0),('ECE591','2023-01-01','Wireless Lab','Sessional',5,'ECE',2,0,0,3),('ECE592','2023-01-01','Embedded Systems Lab','Sessional',5,'ECE',2,0,0,3),('ECE593','2023-01-01','Mini Project I','Sessional',5,'ECE',2,0,0,3),('ECE601','2023-01-01','Optical Fiber Communication','Theory',6,'ECE',3,3,0,0),('ECE602','2023-01-01','IoT & Sensor Networks','Theory',6,'ECE',3,3,0,0),('ECE603','2023-01-01','Elective III','Theory',6,'ECE',3,3,0,0),('ECE604','2023-01-01','Elective IV','Theory',6,'ECE',3,3,0,0),('ECE605','2023-01-01','Open Elective I','Theory',6,'ECE',3,3,0,0),('ECE691','2023-01-01','Optical Comm Lab','Sessional',6,'ECE',2,0,0,3),('ECE692','2023-01-01','Mini Project II','Sessional',6,'ECE',2,0,0,3),('ECE693','2023-01-01','Seminar','Sessional',6,'ECE',1,0,0,0),('ECE701','2023-01-01','5G & Beyond','Theory',7,'ECE',3,3,0,0),('ECE702','2023-01-01','Elective V','Theory',7,'ECE',3,3,0,0),('ECE791','2023-01-01','Project Work Phase I','Sessional',7,'ECE',6,0,0,12),('ECE801','2023-01-01','Elective VI','Theory',8,'ECE',3,3,0,0),('ECE891','2023-01-01','Project Work Phase II','Sessional',8,'ECE',10,0,0,20),('ECE892','2023-01-01','Comprehensive Viva','Sessional',8,'ECE',2,0,0,0),('EE101','2023-01-01','Mathematics I','Theory',1,'EE',4,3,1,0),('EE102','2023-01-01','Engineering Physics','Theory',1,'EE',4,3,1,0),('EE103','2023-01-01','Engineering Chemistry','Theory',1,'EE',3,3,0,0),('EE104','2023-01-01','English for Communication','Theory',1,'EE',2,2,0,0),('EE105','2023-01-01','Engineering Drawing','Theory',1,'EE',2,2,0,0),('EE191','2023-01-01','Physics Lab','Sessional',1,'EE',1,0,0,3),('EE192','2023-01-01','Chemistry Lab','Sessional',1,'EE',1,0,0,3),('EE193','2023-01-01','Workshop Practice','Sessional',1,'EE',1,0,0,3),('EE201','2023-01-01','Mathematics II','Theory',2,'EE',4,3,1,0),('EE202','2023-01-01','Circuit Theory','Theory',2,'EE',4,3,1,0),('EE203','2023-01-01','Electromagnetic Theory','Theory',2,'EE',4,3,1,0),('EE204','2023-01-01','Electronics Devices & Circuits','Theory',2,'EE',3,3,0,0),('EE205','2023-01-01','Programming in C','Theory',2,'EE',3,3,0,0),('EE291','2023-01-01','Circuits Lab','Sessional',2,'EE',2,0,0,3),('EE292','2023-01-01','Electronics Lab','Sessional',2,'EE',2,0,0,3),('EE293','2023-01-01','Programming Lab','Sessional',2,'EE',1,0,0,3),('EE301','2023-01-01','Mathematics III','Theory',3,'EE',4,3,1,0),('EE302','2023-01-01','Electrical Machines I','Theory',3,'EE',4,3,1,0),('EE303','2023-01-01','Power Systems I','Theory',3,'EE',4,3,1,0),('EE304','2023-01-01','Signals & Systems','Theory',3,'EE',4,3,1,0),('EE305','2023-01-01','Analog Electronics','Theory',3,'EE',3,3,0,0),('EE391','2023-01-01','Electrical Machines Lab I','Sessional',3,'EE',2,0,0,3),('EE392','2023-01-01','Analog Electronics Lab','Sessional',3,'EE',2,0,0,3),('EE393','2023-01-01','Simulation Lab','Sessional',3,'EE',1,0,0,3),('EE401','2023-01-01','Electrical Machines II','Theory',4,'EE',4,3,1,0),('EE402','2023-01-01','Power Systems II','Theory',4,'EE',4,3,1,0),('EE403','2023-01-01','Control Systems','Theory',4,'EE',4,3,1,0),('EE404','2023-01-01','Digital Electronics','Theory',4,'EE',3,3,0,0),('EE405','2023-01-01','Measurement & Instrumentation','Theory',4,'EE',3,3,0,0),('EE491','2023-01-01','Electrical Machines Lab II','Sessional',4,'EE',2,0,0,3),('EE492','2023-01-01','Control Systems Lab','Sessional',4,'EE',2,0,0,3),('EE493','2023-01-01','Measurement Lab','Sessional',4,'EE',1,0,0,3),('EE501','2023-01-01','Power Electronics','Theory',5,'EE',4,3,1,0),('EE502','2023-01-01','Power System Protection','Theory',5,'EE',4,3,1,0),('EE503','2023-01-01','Electric Drives','Theory',5,'EE',4,3,1,0),('EE504','2023-01-01','Elective I','Theory',5,'EE',3,3,0,0),('EE505','2023-01-01','Elective II','Theory',5,'EE',3,3,0,0),('EE591','2023-01-01','Power Electronics Lab','Sessional',5,'EE',2,0,0,3),('EE592','2023-01-01','Electric Drives Lab','Sessional',5,'EE',2,0,0,3),('EE593','2023-01-01','Mini Project I','Sessional',5,'EE',2,0,0,3),('EE601','2023-01-01','High Voltage Engineering','Theory',6,'EE',3,3,0,0),('EE602','2023-01-01','Renewable Energy Systems','Theory',6,'EE',3,3,0,0),('EE603','2023-01-01','Elective III','Theory',6,'EE',3,3,0,0),('EE604','2023-01-01','Elective IV','Theory',6,'EE',3,3,0,0),('EE605','2023-01-01','Open Elective I','Theory',6,'EE',3,3,0,0),('EE691','2023-01-01','High Voltage Lab','Sessional',6,'EE',2,0,0,3),('EE692','2023-01-01','Mini Project II','Sessional',6,'EE',2,0,0,3),('EE693','2023-01-01','Seminar','Sessional',6,'EE',1,0,0,0),('EE701','2023-01-01','Smart Grid Technology','Theory',7,'EE',3,3,0,0),('EE702','2023-01-01','Elective V','Theory',7,'EE',3,3,0,0),('EE703','2023-01-01','Open Elective II','Theory',7,'EE',3,3,0,0),('EE791','2023-01-01','Project Work Phase I','Sessional',7,'EE',6,0,0,12),('EE801','2023-01-01','Elective VI','Theory',8,'EE',3,3,0,0),('EE891','2023-01-01','Project Work Phase II','Sessional',8,'EE',10,0,0,20),('EE892','2023-01-01','Comprehensive Viva','Sessional',8,'EE',2,0,0,0),('IT101','2023-01-01','Mathematics I','Theory',1,'IT',4,3,1,0),('IT102','2023-01-01','Engineering Physics','Theory',1,'IT',4,3,1,0),('IT103','2023-01-01','Engineering Chemistry','Theory',1,'IT',3,3,0,0),('IT104','2023-01-01','English for Communication','Theory',1,'IT',2,2,0,0),('IT105','2023-01-01','Basics of Electrical Engineering','Theory',1,'IT',3,3,0,0),('IT191','2023-01-01','Physics Lab','Sessional',1,'IT',1,0,0,3),('IT192','2023-01-01','Chemistry Lab','Sessional',1,'IT',1,0,0,3),('IT193','2023-01-01','Workshop Practice','Sessional',1,'IT',1,0,0,3),('IT201','2023-01-01','Mathematics II','Theory',2,'IT',4,3,1,0),('IT202','2023-01-01','Data Structures','Theory',2,'IT',4,3,1,0),('IT203','2023-01-01','Digital Logic & Computer Organization','Theory',2,'IT',4,3,1,0),('IT204','2023-01-01','Object Oriented Programming','Theory',2,'IT',3,3,0,0),('IT205','2023-01-01','Discrete Mathematics','Theory',2,'IT',3,3,0,0),('IT291','2023-01-01','Data Structures Lab','Sessional',2,'IT',2,0,0,3),('IT292','2023-01-01','OOP Lab','Sessional',2,'IT',2,0,0,3),('IT293','2023-01-01','Digital Logic Lab','Sessional',2,'IT',1,0,0,3),('IT301','2023-01-01','Mathematics III','Theory',3,'IT',3,3,0,0),('IT302','2023-01-01','Algorithms','Theory',3,'IT',4,3,1,0),('IT303','2023-01-01','Operating Systems','Theory',3,'IT',4,3,1,0),('IT304','2023-01-01','Database Systems','Theory',3,'IT',4,3,1,0),('IT305','2023-01-01','Web Technologies','Theory',3,'IT',3,3,0,0),('IT391','2023-01-01','Algorithms Lab','Sessional',3,'IT',2,0,0,3),('IT392','2023-01-01','OS Lab','Sessional',3,'IT',2,0,0,3),('IT393','2023-01-01','Web Technology Lab','Sessional',3,'IT',1,0,0,3),('IT401','2023-01-01','Computer Networks','Theory',4,'IT',4,3,1,0),('IT402','2023-01-01','Software Engineering','Theory',4,'IT',3,3,0,0),('IT403','2023-01-01','Information Security','Theory',4,'IT',4,3,1,0),('IT404','2023-01-01','Theory of Computation','Theory',4,'IT',4,3,1,0),('IT405','2023-01-01','Numerical Methods','Theory',4,'IT',3,3,0,0),('IT491','2023-01-01','Networks Lab','Sessional',4,'IT',2,0,0,3),('IT492','2023-01-01','Security Lab','Sessional',4,'IT',2,0,0,3),('IT493','2023-01-01','Software Engg Lab','Sessional',4,'IT',1,0,0,3),('IT501','2023-01-01','Machine Learning','Theory',5,'IT',4,3,1,0),('IT502','2023-01-01','Cloud Computing','Theory',5,'IT',3,3,0,0),('IT503','2023-01-01','Mobile Application Development','Theory',5,'IT',3,3,0,0),('IT504','2023-01-01','Elective I','Theory',5,'IT',3,3,0,0),('IT505','2023-01-01','Elective II','Theory',5,'IT',3,3,0,0),('IT591','2023-01-01','ML Lab','Sessional',5,'IT',2,0,0,3),('IT592','2023-01-01','Mobile Dev Lab','Sessional',5,'IT',2,0,0,3),('IT593','2023-01-01','Mini Project I','Sessional',5,'IT',2,0,0,3),('IT601','2023-01-01','Big Data & Analytics','Theory',6,'IT',3,3,0,0),('IT602','2023-01-01','IoT Systems','Theory',6,'IT',3,3,0,0),('IT603','2023-01-01','Elective III','Theory',6,'IT',3,3,0,0),('IT604','2023-01-01','Elective IV','Theory',6,'IT',3,3,0,0),('IT605','2023-01-01','Open Elective I','Theory',6,'IT',3,3,0,0),('IT691','2023-01-01','Big Data Lab','Sessional',6,'IT',2,0,0,3),('IT692','2023-01-01','Mini Project II','Sessional',6,'IT',2,0,0,3),('IT693','2023-01-01','Seminar','Sessional',6,'IT',1,0,0,0),('IT701','2023-01-01','Elective V','Theory',7,'IT',3,3,0,0),('IT702','2023-01-01','Open Elective II','Theory',7,'IT',3,3,0,0),('IT791','2023-01-01','Project Work Phase I','Sessional',7,'IT',6,0,0,12),('IT801','2023-01-01','Elective VI','Theory',8,'IT',3,3,0,0),('IT891','2023-01-01','Project Work Phase II','Sessional',8,'IT',10,0,0,20),('IT892','2023-01-01','Comprehensive Viva','Sessional',8,'IT',2,0,0,0),('MCA101','2023-01-01','Discrete Mathematics & Logic','Theory',1,'MCA',4,3,1,0),('MCA102','2023-01-01','Programming in C & C++','Theory',1,'MCA',4,3,1,0),('MCA103','2023-01-01','Data Structures','Theory',1,'MCA',4,3,1,0),('MCA104','2023-01-01','Computer Organization','Theory',1,'MCA',3,3,0,0),('MCA105','2023-01-01','Accounting & Financial Management','Theory',1,'MCA',3,3,0,0),('MCA191','2023-01-01','Programming Lab','Sessional',1,'MCA',2,0,0,3),('MCA192','2023-01-01','Data Structures Lab','Sessional',1,'MCA',2,0,0,3),('MCA201','2023-01-01','Operating Systems','Theory',2,'MCA',4,3,1,0),('MCA202','2023-01-01','Database Management Systems','Theory',2,'MCA',4,3,1,0),('MCA203','2023-01-01','Java Programming','Theory',2,'MCA',4,3,1,0),('MCA204','2023-01-01','Computer Networks','Theory',2,'MCA',3,3,0,0),('MCA205','2023-01-01','Software Engineering','Theory',2,'MCA',3,3,0,0),('MCA291','2023-01-01','DBMS Lab','Sessional',2,'MCA',2,0,0,3),('MCA292','2023-01-01','Java Lab','Sessional',2,'MCA',2,0,0,3),('MCA301','2023-01-01','Web Technologies','Theory',3,'MCA',4,3,1,0),('MCA302','2023-01-01','Machine Learning','Theory',3,'MCA',4,3,1,0),('MCA303','2023-01-01','Information Security','Theory',3,'MCA',3,3,0,0),('MCA304','2023-01-01','Elective I','Theory',3,'MCA',3,3,0,0),('MCA391','2023-01-01','Web Technologies Lab','Sessional',3,'MCA',2,0,0,3),('MCA392','2023-01-01','ML Lab','Sessional',3,'MCA',2,0,0,3),('MCA393','2023-01-01','Mini Project','Sessional',3,'MCA',2,0,0,3),('MCA401','2023-01-01','Elective II','Theory',4,'MCA',3,3,0,0),('MCA491','2023-01-01','Project Work','Sessional',4,'MCA',10,0,0,20),('MCA492','2023-01-01','Comprehensive Viva','Sessional',4,'MCA',2,0,0,0),('ME101','2023-01-01','Mathematics I','Theory',1,'ME',4,3,1,0),('ME102','2023-01-01','Engineering Physics','Theory',1,'ME',4,3,1,0),('ME103','2023-01-01','Engineering Chemistry','Theory',1,'ME',3,3,0,0),('ME104','2023-01-01','English for Communication','Theory',1,'ME',2,2,0,0),('ME105','2023-01-01','Engineering Drawing','Theory',1,'ME',3,2,1,0),('ME191','2023-01-01','Physics Lab','Sessional',1,'ME',1,0,0,3),('ME192','2023-01-01','Chemistry Lab','Sessional',1,'ME',1,0,0,3),('ME193','2023-01-01','Workshop Practice','Sessional',1,'ME',2,0,0,6),('ME201','2023-01-01','Mathematics II','Theory',2,'ME',4,3,1,0),('ME202','2023-01-01','Thermodynamics','Theory',2,'ME',4,3,1,0),('ME203','2023-01-01','Engineering Mechanics','Theory',2,'ME',4,3,1,0),('ME204','2023-01-01','Material Science','Theory',2,'ME',3,3,0,0),('ME205','2023-01-01','Basics of Electrical Engineering','Theory',2,'ME',3,3,0,0),('ME291','2023-01-01','Thermodynamics Lab','Sessional',2,'ME',1,0,0,3),('ME292','2023-01-01','Material Science Lab','Sessional',2,'ME',1,0,0,3),('ME293','2023-01-01','Computer Aided Drawing','Sessional',2,'ME',2,0,0,3),('ME301','2023-01-01','Mathematics III','Theory',3,'ME',3,3,0,0),('ME302','2023-01-01','Mechanics of Solids','Theory',3,'ME',4,3,1,0),('ME303','2023-01-01','Fluid Mechanics','Theory',3,'ME',4,3,1,0),('ME304','2023-01-01','Manufacturing Processes I','Theory',3,'ME',4,3,1,0),('ME305','2023-01-01','Kinematics of Machines','Theory',3,'ME',3,3,0,0),('ME391','2023-01-01','Fluid Mechanics Lab','Sessional',3,'ME',2,0,0,3),('ME392','2023-01-01','Manufacturing Processes Lab','Sessional',3,'ME',2,0,0,3),('ME393','2023-01-01','Mechanics of Solids Lab','Sessional',3,'ME',1,0,0,3),('ME401','2023-01-01','Heat Transfer','Theory',4,'ME',4,3,1,0),('ME402','2023-01-01','Dynamics of Machines','Theory',4,'ME',4,3,1,0),('ME403','2023-01-01','Manufacturing Processes II','Theory',4,'ME',4,3,1,0),('ME404','2023-01-01','Metrology & Quality Control','Theory',4,'ME',3,3,0,0),('ME405','2023-01-01','Industrial Engineering','Theory',4,'ME',3,3,0,0),('ME491','2023-01-01','Heat Transfer Lab','Sessional',4,'ME',2,0,0,3),('ME492','2023-01-01','Dynamics Lab','Sessional',4,'ME',2,0,0,3),('ME493','2023-01-01','Metrology Lab','Sessional',4,'ME',1,0,0,3),('ME501','2023-01-01','Refrigeration & Air Conditioning','Theory',5,'ME',4,3,1,0),('ME502','2023-01-01','Machine Design I','Theory',5,'ME',4,3,1,0),('ME503','2023-01-01','Turbomachinery','Theory',5,'ME',4,3,1,0),('ME504','2023-01-01','Elective I','Theory',5,'ME',3,3,0,0),('ME505','2023-01-01','Elective II','Theory',5,'ME',3,3,0,0),('ME591','2023-01-01','RAC Lab','Sessional',5,'ME',2,0,0,3),('ME592','2023-01-01','Machine Design Lab','Sessional',5,'ME',2,0,0,3),('ME593','2023-01-01','Mini Project I','Sessional',5,'ME',2,0,0,3),('ME601','2023-01-01','Finite Element Analysis','Theory',6,'ME',3,3,0,0),('ME602','2023-01-01','Machine Design II','Theory',6,'ME',4,3,1,0),('ME603','2023-01-01','Elective III','Theory',6,'ME',3,3,0,0),('ME604','2023-01-01','Elective IV','Theory',6,'ME',3,3,0,0),('ME605','2023-01-01','Open Elective I','Theory',6,'ME',3,3,0,0),('ME691','2023-01-01','FEA Lab','Sessional',6,'ME',2,0,0,3),('ME692','2023-01-01','Mini Project II','Sessional',6,'ME',2,0,0,3),('ME693','2023-01-01','Seminar','Sessional',6,'ME',1,0,0,0),('ME701','2023-01-01','Elective V','Theory',7,'ME',3,3,0,0),('ME702','2023-01-01','Open Elective II','Theory',7,'ME',3,3,0,0),('ME791','2023-01-01','Project Work Phase I','Sessional',7,'ME',6,0,0,12),('ME801','2023-01-01','Elective VI','Theory',8,'ME',3,3,0,0),('ME891','2023-01-01','Project Work Phase II','Sessional',8,'ME',10,0,0,20),('ME892','2023-01-01','Comprehensive Viva','Sessional',8,'ME',2,0,0,0);
/*!40000 ALTER TABLE `subjects_pool` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_security_answers`
--

DROP TABLE IF EXISTS `user_security_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_security_answers` (
  `security_ans_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `question_id` int NOT NULL,
  `answer_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`security_ans_id`),
  UNIQUE KEY `uniq_user_question` (`user_id`,`question_id`),
  KEY `idx_user_question` (`user_id`,`question_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `user_security_answers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_security_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `security_questions` (`security_questions_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_security_answers`
--

LOCK TABLES `user_security_answers` WRITE;
/*!40000 ALTER TABLE `user_security_answers` DISABLE KEYS */;
INSERT INTO `user_security_answers` VALUES (1,5,1,'$2y$10$c9YoGRyh6eokutfvSZNEruU0njdLl44BvXSrlXuMNTdcDvjGTVRxC','2026-03-17 19:36:54'),(2,5,6,'$2y$10$3yCw1KTfxAleR5CKq9DVMuIg73/3ccWRF50fnlVcxNRvD/YZPMKwy','2026-03-17 19:36:54'),(3,5,5,'$2y$10$amPVw1H1mb4wBje9umksp.vHRMx2LJawaWwX3HLDsavhoL2McWA2.','2026-03-17 19:36:54'),(4,7,1,'$2y$10$wGbtkJaXWnNrcUbgEpOIgukiZZJCOkAV2b0hPsba5uhJetfvVZtLK','2026-03-18 05:10:13'),(5,7,5,'$2y$10$SI/.zfdZL3tAFSC8NSlJpeZN2M5JR0acbKOCUpamOgHm1p.Kyph5.','2026-03-18 05:10:13'),(6,7,9,'$2y$10$e.ULeeCPoYVSlVDwN4tZbum.t5ay861njdD4t654Rhsq7bp1EekKq','2026-03-18 05:10:13'),(7,8,1,'$2y$10$VKYyomQZ/CdEKxYfkm489eCznOiTw1xn/IGlQ8WnKtwDACmqjZck.','2026-03-18 09:16:40'),(8,8,10,'$2y$10$V3VHr0biW8s3APl0YXpMb.Q3dnuJG8oBMaukwex/x7aT4t72th5Ta','2026-03-18 09:16:40'),(9,8,7,'$2y$10$Cv3.vSlmqheVD9HB2k.Dt.L1/nP.zRfGueqHQ0IC.SOjuQ7xj5Jv.','2026-03-18 09:16:40'),(10,10,1,'$2y$10$9A/YOjeAr0/Cck18kdLYRuPlMFWlR5QJm1fbVrsIw4hKpGDvDo82S','2026-03-31 18:49:03'),(11,10,5,'$2y$10$xCc8r5YAdz6nZA9.uCO5VOGa6lRHMRldnXTpjwvkVFAAHhZH.d2.2','2026-03-31 18:49:03'),(12,10,10,'$2y$10$mJCv7EFHCw/d/IKglZeGCuKX7o0EFBYCxNwl4gmPxOmyT3isBfuSy','2026-03-31 18:49:03');
/*!40000 ALTER TABLE `user_security_answers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `verification`
--

DROP TABLE IF EXISTS `verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verification` (
  `student_id` int NOT NULL,
  `emp_id` int NOT NULL,
  `verification_type` varchar(30) DEFAULT NULL,
  `verification_flag` bigint DEFAULT NULL,
  `verification_date` date NOT NULL,
  `verification_time` time NOT NULL,
  PRIMARY KEY (`student_id`,`verification_date`,`verification_time`),
  KEY `fk_verification_verifier` (`emp_id`),
  CONSTRAINT `fk_verification_verifier` FOREIGN KEY (`emp_id`) REFERENCES `index` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `verification_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student_info` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `verification`
--

LOCK TABLES `verification` WRITE;
/*!40000 ALTER TABLE `verification` DISABLE KEYS */;
INSERT INTO `verification` VALUES (10,6,'Profile',1,'2026-04-01','05:37:13');
/*!40000 ALTER TABLE `verification` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-16 18:54:30
