# 🔐 DILG-RC System - Account Credentials

## System Access Information
**Santa Cruz, Laguna - Road Clearing Violation Reporting System**

---

## 🏛️ DILG Administrator Account

**Role:** DILG Administrator  
**Email:** `admin@dilg.gov.ph`  
**Password:** `[removed-credential]`  
**Access Level:** Full system access - can view all 26 barangays  
**Dashboard:** http://localhost:8000/dilg-dashboard

---

## 🏘️ Barangay Staff Accounts (26 Total)

All barangay staff accounts use the same password: `[removed-credential]`

### Complete List of Santa Cruz, Laguna Barangays

| No. | Barangay Name | Email Address | Assigned Barangay |
|-----|---------------|---------------|-------------------|
| 1   | Alipit | alipit@barangay.dilg.gov.ph | Alipit |
| 2   | Bagumbayan | bagumbayan@barangay.dilg.gov.ph | Bagumbayan |
| 3   | Bubukal | bubukal@barangay.dilg.gov.ph | Bubukal |
| 4   | Calios | calios@barangay.dilg.gov.ph | Calios |
| 5   | Duhat | duhat@barangay.dilg.gov.ph | Duhat |
| 6   | Gatid | gatid@barangay.dilg.gov.ph | Gatid |
| 7   | Jasaan | jasaan@barangay.dilg.gov.ph | Jasaan |
| 8   | Labuin | labuin@barangay.dilg.gov.ph | Labuin |
| 9   | Malinao | malinao@barangay.dilg.gov.ph | Malinao |
| 10  | Oogong | oogong@barangay.dilg.gov.ph | Oogong |
| 11  | Pagsawitan | pagsawitan@barangay.dilg.gov.ph | Pagsawitan |
| 12  | Palasan | palasan@barangay.dilg.gov.ph | Palasan |
| 13  | Patimbao | patimbao@barangay.dilg.gov.ph | Patimbao |
| 14  | Poblacion I | poblacion-i@barangay.dilg.gov.ph | Poblacion I |
| 15  | Poblacion II | poblacion-ii@barangay.dilg.gov.ph | Poblacion II |
| 16  | Poblacion III | poblacion-iii@barangay.dilg.gov.ph | Poblacion III |
| 17  | Poblacion IV | poblacion-iv@barangay.dilg.gov.ph | Poblacion IV |
| 18  | Poblacion V | poblacion-v@barangay.dilg.gov.ph | Poblacion V |
| 19  | San Jose | san-jose@barangay.dilg.gov.ph | San Jose |
| 20  | San Juan | san-juan@barangay.dilg.gov.ph | San Juan |
| 21  | San Pablo Norte | san-pablo-norte@barangay.dilg.gov.ph | San Pablo Norte |
| 22  | San Pablo Sur | san-pablo-sur@barangay.dilg.gov.ph | San Pablo Sur |
| 23  | Santisima Cruz | santisima-cruz@barangay.dilg.gov.ph | Santisima Cruz |
| 24  | Santo Angel Central | santo-angel-central@barangay.dilg.gov.ph | Santo Angel Central |
| 25  | Santo Angel Norte | santo-angel-norte@barangay.dilg.gov.ph | Santo Angel Norte |
| 26  | Santo Angel Sur | santo-angel-sur@barangay.dilg.gov.ph | Santo Angel Sur |

---

## 🔒 Security Notes

- All accounts are secured with hashed passwords
- Role-based access control is enforced
- Barangay staff can ONLY access their assigned barangay
- Unauthorized access attempts will result in 403 Forbidden errors
- All login activities are tracked

---

## 📝 Testing Instructions

### Test DILG Admin Access
1. Login with: `admin@dilg.gov.ph` / `[removed-credential]`
2. Access all DILG admin features
3. View reports from all barangays

### Test Barangay Staff Access
1. Login with any barangay email (e.g., `poblacion-i@barangay.dilg.gov.ph`) / `[removed-credential]`
2. Can only access assigned barangay dashboard
3. Cannot view reports from other barangays

---

## ⚠️ Important Notes

- **For Proposal/Study Use Only**
- Change default passwords before production deployment
- Implement password complexity requirements for production
- Enable two-factor authentication for production use
- Regular security audits recommended

---

**System Version:** Phase 3C - Authentication & Role-Based Access  
**Database:** SQLite  
**Framework:** Laravel 11  
**Location:** Santa Cruz, Laguna  
**Total Barangays:** 26
