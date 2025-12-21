# Requirements Document

## Introduction

Fitur Kas Internal adalah sistem manajemen pembayaran iuran kas bulanan untuk anggota BEM. Sistem ini memungkinkan anggota membayar kas melalui payment gateway Midtrans, dengan deadline pembayaran setiap tanggal 25. Bendahara dapat memantau status pembayaran seluruh anggota dan menghasilkan laporan keuangan.

## Glossary

- **Kas Internal**: Iuran bulanan yang wajib dibayar oleh setiap anggota BEM
- **Anggota**: User yang terdaftar dalam sistem BEM dan memiliki kewajiban membayar kas
- **Bendahara**: Role yang memiliki akses untuk mengelola dan memantau pembayaran kas
- **Periode Kas**: Rentang waktu satu bulan untuk pembayaran kas (tanggal 1 - 25)
- **Midtrans**: Payment gateway yang digunakan untuk memproses pembayaran online
- **Payment Callback**: Notifikasi dari Midtrans ketika status pembayaran berubah

## Requirements

### Requirement 1

**User Story:** As an anggota BEM, I want to pay my monthly kas through online payment, so that I can fulfill my kas obligation conveniently.

#### Acceptance Criteria

1. WHEN an anggota accesses the kas payment page THEN the System SHALL display the current month's kas amount and payment status
2. WHEN an anggota initiates a payment THEN the System SHALL create a Midtrans transaction and redirect to the payment page
3. WHEN Midtrans sends a payment callback THEN the System SHALL update the payment status accordingly
4. WHEN a payment is successful THEN the System SHALL record the transaction with timestamp and payment method
5. IF an anggota attempts to pay for an already paid period THEN the System SHALL prevent duplicate payment and display appropriate message

### Requirement 2

**User Story:** As a Bendahara, I want to view payment status of all anggota, so that I can monitor kas collection progress.

#### Acceptance Criteria

1. WHEN a Bendahara accesses the kas dashboard THEN the System SHALL display a list of all anggota with their payment status for the current month
2. WHEN viewing the payment list THEN the System SHALL show anggota name, ministry, payment status, payment date, and amount
3. WHEN a Bendahara filters by payment status THEN the System SHALL display only anggota matching the selected status (paid/unpaid)
4. WHEN a Bendahara filters by ministry THEN the System SHALL display only anggota from the selected ministry
5. WHEN a Bendahara exports the report THEN the System SHALL generate a downloadable file containing payment data

### Requirement 3

**User Story:** As a Bendahara, I want to configure kas settings, so that I can manage the kas amount and payment periods.

#### Acceptance Criteria

1. WHEN a Bendahara sets the monthly kas amount THEN the System SHALL apply the amount to all future payment periods
2. WHEN a Bendahara views kas configuration THEN the System SHALL display current kas amount and deadline date
3. IF a Bendahara changes kas amount mid-period THEN the System SHALL only apply changes to the next period

### Requirement 4

**User Story:** As an anggota BEM, I want to view my payment history, so that I can track my kas payment records.

#### Acceptance Criteria

1. WHEN an anggota accesses payment history THEN the System SHALL display all past kas payments with date, amount, and status
2. WHEN viewing payment details THEN the System SHALL show transaction ID, payment method, and timestamp
3. WHEN an anggota has unpaid periods THEN the System SHALL highlight overdue payments

### Requirement 5

**User Story:** As a system administrator, I want the system to handle payment deadlines automatically, so that overdue payments are tracked without manual intervention.

#### Acceptance Criteria

1. WHEN the current date passes the 25th of the month THEN the System SHALL mark unpaid kas as overdue
2. WHEN a new month begins THEN the System SHALL create new kas payment records for all active anggota
3. WHEN generating payment records THEN the System SHALL use the configured kas amount for that period
4. WHILE a payment is overdue THEN the System SHALL calculate penalty at Rp 500 per day
5. WHEN calculating total payment THEN the System SHALL add accumulated penalty to the base kas amount

### Requirement 8

**User Story:** As an anggota BEM, I want to receive payment reminders, so that I don't miss the payment deadline.

#### Acceptance Criteria

1. WHEN the date is 7 days before deadline (18th of month) THEN the System SHALL send reminder notification to unpaid anggota
2. WHEN sending reminder THEN the System SHALL include kas amount and deadline date in the notification
3. WHEN an anggota has already paid THEN the System SHALL exclude them from reminder notifications

### Requirement 6

**User Story:** As a Bendahara, I want to record manual/cash payments, so that I can accommodate anggota who pay directly.

#### Acceptance Criteria

1. WHEN a Bendahara records a manual payment THEN the System SHALL update the anggota's payment status to paid
2. WHEN recording manual payment THEN the System SHALL require payment proof or notes
3. WHEN a manual payment is recorded THEN the System SHALL log the Bendahara who processed it

### Requirement 7

**User Story:** As a Bendahara, I want to view kas financial summary, so that I can report total collection to the organization.

#### Acceptance Criteria

1. WHEN a Bendahara views the summary THEN the System SHALL display total collected amount for the current month
2. WHEN viewing summary THEN the System SHALL show percentage of anggota who have paid
3. WHEN a Bendahara selects a date range THEN the System SHALL calculate total collection for that period
