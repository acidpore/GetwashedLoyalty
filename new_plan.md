1. EXECUTIVE SUMMARY
Current System
Single loyalty program for Car Wash only with basic QR check-in

New Requirements
Dual loyalty system supporting:

Car Wash loyalty program
Coffee Shop loyalty program
Combined QR codes for earning points in both programs simultaneously
Separate admin dashboards and statistics for each program
Unified customer accounts across both programs
Approach
Combined QR Code Generator system where admin generates permanent QR codes that customers can scan to earn loyalty points. Each QR code is tagged with a loyalty type (carwash, coffeeshop, or both).

2. BUSINESS LOGIC
Customer Account Structure
One customer account can participate in BOTH loyalty programs
Points are tracked separately for each program
Example: John has 3 Car Wash points AND 2 Coffee Shop points
QR Code Types
Admin can generate three types of permanent QR codes:

CAR WASH ONLY

Customer scans at car wash location
Earns 1 point in Car Wash loyalty only
URL format: /checkin?type=carwash
COFFEE SHOP ONLY

Customer scans at coffee shop location
Earns 1 point in Coffee Shop loyalty only
URL format: /checkin?type=coffeeshop
BOTH (COMBINED)

Customer scans at combined location
Earns 1 point in Car Wash AND 1 point in Coffee Shop
URL format: /checkin?type=both
Points and Rewards
Car Wash Program
Threshold: 5 points = 1 reward
Reward: Discount on car wash service
Points reset to 0 after claiming reward
Independent from Coffee Shop points
Coffee Shop Program
Threshold: 5 points = 1 reward (configurable)
Reward: Free coffee or discount
Points reset to 0 after claiming reward
Independent from Car Wash points
Combined Check-in
Scanning "BOTH" QR gives:
+1 point to Car Wash loyalty
+1 point to Coffee Shop loyalty
Both programs evaluated independently for rewards
Possible to earn rewards in both programs simultaneously
WhatsApp Notifications
Single Loyalty Check-in
Message shows progress for that specific loyalty only

Both Loyalty Check-in
Single message showing progress for BOTH programs:

Car Wash points: X/5
Coffee Shop points: Y/5
Reward Achieved
Message indicates which program(s) earned rewards

3. DATABASE ARCHITECTURE
3.1 Modified Tables
Table: customers (MIGRATION REQUIRED)
BEFORE (Current):

- id
- user_id
- current_points
- total_visits
- last_visit_at
- created_at
- updated_at
AFTER (New):

- id
- user_id
- carwash_points
- carwash_total_visits
- carwash_last_visit_at
- coffeeshop_points
- coffeeshop_total_visits
- coffeeshop_last_visit_at
- created_at
- updated_at
CHANGES:

Rename current_points → carwash_points
Rename total_visits → carwash_total_visits
Rename last_visit_at → carwash_last_visit_at
Add coffeeshop_points (default 0)
Add coffeeshop_total_visits (default 0)
Add coffeeshop_last_visit_at (nullable)
Table: visit_histories (MIGRATION REQUIRED)
BEFORE:

- id
- customer_id
- points_earned
- visited_at
- ip_address
- created_at
- updated_at
AFTER:

- id
- customer_id
- loyalty_type ENUM('carwash', 'coffeeshop', 'both')
- points_earned
- visited_at
- ip_address
- created_at
- updated_at
CHANGES:

Add loyalty_type column
Existing records default to 'carwash'
Table: system_settings (NEW ENTRIES)
NEW SETTINGS:

carwash_reward_threshold (default: 5)
coffeeshop_reward_threshold (default: 5)
carwash_reward_message (default: "DISKON CAR WASH")
coffeeshop_reward_message (default: "GRATIS KOPI")
3.2 New Tables
Table: qr_codes
PURPOSE: Store generated QR codes for tracking and management

- id BIGINT PK AUTO_INCREMENT
- code VARCHAR(255) UNIQUE NOT NULL
- loyalty_type ENUM('carwash', 'coffeeshop', 'both') NOT NULL
- qr_type ENUM('permanent', 'onetime') DEFAULT 'permanent'
- name VARCHAR(255) NULLABLE
- location VARCHAR(255) NULLABLE
- is_active BOOLEAN DEFAULT true
- is_used BOOLEAN DEFAULT false
- expires_at DATETIME NULLABLE
- scan_count INT DEFAULT 0
- created_by BIGINT FK → users.id
- created_at TIMESTAMP
- updated_at TIMESTAMP
INDEXES:

code (unique)
loyalty_type
is_active
created_by
RELATIONSHIPS:

created_by REFERENCES users(id) ON DELETE SET NULL
4. MODEL UPDATES
4.1 Customer Model
NEW METHODS:

getCarwashPoints(): int
getCoffeeshopPoints(): int
getPoints(string $loyaltyType): int
hasCarwashReward(): bool
hasCoffeeshopReward(): bool
hasReward(string $loyaltyType): bool
pointsUntilCarwashReward(): int
pointsUntilCoffeeshopReward(): int
pointsUntilReward(string $loyaltyType): int
addCarwashPoints(int $points = 1): void
addCoffeeshopPoints(int $points = 1): void
addPoints(string $loyaltyType, int $points = 1): void
resetCarwashPoints(): void
resetCoffeeshopPoints(): void
resetPoints(string $loyaltyType): void
UPDATED METHODS:

Remove old hasEarnedReward(), pointsUntilReward(), addPoints(), resetPoints()
Replace with loyalty-type specific methods
4.2 VisitHistory Model
NEW ATTRIBUTES:

protected $fillable = [
    'customer_id',
    'loyalty_type',
    'points_earned',
    'visited_at',
    'ip_address',
];
protected $casts = [
    'visited_at' => 'datetime',
    'loyalty_type' => 'string',
];
NEW SCOPES:

scopeCarwash($query)
scopeCoffeeshop($query)
scopeBoth($query)
4.3 QrCode Model (NEW)
namespace App\Models;
class QrCode extends Model
{
    protected $fillable = [
        'code',
        'loyalty_type',
        'qr_type',
        'name',
        'location',
        'is_active',
        'is_used',
        'expires_at',
        'scan_count',
        'created_by',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'is_used' => 'boolean',
        'expires_at' => 'datetime',
        'scan_count' => 'integer',
    ];
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function incrementScan(): void
    {
        $this->increment('scan_count');
    }
    public function getUrlAttribute(): string
    {
        return url("/checkin?code={$this->code}");
    }
    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->qr_type === 'onetime' && $this->is_used) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        return true;
    }
}
5. CONTROLLER UPDATES
5.1 CheckinController
CURRENT FLOW:

Scan QR → /checkin
Fill form (name + phone)
Process check-in
Add points (single loyalty)
Send notification
NEW FLOW:

Scan QR → /checkin?code=ABC123 OR /checkin?type=carwash
System detects loyalty type from QR code
Fill form (name + phone) - loyalty type hidden
Process check-in based on loyalty type:
carwash → add carwash points
coffeeshop → add coffeeshop points
both → add BOTH points
Evaluate rewards for affected loyalty programs
Send combined notification
UPDATED METHODS:

public function index(Request $request)
{
    $loyaltyType = $this->detectLoyaltyType($request);
    $qrCode = $this->validateQrCode($request);
    
    return view('checkin', [
        'loyaltyType' => $loyaltyType,
        'qrCode' => $qrCode,
    ]);
}
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|min:3|max:255',
        'phone' => 'required|string|min:10|max:15',
        'loyalty_type' => 'required|in:carwash,coffeeshop,both',
        'qr_code' => 'nullable|exists:qr_codes,code',
    ]);
    $loyaltyType = $validated['loyalty_type'];
    
    // Process check-in
    DB::beginTransaction();
    
    $user = $this->findOrCreateUser($phone, $name);
    $customer = $this->findOrCreateCustomer($user->id);
    
    // Handle different loyalty types
    if ($loyaltyType === 'both') {
        $this->processMultiLoyaltyCheckin($customer, $request->ip());
    } else {
        $this->processSingleLoyaltyCheckin($customer, $loyaltyType, $request->ip());
    }
    
    // Increment QR scan count
    if ($validated['qr_code']) {
        $this->incrementQrScan($validated['qr_code']);
    }
    
    // Send notification
    $this->sendNotification($phone, $name, $customer, $loyaltyType);
    
    DB::commit();
    
    return redirect()->route('success', [
        'name' => $name,
        'loyalty_type' => $loyaltyType,
        'carwash_points' => $customer->carwash_points,
        'coffeeshop_points' => $customer->coffeeshop_points,
    ]);
}
private function detectLoyaltyType(Request $request): string
{
    if ($request->has('code')) {
        $qr = QrCode::where('code', $request->code)->first();
        return $qr?->loyalty_type ?? 'carwash';
    }
    
    return $request->get('type', 'carwash');
}
private function processSingleLoyaltyCheckin(Customer $customer, string $type, string $ip): void
{
    $customer->addPoints($type);
    
    VisitHistory::create([
        'customer_id' => $customer->id,
        'loyalty_type' => $type,
        'points_earned' => 1,
        'visited_at' => now(),
        'ip_address' => $ip,
    ]);
}
private function processMultiLoyaltyCheckin(Customer $customer, string $ip): void
{
    $customer->addPoints('carwash');
    $customer->addPoints('coffeeshop');
    
    VisitHistory::create([
        'customer_id' => $customer->id,
        'loyalty_type' => 'both',
        'points_earned' => 2,
        'visited_at' => now(),
        'ip_address' => $ip,
    ]);
}
5.2 QrCodeController (NEW)
namespace App\Http\Controllers;
use App\Models\QrCode;
use Illuminate\Support\Str;
class QrCodeController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'loyalty_type' => 'required|in:carwash,coffeeshop,both',
            'qr_type' => 'required|in:permanent,onetime',
            'name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);
        
        $code = Str::random(10);
        
        $qr = QrCode::create([
            'code' => $code,
            'loyalty_type' => $validated['loyalty_type'],
            'qr_type' => $validated['qr_type'],
            'name' => $validated['name'],
            'location' => $validated['location'],
            'created_by' => auth()->id(),
        ]);
        
        return response()->json([
            'success' => true,
            'qr_code' => $qr,
            'url' => $qr->url,
        ]);
    }
}
6. FILAMENT ADMIN PANEL RESTRUCTURE
6.1 Navigation Structure
NEW SIDEBAR MENU:

🏠 Dashboard (Overview of both programs)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🚗 CAR WASH LOYALTY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
├── Car Wash Customers
├── Car Wash Visits
├── Car Wash QR Codes
└── Car Wash Statistics
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
☕ COFFEE SHOP LOYALTY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
├── Coffee Shop Customers
├── Coffee Shop Visits
├── Coffee Shop QR Codes
└── Coffee Shop Statistics
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
⚙️ SYSTEM
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
├── All Customers
├── All Visit History
├── QR Code Manager
├── System Settings
└── Broadcasts
6.2 Resource Files
CREATE NEW RESOURCES:

app/Filament/Resources/Carwash/CarwashCustomerResource.php

Shows customers with carwash_points > 0
Filter by carwash points threshold
Export carwash customers only
app/Filament/Resources/Carwash/CarwashVisitResource.php

Shows visit_histories WHERE loyalty_type IN ('carwash', 'both')
Statistics for carwash visits
app/Filament/Resources/Carwash/CarwashQrCodeResource.php

Generate QR codes for carwash
View existing carwash QR codes
Download/Print QR codes
app/Filament/Resources/Coffeeshop/CoffeeshopCustomerResource.php

Shows customers with coffeeshop_points > 0
Filter by coffeeshop points threshold
app/Filament/Resources/Coffeeshop/CoffeeshopVisitResource.php

Shows visit_histories WHERE loyalty_type IN ('coffeeshop', 'both')
app/Filament/Resources/Coffeeshop/CoffeeshopQrCodeResource.php

Generate QR codes for coffeeshop
app/Filament/Resources/QrCodeResource.php

Manage ALL QR codes
Generate combined QR codes
View scan statistics
Toggle active/inactive
6.3 Widgets
CREATE NEW WIDGETS:

app/Filament/Widgets/CarwashStatsWidget.php

Total carwash customers
Total carwash visits
Customers ready for carwash reward
Average carwash points
app/Filament/Widgets/CoffeeshopStatsWidget.php

Total coffeeshop customers
Total coffeeshop visits
Customers ready for coffeeshop reward
Average coffeeshop points
app/Filament/Widgets/OverviewStatsWidget.php

Combined statistics
Total unique customers
Total QR codes generated
Most popular loyalty type
app/Filament/Widgets/QrCodeStatsWidget.php

Total QR codes
Most scanned QR codes
QR codes by type
7. WHATSAPP SERVICE UPDATES
7.1 Message Templates
Template 1: Car Wash Only Check-in
Halo {name}! 👋
✅ Car Wash Check-in Berhasil!
Poin Car Wash: {carwash_points}/{carwash_threshold} 🚗
Kumpulkan {points_remaining} poin lagi untuk DISKON!
Terima kasih! 🎉
Template 2: Coffee Shop Only Check-in
Halo {name}! 👋
✅ Coffee Shop Check-in Berhasil!
Poin Coffee Shop: {coffeeshop_points}/{coffeeshop_threshold} ☕
Kumpulkan {points_remaining} poin lagi untuk GRATIS KOPI!
Terima kasih! 🎉
Template 3: Both Check-in (No Reward)
Halo {name}! 👋
✅ Check-in Berhasil!
🚗 Car Wash: {carwash_points}/{carwash_threshold}
☕ Coffee Shop: {coffeeshop_points}/{coffeeshop_threshold}
Double poin! Keren! 🎁
Terima kasih! 🎉
Template 4: Car Wash Reward Achieved
🎉 SELAMAT {name}! 🎉
Kamu dapat DISKON CAR WASH! 🚗✨
Tunjukkan pesan ini ke kasir.
Poin Car Wash direset ke 0/{carwash_threshold}
Poin Coffee Shop: {coffeeshop_points}/{coffeeshop_threshold} ☕
Terima kasih sudah setia! 💙
Template 5: Coffee Shop Reward Achieved
🎉 SELAMAT {name}! 🎉
Kamu dapat GRATIS KOPI! ☕✨
Tunjukkan pesan ini ke kasir.
Poin Coffee Shop direset ke 0/{coffeeshop_threshold}
Poin Car Wash: {carwash_points}/{carwash_threshold} 🚗
Terima kasih sudah setia! 💙
Template 6: Both Rewards Achieved
🎊 DOUBLE REWARD! 🎊
SELAMAT {name}!
🚗 DISKON CAR WASH!
☕ GRATIS KOPI!
Tunjukkan pesan ini ke kasir untuk klaim BOTH rewards!
Kedua poin direset ke 0. Keep going! 🔥
Terima kasih! 💙
7.2 WhatsAppService Updates
public function sendLoyaltyNotification(
    string $phone, 
    string $name, 
    Customer $customer,
    string $loyaltyType
): bool {
    $message = $this->buildLoyaltyMessage($name, $customer, $loyaltyType);
    return $this->sendMessage($phone, $message);
}
private function buildLoyaltyMessage(
    string $name, 
    Customer $customer, 
    string $loyaltyType
): string {
    $carwashThreshold = SystemSetting::get('carwash_reward_threshold', 5);
    $coffeeshopThreshold = SystemSetting::get('coffeeshop_reward_threshold', 5);
    
    $carwashReward = $customer->hasReward('carwash');
    $coffeeshopReward = $customer->hasReward('coffeeshop');
    
    // Both rewards
    if ($carwashReward && $coffeeshopReward) {
        return $this->buildBothRewardsMessage($name, $customer);
    }
    
    // Car wash reward only
    if ($carwashReward) {
        return $this->buildCarwashRewardMessage($name, $customer, $coffeeshopThreshold);
    }
    
    // Coffee shop reward only
    if ($coffeeshopReward) {
        return $this->buildCoffeeshopRewardMessage($name, $customer, $carwashThreshold);
    }
    
    // No rewards - show progress
    return match($loyaltyType) {
        'carwash' => $this->buildCarwashProgressMessage($name, $customer, $carwashThreshold),
        'coffeeshop' => $this->buildCoffeeshopProgressMessage($name, $customer, $coffeeshopThreshold),
        'both' => $this->buildBothProgressMessage($name, $customer, $carwashThreshold, $coffeeshopThreshold),
    };
}
8. VIEWS UPDATES
8.1 checkin.blade.php
UPDATE TO INCLUDE:

Hidden field for loyalty_type
Hidden field for qr_code
Display loyalty type badge ("Car Wash", "Coffee Shop", or "Both")
Update form styling based on loyalty type
8.2 success.blade.php
UPDATE TO SHOW:

Different success messages based on loyalty_type
Progress bars for affected loyalties
Reward badges if threshold reached
Icon differentiation (car vs coffee cup)
8.3 customer/dashboard.blade.php
UPDATE TO DISPLAY:

TWO separate loyalty cards side by side
Car Wash loyalty progress
Coffee Shop loyalty progress
Combined visit history with type indicators
9. MIGRATION PLAN
9.1 Data Migration Strategy
CONCERN: Existing customers have current_points data

SOLUTION: Migrate to carwash_points

Schema::table('customers', function (Blueprint $table) {
    // Step 1: Add new columns
    $table->integer('carwash_points')->default(0)->after('user_id');
    $table->integer('carwash_total_visits')->default(0)->after('carwash_points');
    $table->datetime('carwash_last_visit_at')->nullable()->after('carwash_total_visits');
    
    $table->integer('coffeeshop_points')->default(0)->after('carwash_last_visit_at');
    $table->integer('coffeeshop_total_visits')->default(0)->after('coffeeshop_points');
    $table->datetime('coffeeshop_last_visit_at')->nullable()->after('coffeeshop_total_visits');
});
// Step 2: Migrate data
DB::table('customers')->update([
    'carwash_points' => DB::raw('current_points'),
    'carwash_total_visits' => DB::raw('total_visits'),
    'carwash_last_visit_at' => DB::raw('last_visit_at'),
]);
// Step 3: Drop old columns
Schema::table('customers', function (Blueprint $table) {
    $table->dropColumn(['current_points', 'total_visits', 'last_visit_at']);
});
9.2 Visit Histories Migration
Schema::table('visit_histories', function (Blueprint $table) {
    $table->enum('loyalty_type', ['carwash', 'coffeeshop', 'both'])
        ->default('carwash')
        ->after('customer_id');
});
// All existing records default to 'carwash'
DB::table('visit_histories')->update(['loyalty_type' => 'carwash']);
10. IMPLEMENTATION PHASES
Phase 1: Database Foundation (4-5 hours)
TASKS:

Create migration for customers table modification
Create migration for visit_histories table modification
Create migration for qr_codes table
Create migration for system_settings entries
Update all seeders
Test migrations on fresh database
Backup production database before migration
DELIVERABLES:

4 migration files
Updated seeders
Migration rollback plan
Phase 2: Models & Business Logic (4-5 hours)
TASKS:

Update Customer model with dual loyalty methods
Update VisitHistory model with loyalty_type
Create QrCode model
Update SystemSetting with new settings
Create unit tests for model methods
Update factories for testing
DELIVERABLES:

4 updated/new models
Model helper methods
Relationships configured
Phase 3: QR Code System (3-4 hours)
TASKS:

Create QrCodeResource for Filament
Implement QR code generation UI
Add QR code download/print feature
Create QR code validation logic
Add scan counter functionality
Build QR code statistics
DELIVERABLES:

QrCode Filament resource
QR generation interface
Printable QR codes (PDF/PNG)
Phase 4: Check-in Logic Refactor (5-6 hours)
TASKS:

Update CheckinController for dual loyalty
Implement loyalty type detection from QR
Create processSingleLoyaltyCheckin method
Create processMultiLoyaltyCheckin method
Update anti-spam logic per loyalty type
Add reward evaluation for both programs
Test all check-in scenarios
DELIVERABLES:

Refactored CheckinController
Support for all QR types
Proper reward handling
Phase 5: Admin Panel Restructure (6-8 hours)
TASKS:

Create navigation groups (Car Wash, Coffee Shop, System)
Create CarwashCustomerResource
Create CoffeeshopCustomerResource
Create CarwashVisitResource
Create CoffeeshopVisitResource
Create CarwashQrCodeResource
Create CoffeeshopQrCodeResource
Update existing resources to "All" category
Create 4 new widgets (stats for each program)
Configure filters and scopes
Add export functionality per loyalty
DELIVERABLES:

6 new Filament resources
4 new widgets
Organized navigation structure
Phase 6: WhatsApp Notifications (3-4 hours)
TASKS:

Create 6 message templates
Update WhatsAppService with buildLoyaltyMessage
Implement reward detection logic
Create template rendering methods
Test all notification scenarios
Add message preview in admin panel
DELIVERABLES:

6 message templates
Smart notification logic
Template preview feature
Phase 7: Frontend Updates (4-5 hours)
TASKS:

Update checkin.blade.php with loyalty badges
Update success.blade.php with dual loyalty display
Update customer/dashboard.blade.php with two cards
Add icons and styling for each loyalty type
Make responsive for mobile
Add progress animations
DELIVERABLES:

Updated check-in form
Updated success page
Updated customer dashboard
Phase 8: Testing & Quality Assurance (4-5 hours)
TASKS:

Test car wash only QR flow
Test coffee shop only QR flow
Test combined QR flow
Test reward scenarios (single and double)
Test WhatsApp notifications
Test admin panel filtering
Test QR code generation
Test data export
Cross-browser testing
Mobile device testing
DELIVERABLES:

Tested all user flows
Bug fixes completed
QA checklist signed off
Phase 9: Documentation & Deployment (2-3 hours)
TASKS:

Update README.md
Update PROJECT_PLAN.md
Create admin user guide
Create QR code printing guide
Create deployment checklist
Update API documentation (if any)
DELIVERABLES:

Complete documentation
User guides
Deployment ready
11. TOTAL ESTIMATED TIME
Phase	Hours
Phase 1: Database Foundation	4-5
Phase 2: Models & Business Logic	4-5
Phase 3: QR Code System	3-4
Phase 4: Check-in Logic	5-6
Phase 5: Admin Panel	6-8
Phase 6: WhatsApp	3-4
Phase 7: Frontend	4-5
Phase 8: Testing	4-5
Phase 9: Documentation	2-3
TOTAL	35-45 hours
REALISTIC TIMELINE: 5-6 working days (8 hours/day)

12. RISKS & MITIGATION
Risk 1: Data Loss During Migration
MITIGATION:

Full database backup before migration
Test migration on staging first
Implement rollback script
Verify data integrity after migration
Risk 2: Breaking Existing Functionality
MITIGATION:

Comprehensive testing plan
Maintain backward compatibility where possible
Feature flags for gradual rollout
Staging environment testing
Risk 3: QR Code Confusion
MITIGATION:

Clear labeling on printed QR codes
Different colors for each type
Admin training on QR generation
Customer education materials
Risk 4: Performance Issues with Dual Loyalty
MITIGATION:

Optimize database queries with proper indexing
Use eager loading for relationships
Cache system settings
Monitor query performance
13. SUCCESS CRITERIA
The implementation is considered successful when:

✅ Admin can generate QR codes for all three types
✅ Customer can scan and earn points in correct loyalty program
✅ Both loyalty points are tracked independently
✅ Rewards are calculated separately for each program
✅ WhatsApp notifications show correct loyalty status
✅ Admin panel shows separated statistics
✅ No data loss from migration
✅ All existing functionality still works
✅ Performance is acceptable (page load < 2s)
✅ Mobile responsive on all screens
14. POST-IMPLEMENTATION TASKS
AFTER DEPLOYMENT:

Monitor error logs for 1 week
Gather user feedback (admin and customers)
Optimize slow queries identified in logs
Create video tutorial for admin QR generation
Print and distribute QR codes to locations
Train staff on new dual loyalty system
Update maintenance SLA document
Schedule Phase 2 improvements (if any)
15. FUTURE ENHANCEMENTS (Out of Scope)
POTENTIAL FEATURES FOR PHASE 2:

QR code analytics dashboard
Expiring QR codes for promotions
Custom point values per QR code
Loyalty tier system (Bronze, Silver, Gold)
Referral program
Birthday rewards
Email notifications (backup for WhatsApp)
Mobile app integration
Analytics charts for admin
Automated marketing campaigns
16. APPROVAL REQUIRED
Before implementation begins, client must approve:

Database schema changes
Navigation structure
WhatsApp message templates
QR code design/format
Timeline and budget
Testing scope
Deployment plan
PREPARED BY: Development Team REVIEWED BY: [Client Name] STATUS: Awaiting Approval NEXT STEP: Client review and feedback

END OF DOCUMENT