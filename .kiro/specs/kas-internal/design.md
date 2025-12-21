# Design Document - Kas Internal BEM

## Overview

Sistem Kas Internal adalah modul keuangan untuk mengelola pembayaran iuran kas bulanan anggota BEM. Sistem terintegrasi dengan Midtrans sebagai payment gateway dan menyediakan dashboard untuk Bendahara memantau pembayaran serta generate laporan.

**Key Features:**
- Pembayaran online via Midtrans (Snap)
- Auto-generate tagihan bulanan
- Sistem denda keterlambatan (Rp 500/hari)
- Reminder 7 hari sebelum deadline
- Dashboard monitoring untuk Bendahara
- Export laporan pembayaran

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        Filament Admin Panel                      │
├─────────────────┬─────────────────┬─────────────────────────────┤
│  KasPayment     │  KasSetting     │  KasReport                  │
│  Resource       │  Resource       │  (Dashboard Widget)         │
└────────┬────────┴────────┬────────┴──────────────┬──────────────┘
         │                 │                       │
         ▼                 ▼                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                         Services Layer                           │
├─────────────────┬─────────────────┬─────────────────────────────┤
│  MidtransService│  KasService     │  NotificationService        │
└────────┬────────┴────────┬────────┴──────────────┬──────────────┘
         │                 │                       │
         ▼                 ▼                       ▼
┌─────────────────────────────────────────────────────────────────┐
│                         Models Layer                             │
├─────────────────┬─────────────────┬─────────────────────────────┤
│  KasPayment     │  KasSetting     │  KasReminder                │
└─────────────────┴─────────────────┴─────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    External Services                             │
├─────────────────┬───────────────────────────────────────────────┤
│  Midtrans API   │  Laravel Scheduler (Cron Jobs)                │
└─────────────────┴───────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. MidtransService

Service untuk handle integrasi dengan Midtrans Snap API.

```php
interface MidtransServiceInterface
{
    public function createTransaction(KasPayment $payment): array;
    public function handleCallback(array $payload): bool;
    public function getTransactionStatus(string $orderId): array;
}
```

**Configuration:**
- Merchant ID: YOUR_MERCHANT_ID
- Client Key: YOUR_CLIENT_KEY
- Server Key: YOUR_SERVER_KEY

### 2. KasService

Service untuk business logic kas internal.

```php
interface KasServiceInterface
{
    public function generateMonthlyPayments(): void;
    public function calculatePenalty(KasPayment $payment): int;
    public function getTotalAmount(KasPayment $payment): int;
    public function markAsOverdue(): void;
    public function recordManualPayment(KasPayment $payment, User $bendahara, string $notes): bool;
}
```

### 3. NotificationService

Service untuk mengirim reminder pembayaran.

```php
interface NotificationServiceInterface
{
    public function sendPaymentReminder(User $user, KasPayment $payment): void;
    public function sendPaymentSuccess(User $user, KasPayment $payment): void;
}
```

### 4. Filament Resources

- **KasPaymentResource**: CRUD untuk pembayaran kas (Bendahara view all, Anggota view own)
- **KasSettingResource**: Konfigurasi nominal kas dan deadline (Bendahara only)
- **KasDashboardWidget**: Summary widget di dashboard Filament

### 5. API Endpoints

```
POST   /api/kas/pay                 - Initiate payment (returns Midtrans snap token)
POST   /api/kas/callback            - Midtrans webhook callback
GET    /api/kas/history             - Get user's payment history
GET    /api/kas/current             - Get current month payment status
```

## Data Models

### KasSetting

| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| nominal | integer | Nominal kas bulanan (dalam Rupiah) |
| deadline_day | integer | Tanggal deadline (default: 25) |
| penalty_per_day | integer | Denda per hari (default: 500) |
| reminder_days_before | integer | Hari sebelum deadline untuk reminder (default: 7) |
| is_active | boolean | Status aktif setting |
| created_at | timestamp | |
| updated_at | timestamp | |

### KasPayment

| Field | Type | Description |
|-------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | FK to users |
| period_month | integer | Bulan periode (1-12) |
| period_year | integer | Tahun periode |
| amount | integer | Nominal kas |
| penalty | integer | Total denda (default: 0) |
| total_amount | integer | amount + penalty |
| status | enum | pending, paid, overdue |
| payment_method | string | midtrans, manual, cash |
| midtrans_order_id | string | Order ID dari Midtrans |
| midtrans_transaction_id | string | Transaction ID dari Midtrans |
| paid_at | timestamp | Waktu pembayaran |
| processed_by | bigint | FK to users (untuk manual payment) |
| notes | text | Catatan (untuk manual payment) |
| created_at | timestamp | |
| updated_at | timestamp | |

### Relationships

```
User (1) ──────< (N) KasPayment
User (1) ──────< (N) KasPayment (as processor for manual payments)
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Penalty Calculation Consistency
*For any* overdue KasPayment, the penalty amount SHALL equal (days_overdue × penalty_per_day) where days_overdue is calculated from deadline date to current date or paid_at date.
**Validates: Requirements 5.4, 5.5**

### Property 2: No Duplicate Payment Per Period
*For any* user and period (month/year combination), there SHALL exist at most one KasPayment record.
**Validates: Requirements 1.5**

### Property 3: Payment Status Transition Validity
*For any* KasPayment, status transitions SHALL only follow: pending → paid, pending → overdue, overdue → paid. No other transitions are valid.
**Validates: Requirements 1.3, 1.4, 5.1**

### Property 4: Total Amount Calculation
*For any* KasPayment, total_amount SHALL always equal (amount + penalty).
**Validates: Requirements 5.5**

### Property 5: Manual Payment Audit Trail
*For any* KasPayment with payment_method = 'manual', processed_by SHALL NOT be null and notes SHALL NOT be empty.
**Validates: Requirements 6.2, 6.3**

### Property 6: Monthly Payment Generation Completeness
*For any* month when generateMonthlyPayments() is executed, every active user SHALL have exactly one KasPayment record for that period.
**Validates: Requirements 5.2**

## Error Handling

| Scenario | Handling |
|----------|----------|
| Midtrans API timeout | Retry 3x with exponential backoff, log error, notify admin |
| Duplicate callback | Check existing transaction status, ignore if already processed |
| Invalid callback signature | Reject request, log security warning |
| Payment for non-existent user | Return 404, log warning |
| Database transaction failure | Rollback, return error response |
| Setting not configured | Use default values (nominal: 0, deadline: 25, penalty: 500) |

## Testing Strategy

### Unit Tests
- KasService: penalty calculation, total amount calculation
- MidtransService: transaction creation, callback handling
- Model validations and relationships

### Property-Based Tests
Using PHPUnit with data providers for property-based testing approach:

1. **Penalty Calculation Property Test**
   - Generate random overdue days (1-365)
   - Verify penalty = days × penalty_rate

2. **Duplicate Prevention Property Test**
   - Generate random user/period combinations
   - Verify constraint prevents duplicates

3. **Status Transition Property Test**
   - Generate random status transition sequences
   - Verify only valid transitions succeed

4. **Total Amount Property Test**
   - Generate random amount and penalty values
   - Verify total_amount = amount + penalty

### Integration Tests
- Full payment flow with Midtrans sandbox
- Callback processing
- Monthly payment generation via scheduler

### Test Configuration
- Use Midtrans Sandbox environment for testing
- Mock external API calls in unit tests
- Use database transactions for test isolation
