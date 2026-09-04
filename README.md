# SecureHostel Management System (CSE447 Project)

A secure, full-stack university hostel management web application built for **CSE447: Cryptography and Cryptanalysis (Spring 2026, BRAC University)**. The system incorporates custom-built, from-scratch cryptographic engines to ensure complete data confidentiality, user integrity, and strict access control without relying on built-in framework encryption helpers.

---

## 🚀 Key Features

* **Custom Asymmetric Encryption (RSA & ECC):** Implemented completely from scratch. RSA is used for user profile parameters and identification records, while Elliptic Curve Cryptography (ECC) handles hostel room applications, medical preferences, and accommodation data.
* **Row-Level Integrity (MAC):** Custom `MACEngine` implementing secure hashing-based Message Authentication Codes to detect any unauthorized modifications or database tampering.
* **Two-Factor Authentication (2FA):** Multi-step login flow requiring primary credential validation followed by a time-sensitive 6-digit OTP dispatched directly to the user's Gmail inbox.
* **Password Hashing & Salting:** Secure per-user salt generation and hashing managed via `AuthEngine` to prevent dictionary and rainbow table attacks.
* **Key Management Module (KMM):** Dedicated key manager handling generation, active storage, and rotation protocols.
* **Role-Based Access Control (RBAC):** Distinct administrative, warden, and student privilege boundaries for managing applications, maintenance tickets, and accounts.
* **Secure Messenger Chat:** Real-time floating community chat widget featuring end-to-end ECC message encryption.
* **Modern UI:** Styled using Tailwind CSS with an interactive responsive layout, glassmorphic touches, and a campus-themed background login portal.

---

## 🛠️ Technology Stack

* **Backend Framework:** Laravel (PHP)
* **Frontend:** Blade Templates, Tailwind CSS, JavaScript (Fetch API)
* **Database:** MySQL
* **Cryptographic Layer:** Custom PHP Services (`RSAEngine`, `ECCEngine`, `MACEngine`, `KeyManager`, `AuthEngine`)

---

## ⚙️ Prerequisites & System Requirements

Before running the project, ensure your environment meets the following requirements:
* PHP >= 8.2
* Composer
* Node.js & NPM (optional, if compiling custom assets)
* MySQL / MariaDB

---

## 📥 Installation & Setup Instructions

Follow these steps to set up and run the project locally:

1. **Clone the Repository:**
   ```bash
   git clone [https://github.com/your-username/secure-hostel-management.git](https://github.com/your-username/secure-hostel-management.git)
   cd secure-hostel-management
