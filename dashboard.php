<?php
$pageTitle = 'Dashboard';
$bodyClass = 'dashboard-page';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
$extraScripts = '<script src="js/app.js"></script>';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in or in guest mode
$isAuth = (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) || (isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true);
if (!$isAuth) {
    header('Location: login.php');
    exit;
}

$isGuestMode = isset($_SESSION['guest_mode']) && $_SESSION['guest_mode'] === true;

require_once 'includes/header.php';
?>

    <!-- Guest Mode Banner -->
    <?php if ($isGuestMode): ?>
    <div class="guest-banner show" id="guestBanner" role="alert">
        <div class="container guest-banner-content">
            <span class="guest-banner-text">You're in Guest Mode &bull; Your data is temporary</span>
            <a href="register.php" class="btn btn-sm guest-banner-btn">Create Account to Save</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard Tabs Navigation -->
    <nav class="dashboard-tabs" role="tablist" aria-label="Dashboard sections">
        <div class="tabs-container">
            <button class="tab-btn active" role="tab" aria-selected="true" aria-controls="panel-overview" id="tab-overview" data-tab="overview">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <rect x="2" y="2" width="5" height="5" rx="1"/><rect x="9" y="2" width="5" height="5" rx="1"/>
                    <rect x="2" y="9" width="5" height="5" rx="1"/><rect x="9" y="9" width="5" height="5" rx="1"/>
                </svg>
                Overview
            </button>
            <button class="tab-btn" role="tab" aria-selected="false" aria-controls="panel-add" id="tab-add" data-tab="add">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="8" cy="8" r="6"/><path d="M8 5v6M5 8h6"/>
                </svg>
                Add Transaction
            </button>
            <button class="tab-btn" role="tab" aria-selected="false" aria-controls="panel-transactions" id="tab-transactions" data-tab="transactions">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M3 4h10M3 8h10M3 12h7"/>
                </svg>
                Transactions
            </button>
            <button class="tab-btn" role="tab" aria-selected="false" aria-controls="panel-reports" id="tab-reports" data-tab="reports">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M4 12V7M8 12V4M12 12V9"/>
                </svg>
                Reports
            </button>
            <button class="tab-btn" role="tab" aria-selected="false" aria-controls="panel-categories" id="tab-categories" data-tab="categories">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="8" cy="8" r="2"/><path d="M8 2v2M8 12v2M2 8h2M12 8h2"/>
                </svg>
                Categories
            </button>
            <button class="tab-btn" role="tab" aria-selected="false" aria-controls="panel-account" id="tab-account" data-tab="account">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.3 2.7-5 6-5s6 1.7 6 5"/>
                </svg>
                Account
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="main-content">
        <div class="dashboard-content">

            <!-- =============== OVERVIEW TAB =============== -->
            <div class="tab-panel active" id="panel-overview" role="tabpanel" aria-labelledby="tab-overview">
                <div class="overview-header">
                    <h1 id="dashboardTitle"><?php echo $isGuestMode ? 'Guest Dashboard' : 'Dashboard'; ?></h1>
                    <p id="dashboardSubtitle"><?php echo $isGuestMode ? 'Test all features in Guest Mode • Data not saved' : 'Track your financial activity'; ?></p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card" tabindex="0" aria-label="Total Balance">
                        <div class="stat-card-info">
                            <h3>Total Balance</h3>
                            <div class="stat-card-value" id="totalBalance">$0.00</div>
                            <div class="stat-card-sub" id="balanceSub"><?php echo $isGuestMode ? 'Guest Mode (Sample)' : ''; ?></div>
                        </div>
                        <div class="stat-card-icon blue" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3.5" y="5.5" width="13" height="9" rx="2"/>
                                <path d="M3.5 8.5h13"/>
                                <circle cx="13.25" cy="11.25" r="0.9" fill="currentColor" stroke="none"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card" tabindex="0" aria-label="Total Income">
                        <div class="stat-card-info">
                            <h3>Total Income</h3>
                            <div class="stat-card-value" id="totalIncome">$0.00</div>
                            <div class="stat-card-sub" id="incomeSub"><?php echo $isGuestMode ? 'Sample Data' : ''; ?></div>
                        </div>
                        <div class="stat-card-icon income-wallet" aria-hidden="true">
                            <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 32L32 16" stroke="#13a24c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M20 16H32V28" stroke="#13a24c" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                    <div class="stat-card" tabindex="0" aria-label="Total Expenses">
                        <div class="stat-card-info">
                            <h3>Total Expenses</h3>
                            <div class="stat-card-value" id="totalExpenses">$0.00</div>
                            <div class="stat-card-sub" id="expensesSub"><?php echo $isGuestMode ? 'Sample Data' : ''; ?></div>
                        </div>
                        <div class="stat-card-icon red" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 6l8 8"/>
                                <path d="M9 14h5V9"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-row">
                    <div class="chart-card">
                        <h3>Income vs Expenses</h3>
                        <div class="chart-container">
                            <canvas id="overviewBarChart" aria-label="Bar chart showing income versus expenses by month" role="img"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Expenses by Category</h3>
                        <div class="chart-container">
                            <canvas id="overviewPieChart" aria-label="Pie chart showing expenses by category" role="img"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="recent-transactions">
                    <h3>Recent Transactions</h3>
                    <div id="recentTransactionsList">
                        <div class="loading-overlay"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>

            <!-- =============== ADD TRANSACTION TAB =============== -->
            <div class="tab-panel" id="panel-add" role="tabpanel" aria-labelledby="tab-add">
                <div class="form-card">
                    <h2>Add New Transaction</h2>
                    <p class="form-subtitle">Record your income or expense</p>

                    <div id="addTxnAlert" class="alert" role="alert" aria-live="polite"></div>

                    <form id="addTransactionForm" novalidate>
                        <div class="form-group">
                            <label class="form-label">Transaction Type</label>
                            <div class="type-toggle" role="radiogroup" aria-label="Transaction type">
                                <button type="button" class="type-toggle-btn expense-btn active" data-type="expense" role="radio" aria-checked="true">Expense</button>
                                <button type="button" class="type-toggle-btn income-btn" data-type="income" role="radio" aria-checked="false">Income</button>
                            </div>
                            <input type="hidden" id="txnType" value="expense" />
                        </div>
                        <div class="form-group">
                            <label for="txnName" class="form-label">Item Name</label>
                            <input type="text" id="txnName" class="form-input" placeholder="e.g. Grocery shopping, Salary" required aria-required="true" />
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="txnDate" class="form-label">Date</label>
                                <input type="date" id="txnDate" class="form-input" required aria-required="true" />
                            </div>
                            <div class="form-group">
                                <label for="txnCategory" class="form-label">Category</label>
                                <select id="txnCategory" class="form-select" required aria-required="true">
                                    <option value="">Select category</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="txnAmount" class="form-label">Amount</label>
                                <input type="number" id="txnAmount" class="form-input" placeholder="0.00" step="0.01" min="0.01" required aria-required="true" />
                            </div>
                            <div class="form-group">
                                <label for="txnCurrency" class="form-label">Currency</label>
                                <select id="txnCurrency" class="form-select" required aria-required="true">
                                    <option value="">Select currency</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block mt-2" id="addTxnBtn">Add Transaction</button>
                    </form>
                </div>
            </div>

            <!-- =============== TRANSACTIONS LIST TAB =============== -->
            <div class="tab-panel" id="panel-transactions" role="tabpanel" aria-labelledby="tab-transactions">
                <div class="list-header">
                    <h2>All Transactions</h2>
                    <div class="list-filters">
                        <select id="filterType" class="filter-select" aria-label="Filter by type">
                            <option value="">All Types</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                        <select id="filterCategory" class="filter-select" aria-label="Filter by category">
                            <option value="">All Categories</option>
                        </select>
                        <input type="date" id="filterStartDate" class="filter-input" aria-label="Start date filter" />
                        <input type="date" id="filterEndDate" class="filter-input" aria-label="End date filter" />
                    </div>
                </div>

                <div class="transactions-table-wrapper">
                    <table class="transactions-table" aria-label="Transactions list">
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="date" tabindex="0" aria-label="Sort by date">Date <span class="sort-icon">&#9650;&#9660;</span></th>
                                <th class="sortable" data-sort="name" tabindex="0" aria-label="Sort by name">Name <span class="sort-icon">&#9650;&#9660;</span></th>
                                <th>Category</th>
                                <th>Type</th>
                                <th class="sortable" data-sort="amount" tabindex="0" aria-label="Sort by amount">Amount <span class="sort-icon">&#9650;&#9660;</span></th>
                                <th>Currency</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsTableBody"></tbody>
                    </table>
                </div>

                <div class="transaction-cards-mobile" id="transactionsCardsMobile"></div>

                <div class="empty-state" id="transactionsEmpty" style="display:none">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <h3>No transactions yet</h3>
                    <p>Start by adding your first transaction</p>
                    <button class="btn btn-primary" onclick="switchTab('add')">Add Transaction</button>
                </div>

                <div class="pagination" id="transactionsPagination"></div>
            </div>

            <!-- =============== REPORTS TAB =============== -->
            <div class="tab-panel" id="panel-reports" role="tabpanel" aria-labelledby="tab-reports">
                <h2 class="panel-title">Financial Reports</h2>
                <p class="panel-subtitle">Analyze your income and spending patterns</p>

                <div class="reports-filters">
                    <select id="reportType" class="filter-select" aria-label="Report type filter">
                        <option value="">All Types</option>
                        <option value="income">Income Only</option>
                        <option value="expense">Expenses Only</option>
                    </select>
                    <select id="reportCategory" class="filter-select" aria-label="Report category filter">
                        <option value="">All Categories</option>
                    </select>
                    <input type="date" id="reportStartDate" class="filter-input" aria-label="Report start date" />
                    <input type="date" id="reportEndDate" class="filter-input" aria-label="Report end date" />
                    <button class="btn btn-primary btn-sm" id="applyReportFilters">Apply Filters</button>
                </div>

                <div class="report-summary-grid" id="reportSummary">
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>Total Income</h3>
                            <div class="stat-card-value text-success" id="reportIncome">$0.00</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>Total Expenses</h3>
                            <div class="stat-card-value text-danger" id="reportExpenses">$0.00</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-info">
                            <h3>Net Balance</h3>
                            <div class="stat-card-value" id="reportBalance">$0.00</div>
                        </div>
                    </div>
                </div>

                <div class="report-chart-section">
                    <div class="chart-card">
                        <h3>Monthly Trend</h3>
                        <div class="chart-container">
                            <canvas id="reportBarChart" aria-label="Monthly trend bar chart" role="img"></canvas>
                        </div>
                    </div>
                    <div class="chart-card">
                        <h3>Category Breakdown</h3>
                        <div class="chart-container">
                            <canvas id="reportPieChart" aria-label="Category breakdown pie chart" role="img"></canvas>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Transaction Details</h3>
                    <div class="transactions-table-wrapper" style="display:block;border:none;border-radius:0">
                        <table class="transactions-table" aria-label="Report transaction details">
                            <thead>
                                <tr>
                                    <th>Date</th><th>Name</th><th>Category</th><th>Type</th><th>Amount</th><th>Currency</th>
                                </tr>
                            </thead>
                            <tbody id="reportTableBody"></tbody>
                        </table>
                    </div>
                    <div class="empty-state" id="reportEmpty" style="display:none">
                        <h3>No data available</h3>
                        <p>Add transactions to see your reports</p>
                    </div>
                </div>
            </div>

            <!-- =============== CATEGORIES TAB =============== -->
            <div class="tab-panel" id="panel-categories" role="tabpanel" aria-labelledby="tab-categories">
                <div class="categories-header">
                    <h2>Categories</h2>
                    <button class="btn btn-primary" id="addCategoryBtn">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 3v10M3 8h10"/></svg>
                        Add Category
                    </button>
                </div>
                <div class="categories-grid" id="categoriesGrid">
                    <div class="loading-overlay"><div class="spinner"></div></div>
                </div>
            </div>

            <!-- =============== ACCOUNT TAB =============== -->
            <div class="tab-panel" id="panel-account" role="tabpanel" aria-labelledby="tab-account">
                <div class="account-section">
                    <div class="account-card">
                        <h2>Edit Profile</h2>
                        <p class="account-subtitle">Update your personal information</p>

                        <div id="accountAlert" class="alert" role="alert" aria-live="polite"></div>

                        <form id="accountForm" novalidate>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="accFirstName" class="form-label">First Name</label>
                                    <input type="text" id="accFirstName" class="form-input" required aria-required="true" />
                                </div>
                                <div class="form-group">
                                    <label for="accLastName" class="form-label">Last Name</label>
                                    <input type="text" id="accLastName" class="form-input" required aria-required="true" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="accEmail" class="form-label">Email Address</label>
                                <input type="email" id="accEmail" class="form-input" required aria-required="true" />
                            </div>
                            <div class="form-group">
                                <label for="accCurrency" class="form-label">Default Currency</label>
                                <select id="accCurrency" class="form-select" aria-label="Default currency"></select>
                            </div>
                            <div class="form-group">
                                <label for="accPassword" class="form-label">New Password (leave blank to keep current)</label>
                                <div class="form-input-wrapper">
                                    <input type="password" id="accPassword" class="form-input" placeholder="Enter new password" autocomplete="new-password" />
                                    <button type="button" class="password-toggle" aria-label="Toggle password visibility">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg" id="saveAccountBtn">Save Changes</button>
                        </form>
                    </div>
                    <div class="account-card">
                        <h2>Account Info</h2>
                        <p class="account-subtitle">Your account details</p>
                        <p><strong>Member since:</strong> <span id="accCreatedAt">-</span></p>
                        <p class="mt-1"><strong>Last updated:</strong> <span id="accUpdatedAt">-</span></p>
                    </div>

                    <?php if (!$isGuestMode): ?>
                    <div class="account-card">
                        <h2>Delete Account</h2>
                        <p class="account-subtitle">Permanently delete your account and all your data</p>
                        <button type="button" class="btn btn-danger" id="deleteAccountBtn">Delete Account</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    
    </main>

    <!-- Edit Transaction Modal -->
    <div class="modal-overlay" id="editTxnModal" role="dialog" aria-modal="true" aria-labelledby="editTxnModalTitle">
        <div class="modal">
            <div class="modal-header">
                <h3 id="editTxnModalTitle">Edit Transaction</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('editTxnModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editTransactionForm" novalidate>
                    <input type="hidden" id="editTxnId" />
                    <div class="form-group">
                        <label for="editTxnName" class="form-label">Item Name</label>
                        <input type="text" id="editTxnName" class="form-input" required />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editTxnDate" class="form-label">Date</label>
                            <input type="date" id="editTxnDate" class="form-input" required />
                        </div>
                        <div class="form-group">
                            <label for="editTxnCategory" class="form-label">Category</label>
                            <select id="editTxnCategory" class="form-select" required></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editTxnAmount" class="form-label">Amount</label>
                            <input type="number" id="editTxnAmount" class="form-input" step="0.01" min="0.01" required />
                        </div>
                        <div class="form-group">
                            <label for="editTxnCurrency" class="form-label">Currency</label>
                            <select id="editTxnCurrency" class="form-select" required></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <div class="type-toggle">
                            <button type="button" class="type-toggle-btn expense-btn" data-type="expense" id="editExpenseBtn">Expense</button>
                            <button type="button" class="type-toggle-btn income-btn" data-type="income" id="editIncomeBtn">Income</button>
                        </div>
                        <input type="hidden" id="editTxnType" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('editTxnModal')">Cancel</button>
                <button class="btn btn-primary" id="saveEditTxnBtn">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div class="modal-overlay" id="categoryModal" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle">
        <div class="modal">
            <div class="modal-header">
                <h3 id="categoryModalTitle">Add Category</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" novalidate>
                    <input type="hidden" id="catEditId" />
                    <div class="form-group">
                        <label for="catName" class="form-label">Category Name</label>
                        <input type="text" id="catName" class="form-input" placeholder="e.g. Travel, Bills" required />
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="catType" class="form-label">Category Type</label>
                            <select id="catType" class="form-select">
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="catIcon" class="form-label">Icon</label>
                            <select id="catIcon" class="form-select">
                                <option value="tag">Tag</option><option value="utensils">Food</option>
                                <option value="car">Transport</option><option value="bolt">Utilities</option>
                                <option value="film">Entertainment</option><option value="bag-shopping">Shopping</option>
                                <option value="heart-pulse">Health</option><option value="graduation-cap">Education</option>
                                <option value="wallet">Income</option><option value="money-bill">Money</option>
                                <option value="house">Home</option><option value="plane">Travel</option>
                                <option value="gift">Gift</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                            <label for="catColor" class="form-label">Color</label>
                            <input type="color" id="catColor" class="form-input" value="#6366F1" style="height:44px;padding:4px" />
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('categoryModal')">Cancel</button>
                <button class="btn btn-primary" id="saveCategoryBtn">Save Category</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
        <div class="modal" style="max-width:400px">
            <div class="modal-header">
                <h3 id="deleteModalTitle">Confirm Delete</h3>
                <button class="modal-close" aria-label="Close modal" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Are you sure you want to delete this item? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<?php
// No footer on dashboard - just close body/html
if (isset($extraScripts)) echo $extraScripts;
?>
</body>
</html>
