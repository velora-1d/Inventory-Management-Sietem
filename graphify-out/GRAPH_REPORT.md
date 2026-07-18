# Graph Report - .  (2026-07-17)

## Corpus Check
- 261 files · ~63,952 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1100 nodes · 1863 edges · 148 communities (128 shown, 20 thin omitted)
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 102 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Illuminate\Http\Request
- Purchase
- Illuminate\Database\Eloquent\Builder
- Livewire\Component
- composer.json
- Product
- Unit
- Illuminate\Database\Seeder
- Customer
- Exception
- devDependencies
- Category
- Inventory Management System
- scripts
- DashboardStatsService
- Illuminate\Database\Eloquent\Factories\Factory
- Illuminate\Foundation\Http\FormRequest
- FinanceTransaction
- TestCase
- UserForm
- Illuminate\Database\Eloquent\Factories\HasFactory
- Sale
- FinanceTransactionService
- ProfileService
- User
- SaleData
- .fromArray
- SaleException
- FinanceTransactionData
- SalesTable
- User.php
- FinanceReportController.php
- AuthenticationTest
- PasswordResetTest
- StoreSaleRequest
- Illuminate\Database\Eloquent\Relations\HasMany
- AppServiceProvider
- index.blade.php
- index.blade.php
- index.blade.php
- index.blade.php
- app.blade.php
- ExampleTest
- index.blade.php
- index.blade.php
- index.blade.php
- index.blade.php
- index.blade.php
- index.blade.php
- index.blade.php
- index.blade.php
- confirm
- dashboard.dashboard
- $refresh
- create.blade.php
- edit.blade.php

## God Nodes (most connected - your core abstractions)
1. `User` - 45 edges
2. `Controller` - 38 edges
3. `Purchase` - 34 edges
4. `Sale` - 30 edges
5. `FinanceTransaction` - 28 edges
6. `Product` - 27 edges
7. `FinanceCategory` - 24 edges
8. `Category` - 22 edges
9. `Unit` - 22 edges
10. `Customer` - 20 edges

## Surprising Connections (you probably didn't know these)
- `Application Logo (SVG)` --part_of--> `Inventory Management System`  [EXTRACTED]
  /home/pak-hakim/Hakim/Project/inventory-management-system/public/images/logo.svg → /home/pak-hakim/Hakim/Project/inventory-management-system/README.md
- `Advanced Analytics Dashboard` --implements--> `Overview / Dashboard Page`  [INFERRED]
  /home/pak-hakim/Hakim/Project/inventory-management-system/README.md → /home/pak-hakim/Hakim/Project/inventory-management-system/public/images/screenshot.png
- `Dashboard Screenshot` --part_of--> `Inventory Management System`  [EXTRACTED]
  /home/pak-hakim/Hakim/Project/inventory-management-system/public/images/screenshot.png → /home/pak-hakim/Hakim/Project/inventory-management-system/README.md
- `Fajar Ghifar` --implements--> `KencanaPOS Brand`  [EXTRACTED]
  /home/pak-hakim/Hakim/Project/inventory-management-system/README.md → /home/pak-hakim/Hakim/Project/inventory-management-system/public/images/screenshot.png
- `format_money()` --calls--> `Setting`  [EXTRACTED]
  app/Helpers/CurrencyHelper.php → app/Models/Setting.php

## Import Cycles
- None detected.

## Communities (148 total, 20 thin omitted)

### Community 0 - "Illuminate\Http\Request"
Cohesion: 0.07
Nodes (24): CategoryController, FinanceCategoryController, ProductController, SupplierController, UnitController, UserController, AuthenticatedSessionController, ConfirmablePasswordController (+16 more)

### Community 1 - "Purchase"
Cohesion: 0.06
Nodes (14): Carbon, self, PurchaseData, self, PurchaseItemData, self, PurchaseException, format_money() (+6 more)

### Community 2 - "Illuminate\Database\Eloquent\Builder"
Cohesion: 0.06
Nodes (11): CategoryTable, CustomerTable, FinanceCategoryTable, ProductTable, SettingTable, SupplierTable, UserTable, Illuminate\Database\Eloquent\Builder (+3 more)

### Community 3 - "Livewire\Component"
Cohesion: 0.06
Nodes (13): FinanceCategoryData, FinanceCategoryType, self, FinanceCategoryException, self, DeleteModal, FinanceCategoryDetail, FinanceCategoryForm (+5 more)

### Community 4 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, files, psr-4, config (+39 more)

### Community 5 - "Product"
Cohesion: 0.08
Nodes (8): self, ProductData, self, ProductException, ProductDetail, ProductForm, Product, ProductService

### Community 6 - "Unit"
Cohesion: 0.08
Nodes (9): self, UnitData, self, UnitException, UnitDetail, UnitForm, UnitTable, Unit (+1 more)

### Community 7 - "Illuminate\Database\Seeder"
Cohesion: 0.08
Nodes (12): SettingForm, Setting, CategorySeeder, CustomerSeeder, DatabaseSeeder, ProductSeeder, SettingSeeder, SupplierSeeder (+4 more)

### Community 8 - "Customer"
Cohesion: 0.09
Nodes (9): CustomerData, self, CustomerException, self, CustomerController, CustomerDetail, CustomerForm, Customer (+1 more)

### Community 9 - "Exception"
Cohesion: 0.09
Nodes (11): self, SupplierData, FinanceTransactionException, self, self, SupplierException, SupplierDetail, SupplierForm (+3 more)

### Community 10 - "devDependencies"
Cohesion: 0.06
Nodes (32): alpinejs, autoprefixer, axios, concurrently, flatpickr, laravel-vite-plugin, dependencies, flatpickr (+24 more)

### Community 11 - "Category"
Cohesion: 0.10
Nodes (8): CategoryData, self, CategoryException, self, CategoryDetail, CategoryForm, Category, CategoryService

### Community 12 - "Inventory Management System"
Cohesion: 0.08
Nodes (28): Alpine.js, Advanced Analytics Dashboard, ApexCharts, Blade Heroicons, Expense Breakdown Chart, Fajar Ghifar, Finance Ledger & Cash Flow, GitHub Repository (+20 more)

### Community 13 - "scripts"
Cohesion: 0.08
Nodes (26): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+18 more)

### Community 14 - "DashboardStatsService"
Cohesion: 0.16
Nodes (3): Dashboard, DashboardStatsService, Carbon\Carbon

### Community 15 - "Illuminate\Database\Eloquent\Factories\Factory"
Cohesion: 0.12
Nodes (8): CategoryFactory, CustomerFactory, ProductFactory, SupplierFactory, UnitFactory, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 16 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.13
Nodes (5): LoginRequest, ProfileUpdateRequest, StorePurchaseRequest, UpdatePurchaseRequest, Illuminate\Foundation\Http\FormRequest

### Community 17 - "FinanceTransaction"
Cohesion: 0.12
Nodes (3): FinanceTransactionDetail, FinanceTransactionTable, FinanceTransaction

### Community 18 - "TestCase"
Cohesion: 0.15
Nodes (7): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, EmailVerificationTest, PasswordUpdateTest, RegistrationTest, ExampleTest, TestCase

### Community 19 - "UserForm"
Cohesion: 0.13
Nodes (4): self, UserData, UserForm, UserService

### Community 20 - "Illuminate\Database\Eloquent\Factories\HasFactory"
Cohesion: 0.19
Nodes (5): PurchaseItem, SaleItem, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 21 - "Sale"
Cohesion: 0.23
Nodes (3): SalesController, Sale, SaleService

### Community 22 - "FinanceTransactionService"
Cohesion: 0.22
Nodes (4): SyncFinanceTransactions, FinanceTransactionService, FinanceCategoryType, Illuminate\Console\Command

### Community 23 - "ProfileService"
Cohesion: 0.15
Nodes (3): EditProfile, UpdatePassword, ProfileService

### Community 24 - "User"
Cohesion: 0.21
Nodes (4): UserDetail, User, Illuminate\Foundation\Auth\User, ProfileTest

### Community 26 - ".fromArray"
Cohesion: 0.17
Nodes (6): Carbon, self, self, SaleItemData, PaymentMethod, SaleStatus

### Community 28 - "FinanceTransactionData"
Cohesion: 0.25
Nodes (3): FinanceTransactionData, Carbon, self

### Community 37 - "index.blade.php"
Cohesion: 0.50
Nodes (3): categories.category-detail, categories.category-form, categories.category-table

### Community 38 - "index.blade.php"
Cohesion: 0.50
Nodes (3): customers.customer-detail, customers.customer-form, customers.customer-table

### Community 39 - "index.blade.php"
Cohesion: 0.50
Nodes (3): finance-categories.finance-category-detail, finance-categories.finance-category-form, finance-categories.finance-category-table

### Community 40 - "index.blade.php"
Cohesion: 0.50
Nodes (3): finance-transactions.finance-transaction-detail, finance-transactions.finance-transaction-form, finance-transactions.finance-transaction-table

### Community 41 - "app.blade.php"
Cohesion: 0.50
Nodes (3): layouts.footer, layouts.navigation, components.delete-modal

### Community 43 - "index.blade.php"
Cohesion: 0.50
Nodes (3): products.product-detail, products.product-form, products.product-table

### Community 44 - "index.blade.php"
Cohesion: 0.50
Nodes (3): suppliers.supplier-detail, suppliers.supplier-form, suppliers.supplier-table

### Community 45 - "index.blade.php"
Cohesion: 0.50
Nodes (3): units.unit-detail, units.unit-form, units.unit-table

### Community 46 - "index.blade.php"
Cohesion: 0.50
Nodes (3): users.user-detail, users.user-form, users.user-table

## Knowledge Gaps
- **124 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+119 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **20 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.