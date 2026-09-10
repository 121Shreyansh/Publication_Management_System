# 📚 Academic Publication & Institutional Record Management System

<div align="center">

[![Repository](https://img.shields.io/badge/GitHub-121Shreyansh%2FPublication__Management__System-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/121Shreyansh/Publication_Management_System)
[![License](https://img.shields.io/badge/License-MIT-00ff88?style=for-the-badge)](LICENSE)
[![Institution](https://img.shields.io/badge/Institution-IIEST_Shibpur-blue?style=for-the-badge)](https://www.iiest.ac.in/)

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![Python](https://img.shields.io/badge/Python-Data_Extraction-3776AB?style=flat-square&logo=python&logoColor=white)](https://www.python.org/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0_InnoDB-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Tesseract OCR](https://img.shields.io/badge/OCR-Tesseract_5.x-green?style=flat-square&logo=google)](https://github.com/tesseract-ocr/tesseract)
[![Poppler](https://img.shields.io/badge/Poppler-pdftoppm_300DPI-red?style=flat-square)](https://poppler.freedesktop.org/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![Composer](https://img.shields.io/badge/Composer-Dependencies-885630?style=flat-square&logo=composer&logoColor=white)](https://getcomposer.org/)

**A centralized academic research repository, co-author collaboration network, and computer-vision transcript marksheet digitization platform.**  
*Developed under the faculty guidance of **Prof. Surajeet Ghosh** at the **Indian Institute of Engineering Science and Technology (IIEST), Shibpur**.*

[Overview](#-project-overview--problem-statement) • [Key Technical Highlights](#-key-technical-highlights--engineering-impact) • [Features](#-core-features--functional-modules) • [System Architecture](#-system-architecture) • [OCR Pipeline](#-computer-vision-marksheet-ocr-pipeline) • [Database Architecture](#-database-architecture--schema-design) • [Installation](#-local-installation--setup-guide)

</div>

---

## 📖 Project Overview & Problem Statement

Higher education institutions face persistent administrative hurdles in managing faculty research portfolios and tracking student academic records:
* **Fragmented Research Archives:** Scholarly publications (Journals, Conferences, Book Chapters, Patents) are frequently scattered across personal websites, disconnected spreadsheets, and publisher portals, creating immense friction during **NIRF**, **NAAC**, and **NBA** accreditation audits.
* **Co-Authorship Redundancy:** Collaborative papers written across departments are often entered repeatedly by multiple authors, inflating departmental records and distorting citation metrics.
* **Manual Marksheet Transcription Bottlenecks:** Semester grade entry traditionally requires manual data entry from scanned PDF transcripts, resulting in typographical errors, mismatched subject codes, and hundreds of administrative labor hours per semester.

**The Academic Publication Management System** resolves these challenges by providing:
1. A **centralized scholarly research archive** categorizing multi-attribute publication metadata with DOI validation and real-time co-author invite/approval workflows.
2. An **automated computer-vision OCR data extraction pipeline** that parses scanned student marksheet PDFs, extracts subject codes, grades, and credits with fuzzy character matching, cross-validates computed grade points, and commits verified transcripts directly into MySQL.
3. An **administrative Head of Department (HOD) portal** for departmental verification, faculty onboarding, student academic progression tracking, and automated reporting (Excel, Word, PDF).

---

## 🎯 Key Technical Highlights & Engineering Impact

* **Collaborative Faculty-Guided Engineering:** Developed within a **6-student engineering team** under the academic supervision of **Prof. Surajeet Ghosh**, addressing real institutional administrative requirements at IIEST Shibpur.
* **Automated Marksheet Data Extraction Engine:** Spearheaded the custom OCR pipeline combining **Poppler utilities (`pdftoppm`)**, **Tesseract OCR**, and intelligent text parsing algorithms to parse marksheet tables and extract grades automatically.
* **Error-Resilient Fuzzy Character Parsing:** Formulated algorithmic heuristics utilizing **Levenshtein distance matching** and token canonicalization to resolve common optical scanning ambiguities (`O` ↔ `0`, `I`/`L` ↔ `1`, `AT`/`AY` ↔ `A+`, `BT` ↔ `B+`), achieving high-confidence automated grade extraction.
* **Mathematical Two-Way Cross-Validation:** Implemented independent mathematical checks calculating `earned_points / credit_hours = grade_points`, comparing the quotient against detected letter grades to ensure extracted data integrity.
* **Architected 3NF MySQL Database:** Designed and implemented a normalized relational schema with primary/foreign key cascading actions, unique constraints, and composite indexes to manage publication types, co-author graph relationships, student profiles, and official subject pools.
* **Complete Elimination of Manual Grade Entry:** Bridged the marksheet extraction pipeline directly with the student enrollment database, enforcing strict validation against authorized semester courses and completely removing manual transcription errors.

---

## 🚀 Core Features & Functional Modules

*(Detailed breakdown of Scholarly Publication Repository, Co-Author Collaboration Network, Marksheet OCR Digitization, HOD Governance, and Multi-Format Citation/Exports).*

---

## 🏗️ System Architecture

*(Full Mermaid architecture diagram detailing Browser Clients, Application Routing & Guards, OCR Subsystem, and MySQL Database Storage).*

---

## 🔍 Computer Vision Marksheet OCR Pipeline

*(Mathematical breakdown of 300 DPI rasterization, dual PSM 4 & 6 text extraction, OCR error healing, Levenshtein distance matching, and dual cross-validation).*

---

## 🗄️ Database Architecture & Schema Design

*(Includes Entity-Relationship diagram, detailed schema dictionary for all 13 tables, and integrity guarantees).*

---

## 🔒 Security Architecture & Defensive Design

*(Covers SQL injection immunity via PDO prepared statements, ACID transaction rollbacks, RBAC boundaries, sanitized shell execution via `escapeshellarg`, and temp directory isolation).*

---

## ⚙️ Local Installation & Setup Guide

*(Step-by-step setup for Linux/macOS prerequisites including Poppler and Tesseract, Composer dependencies, database creation, and local PHP server).*
