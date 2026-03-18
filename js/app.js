/**
 * SpendSmart - Dashboard Application JavaScript
 * Handles all dashboard functionality including:
 * - Tab navigation
 * - Transaction CRUD operations
 * - Category management
 * - Reports and charts
 * - Account management
 * Uses AJAX (XMLHttpRequest) for server communication
 * Uses Chart.js for data visualization
 */
(function () {
    'use strict';

    var TAB_STORAGE_KEY = 'spendsmart.activeTab';

    // ==================== GLOBAL STATE ====================
    var state = {
        transactions: [],
        categories: [],
        currencies: [],
        user: null,
        isGuest: false,
        currentTab: 'overview',
        sortField: 'date',
        sortOrder: 'desc',
        currentPage: 1,
        itemsPerPage: 10,
        charts: {}
    };

    // ==================== INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', function () {
        initMobileMenu();
        initTabs();
        initTypeToggles();
        initPasswordToggles();
        restoreActiveTabFromUrlOrStorage();
        window.addEventListener('hashchange', restoreActiveTabFromUrlOrStorage);
        checkSession();
    });

    function getValidTabNames() {
        return Array.prototype.slice.call(document.querySelectorAll('.tab-btn[data-tab]'))
            .map(function (btn) { return btn.getAttribute('data-tab'); })
            .filter(function (name) { return !!name; });
    }

    function getInitialTabName() {
        var validTabs = getValidTabNames();
        if (!validTabs.length) return '';

        var hashTab = (window.location.hash || '').replace('#', '').trim();
        if (hashTab && validTabs.indexOf(hashTab) !== -1) return hashTab;

        try {
            var stored = (window.sessionStorage && sessionStorage.getItem(TAB_STORAGE_KEY)) || '';
            if (stored && validTabs.indexOf(stored) !== -1) return stored;
        } catch (e) {
            // ignore storage errors
        }

        return '';
    }

    function restoreActiveTabFromUrlOrStorage() {
        var tab = getInitialTabName();
        if (tab && typeof window.switchTab === 'function') {
            window.switchTab(tab);
        }
    }

    // ==================== MOBILE MENU ====================
    function initMobileMenu() {
        var menuBtn = document.querySelector('.mobile-menu-btn');
        var mobileMenu = document.querySelector('.mobile-menu');
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', function () {
                var isOpen = mobileMenu.classList.contains('open');
                if (isOpen) {
                    mobileMenu.classList.remove('open');
                    mobileMenu.setAttribute('hidden', '');
                    menuBtn.classList.remove('active');
                    menuBtn.setAttribute('aria-expanded', 'false');
                } else {
                    mobileMenu.classList.add('open');
                    mobileMenu.removeAttribute('hidden');
                    menuBtn.classList.add('active');
                    menuBtn.setAttribute('aria-expanded', 'true');
                }
            });
        }
    }

    // ==================== SESSION CHECK ====================
    function checkSession() {
        apiRequest('GET', 'app/account.php', null, function (response) {
            if (response.success && response.user) {
                state.user = response.user;
                state.isGuest = (response.user.id === 'guest');
                state.currencies = response.currencies || [];
                updateUIForLoggedInUser();
                loadInitialData();
            } else {
                // Not logged in - redirect to home or show login prompt
                window.location.href = 'login.php';
            }
        }, function () {
            window.location.href = 'login.php';
        });
    }

    // ==================== UI UPDATES FOR AUTH STATE ====================
    function updateUIForLoggedInUser() {
        // Show/hide login/logout buttons based on auth state
        var navLogin = document.getElementById('navLoginBtn');
        var navSignup = document.getElementById('navSignupBtn');
        var navLogout = document.getElementById('navLogoutBtn');
        var mobileLogin = document.getElementById('mobileLoginBtn');
        var mobileSignup = document.getElementById('mobileSignupBtn');
        var mobileLogout = document.getElementById('mobileLogoutBtn');

        if (state.isGuest) {
            // Guest mode: show Login/Sign Up, hide Logout
            if (navLogin) navLogin.style.display = '';
            if (navSignup) navSignup.style.display = '';
            if (navLogout) navLogout.style.display = 'none';
            if (mobileLogin) mobileLogin.style.display = '';
            if (mobileSignup) mobileSignup.style.display = '';
            if (mobileLogout) mobileLogout.style.display = 'none';
        } else {
            // Logged in: show Logout, hide Login/Sign Up
            if (navLogin) navLogin.style.display = 'none';
            if (navSignup) navSignup.style.display = 'none';
            if (navLogout) navLogout.style.display = '';
            if (mobileLogin) mobileLogin.style.display = 'none';
            if (mobileSignup) mobileSignup.style.display = 'none';
            if (mobileLogout) mobileLogout.style.display = '';
        }

        // Logout handlers
        if (navLogout) navLogout.addEventListener('click', handleLogout);
        if (mobileLogout) mobileLogout.addEventListener('click', handleLogout);

        // Guest banner
        var guestBanner = document.getElementById('guestBanner');
        if (guestBanner) {
            if (state.isGuest) {
                guestBanner.classList.add('show');
            } else {
                guestBanner.classList.remove('show');
            }
        }

        // Dashboard title
        var title = document.getElementById('dashboardTitle');
        var subtitle = document.getElementById('dashboardSubtitle');
        if (state.isGuest) {
            if (title) title.textContent = 'Guest Dashboard';
            if (subtitle) subtitle.textContent = 'Test all features in Guest Mode \u2022 Data not saved';
        } else {
            if (title) title.textContent = 'Dashboard';
            if (subtitle) subtitle.textContent = 'Welcome back, ' + state.user.firstName;
        }

        // Set default date for add transaction form
        var dateInput = document.getElementById('txnDate');
        if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
    }

    function handleLogout() {
        apiRequest('GET', 'app/logout.php', null, function () {
            window.location.href = 'index.php';
        }, function () {
            window.location.href = 'index.php';
        });
    }

    // ==================== LOAD INITIAL DATA ====================
    function loadInitialData() {
        // Load categories
        apiRequest('GET', 'app/categories.php', null, function (response) {
            if (response.success) {
                state.categories = normalizeCategories(response.categories);
                populateCategorySelects();
                renderCategories();
            }
        });

        // Load transactions
        apiRequest('GET', 'app/transactions.php', null, function (response) {
            if (response.success) {
                state.transactions = response.transactions;
                renderOverview();
                renderTransactionsList();
                renderReports();
            }
        });

        // Populate currency selects
        populateCurrencySelects();

        // Load account data
        loadAccountData();

        // Init event listeners
        initEventListeners();
    }

    // ==================== TAB NAVIGATION ====================
    function initTabs() {
        var tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-tab');
                switchTab(tab);
            });
            // Keyboard support
            btn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    btn.click();
                }
            });
        });
    }

    // Make switchTab globally accessible
    window.switchTab = function (tabName) {
        state.currentTab = tabName;

        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(function (btn) {
            var isActive = btn.getAttribute('data-tab') === tabName;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        // Update panels
        document.querySelectorAll('.tab-panel').forEach(function (panel) {
            panel.classList.remove('active');
        });
        var activePanel = document.getElementById('panel-' + tabName);
        if (activePanel) activePanel.classList.add('active');

        // Refresh data when switching tabs
        if (tabName === 'reports') {
            renderReports();
        }

        // Persist active tab for reloads
        try {
            if (window.sessionStorage) sessionStorage.setItem(TAB_STORAGE_KEY, tabName);
        } catch (e) {
            // ignore storage errors
        }

        if (window.location.hash !== '#' + tabName) {
            // Keep the current PHP page, just update hash
            history.replaceState(null, '', window.location.pathname + window.location.search + '#' + tabName);
        }
    };

    // ==================== TYPE TOGGLE (Expense/Income) ====================
    function initTypeToggles() {
        // Add Transaction form type toggle
        var typeButtons = document.querySelectorAll('#addTransactionForm .type-toggle-btn');
        typeButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                typeButtons.forEach(function (b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-checked', 'false');
                });
                btn.classList.add('active');
                btn.setAttribute('aria-checked', 'true');
                document.getElementById('txnType').value = btn.getAttribute('data-type');
                populateCategorySelects();
            });
        });

        // Edit modal type toggle
        var editExpBtn = document.getElementById('editExpenseBtn');
        var editIncBtn = document.getElementById('editIncomeBtn');
        if (editExpBtn && editIncBtn) {
            editExpBtn.addEventListener('click', function () {
                editExpBtn.classList.add('active');
                editIncBtn.classList.remove('active');
                document.getElementById('editTxnType').value = 'expense';
                populateCategorySelects();
            });
            editIncBtn.addEventListener('click', function () {
                editIncBtn.classList.add('active');
                editExpBtn.classList.remove('active');
                document.getElementById('editTxnType').value = 'income';
                populateCategorySelects();
            });
        }
    }

    // ==================== PASSWORD TOGGLES ====================
    function initPasswordToggles() {
        var toggleBtns = document.querySelectorAll('.password-toggle');
        toggleBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = btn.parentElement.querySelector('input');
                if (input.type === 'password') {
                    input.type = 'text';
                } else {
                    input.type = 'password';
                }
            });
        });
    }

    // ==================== EVENT LISTENERS ====================
    function initEventListeners() {
        // Add Transaction Form
        var addForm = document.getElementById('addTransactionForm');
        if (addForm) addForm.addEventListener('submit', handleAddTransaction);

        // Edit Transaction Save
        var saveEditBtn = document.getElementById('saveEditTxnBtn');
        if (saveEditBtn) saveEditBtn.addEventListener('click', handleEditTransaction);

        // Transaction filters
        var filterType = document.getElementById('filterType');
        var filterCat = document.getElementById('filterCategory');
        var filterStart = document.getElementById('filterStartDate');
        var filterEnd = document.getElementById('filterEndDate');
        if (filterType) filterType.addEventListener('change', function () { state.currentPage = 1; populateCategorySelects(); renderTransactionsList(); });
        if (filterCat) filterCat.addEventListener('change', function () { state.currentPage = 1; renderTransactionsList(); });
        if (filterStart) filterStart.addEventListener('change', function () { state.currentPage = 1; renderTransactionsList(); });
        if (filterEnd) filterEnd.addEventListener('change', function () { state.currentPage = 1; renderTransactionsList(); });

        // Sortable columns
        document.querySelectorAll('.sortable').forEach(function (th) {
            th.addEventListener('click', function () {
                var field = th.getAttribute('data-sort');
                if (state.sortField === field) {
                    state.sortOrder = state.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortField = field;
                    state.sortOrder = 'asc';
                }
                renderTransactionsList();
            });
        });

        // Report filters
        var applyReportBtn = document.getElementById('applyReportFilters');
        if (applyReportBtn) applyReportBtn.addEventListener('click', renderReports);
        var reportType = document.getElementById('reportType');
        if (reportType) reportType.addEventListener('change', populateCategorySelects);

        // Category add button
        var addCatBtn = document.getElementById('addCategoryBtn');
        if (addCatBtn) addCatBtn.addEventListener('click', function () {
            document.getElementById('catEditId').value = '';
            document.getElementById('catName').value = '';
            document.getElementById('catType').value = 'expense';
            document.getElementById('catIcon').value = 'tag';
            document.getElementById('catColor').value = '#6366F1';
            document.getElementById('categoryModalTitle').textContent = 'Add Category';
            openModal('categoryModal');
        });

        // Category save
        var saveCatBtn = document.getElementById('saveCategoryBtn');
        if (saveCatBtn) saveCatBtn.addEventListener('click', handleSaveCategory);

        // Account form
        var accForm = document.getElementById('accountForm');
        if (accForm) accForm.addEventListener('submit', handleSaveAccount);

        // Delete account
        var deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if (deleteAccountBtn) deleteAccountBtn.addEventListener('click', handleDeleteAccount);

        // Close modals when clicking overlay
        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('show');
                }
            });
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.show').forEach(function (m) {
                    m.classList.remove('show');
                });
            }
        });
    }

    // ==================== POPULATE SELECTS ====================
    function populateCategorySelects() {
        setCategoryOptions('txnCategory', getCategoriesForType(document.getElementById('txnType') ? document.getElementById('txnType').value : 'expense'), 'Select category');
        setCategoryOptions('editTxnCategory', getCategoriesForType(document.getElementById('editTxnType') ? document.getElementById('editTxnType').value : 'expense'), 'Select category');
        setCategoryOptions('filterCategory', getCategoriesForType(document.getElementById('filterType') ? document.getElementById('filterType').value : ''), 'All Categories');
        setCategoryOptions('reportCategory', getCategoriesForType(document.getElementById('reportType') ? document.getElementById('reportType').value : ''), 'All Categories');
    }

    function setCategoryOptions(selectId, categories, placeholderText) {
        var select = document.getElementById(selectId);
        if (!select) return;

        var currentValue = select.value;
        select.innerHTML = '';

        var placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = placeholderText;
        select.appendChild(placeholder);

        categories.forEach(function (cat) {
            var option = document.createElement('option');
            option.value = cat.name;
            option.textContent = cat.name;
            select.appendChild(option);
        });

        var stillExists = categories.some(function (cat) { return cat.name === currentValue; });
        select.value = stillExists ? currentValue : '';
    }

    function normalizeCategories(categories) {
        if (!Array.isArray(categories)) return [];

        return categories.map(function (category) {
            return {
                id: category.id,
                name: category.name,
                icon: category.icon || 'tag',
                color: category.color || '#6366F1',
                type: getCategoryType(category),
                isDefault: !!category.isDefault
            };
        });
    }

    function getCategoryType(category) {
        if (category && (category.type === 'income' || category.type === 'expense')) {
            return category.type;
        }

        var name = category && category.name ? String(category.name).toLowerCase() : '';
        var icon = category && category.icon ? String(category.icon).toLowerCase() : '';
        var incomeIcons = ['wallet', 'money-bill'];
        var incomeKeywords = ['income', 'salary', 'freelance', 'bonus', 'allowance', 'interest', 'refund', 'revenue'];

        if (incomeIcons.indexOf(icon) !== -1) {
            return 'income';
        }

        for (var i = 0; i < incomeKeywords.length; i += 1) {
            if (name.indexOf(incomeKeywords[i]) !== -1) {
                return 'income';
            }
        }

        return 'expense';
    }

    function getCategoriesForType(type) {
        var categories = normalizeCategories(state.categories).slice().sort(function (left, right) {
            return left.name.localeCompare(right.name);
        });

        if (!type) {
            return categories;
        }

        return categories.filter(function (category) {
            return category.type === type;
        });
    }

    function populateCurrencySelects() {
        var selects = ['txnCurrency', 'editTxnCurrency', 'accCurrency'];
        selects.forEach(function (id) {
            var select = document.getElementById(id);
            if (!select) return;
            select.innerHTML = '';
            state.currencies.forEach(function (cur) {
                var option = document.createElement('option');
                option.value = cur.code;
                var symbol = (cur && cur.symbol) ? cur.symbol : cur.code;
                var name = (cur && cur.name) ? cur.name : cur.code;
                option.textContent = cur.code + ' (' + symbol + ') - ' + name;
                select.appendChild(option);
            });
            // Set default currency
            if (state.user && state.user.defaultCurrency) {
                select.value = state.user.defaultCurrency;
            }
        });
    }

    // ==================== OVERVIEW RENDERING ====================
    function renderOverview() {
        var transactions = state.transactions;
        var totalIncome = 0;
        var totalExpenses = 0;

        transactions.forEach(function (t) {
            if (t.type === 'income') totalIncome += t.amount;
            else totalExpenses += t.amount;
        });

        var balance = totalIncome - totalExpenses;
        var symbol = getCurrencySymbol(state.user ? state.user.defaultCurrency : 'USD');

        document.getElementById('totalBalance').textContent = symbol + formatNumber(balance);
        document.getElementById('totalIncome').textContent = symbol + formatNumber(totalIncome);
        document.getElementById('totalExpenses').textContent = symbol + formatNumber(totalExpenses);

        // Sub labels
        var balSub = document.getElementById('balanceSub');
        var incSub = document.getElementById('incomeSub');
        var expSub = document.getElementById('expensesSub');
        if (state.isGuest) {
            if (balSub) balSub.textContent = 'Guest Mode (Sample)';
            if (incSub) incSub.textContent = 'Sample Data';
            if (expSub) expSub.textContent = 'Sample Data';
        } else {
            if (balSub) balSub.textContent = '';
            if (incSub) incSub.textContent = '';
            if (expSub) expSub.textContent = '';
        }

        // Render charts
        renderOverviewCharts(transactions);

        // Render recent transactions
        renderRecentTransactions(transactions.slice(0, 5));
    }

    // ==================== OVERVIEW CHARTS ====================
    function renderOverviewCharts(transactions) {
        renderBarChart(transactions);
        renderPieChart(transactions);
    }

    function renderBarChart(transactions) {
        var canvas = document.getElementById('overviewBarChart');
        if (!canvas) return;

        // Group by month
        var monthlyData = {};
        transactions.forEach(function (t) {
            var month = t.date.substring(0, 7); // YYYY-MM
            if (!monthlyData[month]) monthlyData[month] = { income: 0, expense: 0 };
            if (t.type === 'income') monthlyData[month].income += t.amount;
            else monthlyData[month].expense += t.amount;
        });

        var sortedMonths = Object.keys(monthlyData).sort();
        // Take last 7 months
        sortedMonths = sortedMonths.slice(-7);

        var labels = sortedMonths.map(function (m) {
            var parts = m.split('-');
            var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return monthNames[parseInt(parts[1], 10) - 1];
        });
        var incomeData = sortedMonths.map(function (m) { return monthlyData[m].income; });
        var expenseData = sortedMonths.map(function (m) { return monthlyData[m].expense; });

        if (state.charts.overviewBar) state.charts.overviewBar.destroy();

        state.charts.overviewBar = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        backgroundColor: '#22C55E',
                        borderRadius: 4,
                        barPercentage: 0.6
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        backgroundColor: '#EF4444',
                        borderRadius: 4,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderPieChart(transactions) {
        var canvas = document.getElementById('overviewPieChart');
        if (!canvas) return;

        // Group expenses by category
        var catData = {};
        transactions.forEach(function (t) {
            if (t.type === 'expense') {
                if (!catData[t.category]) catData[t.category] = 0;
                catData[t.category] += t.amount;
            }
        });

        var labels = Object.keys(catData);
        var data = labels.map(function (l) { return catData[l]; });
        var colors = labels.map(function (l) {
            var cat = state.categories.find(function (c) { return c.name === l; });
            return cat ? cat.color : '#9CA3AF';
        });

        if (state.charts.overviewPie) state.charts.overviewPie.destroy();

        if (labels.length === 0) {
            labels = ['No Data'];
            data = [1];
            colors = ['#E5E7EB'];
        }

        state.charts.overviewPie = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { usePointStyle: true, padding: 12, font: { size: 12 } } }
                },
                cutout: '60%'
            }
        });
    }

    // ==================== RECENT TRANSACTIONS ====================
    function renderRecentTransactions(transactions) {
        var container = document.getElementById('recentTransactionsList');
        if (!container) return;

        if (transactions.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No transactions yet</p></div>';
            return;
        }

        var html = '';
        transactions.forEach(function (t) {
            var isIncome = t.type === 'income';
            var symbol = getCurrencySymbol(t.currency);
            var amountText = isIncome ? ('+' + symbol + formatNumber(t.amount)) : ('-' + symbol + formatNumber(t.amount));
            var dateText = formatDateRelative(t.date);

            html += '<div class="transaction-item">';
            html += '<div class="transaction-icon ' + t.type + '" aria-hidden="true">';
            html += isIncome
                ? '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 11.5L11.5 4.5"/><path d="M7 4.5h4.5V9"/></svg>'
                : '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.5 4.5l7 7"/><path d="M7 11.5h4.5V7"/></svg>';
            html += '</div>';
            html += '<div class="transaction-details">';
            html += '<div class="transaction-name">' + escapeHtml(t.name) + '</div>';
            html += '<div class="transaction-meta">' + escapeHtml(t.category) + ' &bull; ' + dateText + '</div>';
            html += '</div>';
            html += '<div class="transaction-amount ' + t.type + '">' + amountText + '</div>';
            html += '</div>';
        });

        container.innerHTML = html;
    }

    // ==================== ADD TRANSACTION ====================
    function handleAddTransaction(e) {
        e.preventDefault();

        var name = document.getElementById('txnName').value.trim();
        var date = document.getElementById('txnDate').value;
        var category = document.getElementById('txnCategory').value;
        var amount = document.getElementById('txnAmount').value;
        var currency = document.getElementById('txnCurrency').value;
        var type = document.getElementById('txnType').value;

        // Validation
        if (!name || !date || !category || !amount || !currency) {
            showAlertInPanel('addTxnAlert', 'Please fill in all fields', 'error');
            return;
        }

        if (parseFloat(amount) <= 0) {
            showAlertInPanel('addTxnAlert', 'Amount must be greater than zero', 'error');
            return;
        }

        var btn = document.getElementById('addTxnBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Adding...';

        var data = {
            name: name,
            date: date,
            category: category,
            amount: parseFloat(amount),
            currency: currency,
            type: type
        };

        apiRequest('POST', 'app/transactions.php', data, function (response) {
            btn.disabled = false;
            btn.textContent = 'Add Transaction';

            if (response.success) {
                showToast('Transaction added successfully', 'success');
                // Add to local state
                if (response.transaction) {
                    state.transactions.unshift(response.transaction);
                } else {
                    // Reload for guest mode
                    loadTransactions();
                }
                // Reset form
                document.getElementById('addTransactionForm').reset();
                document.getElementById('txnDate').value = new Date().toISOString().split('T')[0];
                document.getElementById('txnType').value = 'expense';
                document.querySelectorAll('#addTransactionForm .type-toggle-btn').forEach(function (b) {
                    b.classList.remove('active');
                    if (b.getAttribute('data-type') === 'expense') b.classList.add('active');
                });
                populateCategorySelects();
                if (state.user) {
                    document.getElementById('txnCurrency').value = state.user.defaultCurrency;
                }
                hideAlertInPanel('addTxnAlert');
                renderOverview();
                renderTransactionsList();
            } else {
                showAlertInPanel('addTxnAlert', response.message || 'Failed to add transaction', 'error');
            }
        }, function () {
            btn.disabled = false;
            btn.textContent = 'Add Transaction';
            showAlertInPanel('addTxnAlert', 'Network error. Please try again.', 'error');
        });
    }

    function loadTransactions() {
        apiRequest('GET', 'app/transactions.php', null, function (response) {
            if (response.success) {
                state.transactions = response.transactions;
                renderOverview();
                renderTransactionsList();
            }
        });
    }

    // ==================== TRANSACTIONS LIST ====================
    function renderTransactionsList() {
        var transactions = getFilteredTransactions();

        // Sort
        transactions.sort(function (a, b) {
            var valA, valB;
            if (state.sortField === 'date') {
                valA = a.date; valB = b.date;
            } else if (state.sortField === 'name') {
                valA = a.name.toLowerCase(); valB = b.name.toLowerCase();
            } else if (state.sortField === 'amount') {
                valA = a.amount; valB = b.amount;
            } else {
                valA = a.date; valB = b.date;
            }
            if (valA < valB) return state.sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return state.sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        // Pagination
        var totalItems = transactions.length;
        var totalPages = Math.ceil(totalItems / state.itemsPerPage);
        if (state.currentPage > totalPages) state.currentPage = totalPages || 1;
        var startIndex = (state.currentPage - 1) * state.itemsPerPage;
        var pageItems = transactions.slice(startIndex, startIndex + state.itemsPerPage);

        // Empty state
        var emptyEl = document.getElementById('transactionsEmpty');
        var tableWrapper = document.querySelector('#panel-transactions .transactions-table-wrapper');
        var mobileCards = document.getElementById('transactionsCardsMobile');
        var pagination = document.getElementById('transactionsPagination');

        if (totalItems === 0) {
            if (emptyEl) emptyEl.style.display = '';
            if (tableWrapper) tableWrapper.style.display = 'none';
            if (mobileCards) mobileCards.style.display = 'none';
            if (pagination) pagination.innerHTML = '';
            return;
        }

        if (emptyEl) emptyEl.style.display = 'none';

        // Render desktop table
        renderTransactionsTable(pageItems);

        // Render mobile cards
        renderTransactionsMobile(pageItems);

        // Render pagination
        renderPagination(pagination, totalPages);
    }

    function getFilteredTransactions() {
        var transactions = state.transactions.slice();
        var typeFilter = document.getElementById('filterType') ? document.getElementById('filterType').value : '';
        var catFilter = document.getElementById('filterCategory') ? document.getElementById('filterCategory').value : '';
        var startDate = document.getElementById('filterStartDate') ? document.getElementById('filterStartDate').value : '';
        var endDate = document.getElementById('filterEndDate') ? document.getElementById('filterEndDate').value : '';

        if (typeFilter) {
            transactions = transactions.filter(function (t) { return t.type === typeFilter; });
        }
        if (catFilter) {
            transactions = transactions.filter(function (t) { return t.category === catFilter; });
        }
        if (startDate) {
            transactions = transactions.filter(function (t) { return t.date >= startDate; });
        }
        if (endDate) {
            transactions = transactions.filter(function (t) { return t.date <= endDate; });
        }

        return transactions;
    }

    function renderTransactionsTable(items) {
        var tbody = document.getElementById('transactionsTableBody');
        if (!tbody) return;

        var html = '';
        items.forEach(function (t) {
            var symbol = getCurrencySymbol(t.currency);
            html += '<tr>';
            html += '<td>' + escapeHtml(t.date) + '</td>';
            html += '<td>' + escapeHtml(t.name) + '</td>';
            html += '<td>' + escapeHtml(t.category) + '</td>';
            html += '<td><span class="badge badge-' + t.type + '">' + capitalize(t.type) + '</span></td>';
            html += '<td class="' + (t.type === 'income' ? 'text-success' : '') + '">' + symbol + formatNumber(t.amount) + '</td>';
            html += '<td>' + escapeHtml(t.currency) + '</td>';
            html += '<td><div class="table-actions">';
            html += '<button class="btn-icon" onclick="editTransaction(\'' + t.id + '\')" aria-label="Edit transaction" title="Edit"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 12h5.5M9.5 2.5a1.5 1.5 0 012.12 2.12L4.5 11.74l-3 .76.76-3L9.5 2.5z"/></svg></button>';
            html += '<button class="btn-icon delete" onclick="deleteTransaction(\'' + t.id + '\')" aria-label="Delete transaction" title="Delete"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M4.5 3.5V2.5a1 1 0 011-1h3a1 1 0 011 1v1M5.5 6v4M8.5 6v4"/><path d="M3 3.5l.5 8a1.5 1.5 0 001.5 1.5h4a1.5 1.5 0 001.5-1.5l.5-8"/></svg></button>';
            html += '</div></td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    function renderTransactionsMobile(items) {
        var container = document.getElementById('transactionsCardsMobile');
        if (!container) return;

        var html = '';
        items.forEach(function (t) {
            var symbol = getCurrencySymbol(t.currency);
            var isIncome = t.type === 'income';
            html += '<div class="transaction-card-mobile">';
            html += '<div class="card-top">';
            html += '<span class="card-name">' + escapeHtml(t.name) + '</span>';
            html += '<span class="card-amount ' + (isIncome ? 'text-success' : '') + '">' + (isIncome ? '+' : '-') + symbol + formatNumber(t.amount) + '</span>';
            html += '</div>';
            html += '<div class="card-meta">' + escapeHtml(t.date) + ' &bull; ' + escapeHtml(t.category) + ' &bull; <span class="badge badge-' + t.type + '">' + capitalize(t.type) + '</span></div>';
            html += '<div class="card-actions">';
            html += '<button class="btn btn-outline btn-sm" onclick="editTransaction(\'' + t.id + '\')">Edit</button>';
            html += '<button class="btn btn-danger btn-sm" onclick="deleteTransaction(\'' + t.id + '\')">Delete</button>';
            html += '</div>';
            html += '</div>';
        });

        container.innerHTML = html;
    }

    function renderPagination(container, totalPages) {
        if (!container || totalPages <= 1) {
            if (container) container.innerHTML = '';
            return;
        }

        var html = '';
        html += '<button ' + (state.currentPage <= 1 ? 'disabled' : '') + ' onclick="changePage(' + (state.currentPage - 1) + ')" aria-label="Previous page">&laquo;</button>';

        for (var i = 1; i <= totalPages; i++) {
            html += '<button class="' + (i === state.currentPage ? 'active' : '') + '" onclick="changePage(' + i + ')">' + i + '</button>';
        }

        html += '<button ' + (state.currentPage >= totalPages ? 'disabled' : '') + ' onclick="changePage(' + (state.currentPage + 1) + ')" aria-label="Next page">&raquo;</button>';

        container.innerHTML = html;
    }

    window.changePage = function (page) {
        state.currentPage = page;
        renderTransactionsList();
    };

    // ==================== EDIT TRANSACTION ====================
    window.editTransaction = function (id) {
        var t = state.transactions.find(function (tr) { return tr.id === id; });
        if (!t) return;

        document.getElementById('editTxnId').value = t.id;
        document.getElementById('editTxnName').value = t.name;
        document.getElementById('editTxnDate').value = t.date;
        document.getElementById('editTxnAmount').value = t.amount;
        document.getElementById('editTxnCurrency').value = t.currency;
        document.getElementById('editTxnType').value = t.type;

        var expBtn = document.getElementById('editExpenseBtn');
        var incBtn = document.getElementById('editIncomeBtn');
        expBtn.classList.toggle('active', t.type === 'expense');
        incBtn.classList.toggle('active', t.type === 'income');

        populateCategorySelects();
        document.getElementById('editTxnCategory').value = t.category;

        openModal('editTxnModal');
    };

    function handleEditTransaction() {
        var id = document.getElementById('editTxnId').value;
        var data = {
            id: id,
            name: document.getElementById('editTxnName').value.trim(),
            date: document.getElementById('editTxnDate').value,
            category: document.getElementById('editTxnCategory').value,
            amount: parseFloat(document.getElementById('editTxnAmount').value),
            currency: document.getElementById('editTxnCurrency').value,
            type: document.getElementById('editTxnType').value
        };

        if (!data.name || !data.date || !data.category || !data.amount || !data.currency) {
            showToast('Please fill in all fields', 'error');
            return;
        }

        apiRequest('PUT', 'app/transactions.php', data, function (response) {
            if (response.success) {
                showToast('Transaction updated successfully', 'success');
                closeModal('editTxnModal');
                // Update local state
                var idx = state.transactions.findIndex(function (t) { return t.id === id; });
                if (idx !== -1) {
                    Object.assign(state.transactions[idx], data);
                }
                renderOverview();
                renderTransactionsList();
            } else {
                showToast(response.message || 'Failed to update', 'error');
            }
        });
    }

    // ==================== DELETE TRANSACTION ====================
    window.deleteTransaction = function (id) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this transaction? This action cannot be undone.';
        var confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.onclick = function () {
            apiRequest('DELETE', 'app/transactions.php', { id: id }, function (response) {
                if (response.success) {
                    showToast('Transaction deleted', 'success');
                    closeModal('deleteModal');
                    state.transactions = state.transactions.filter(function (t) { return t.id !== id; });
                    renderOverview();
                    renderTransactionsList();
                } else {
                    showToast(response.message || 'Failed to delete', 'error');
                }
            });
        };
        openModal('deleteModal');
    };

    // ==================== REPORTS ====================
    function renderReports() {
        var transactions = state.transactions.slice();

        // Apply report filters
        var typeFilter = document.getElementById('reportType') ? document.getElementById('reportType').value : '';
        var catFilter = document.getElementById('reportCategory') ? document.getElementById('reportCategory').value : '';
        var startDate = document.getElementById('reportStartDate') ? document.getElementById('reportStartDate').value : '';
        var endDate = document.getElementById('reportEndDate') ? document.getElementById('reportEndDate').value : '';

        if (typeFilter) transactions = transactions.filter(function (t) { return t.type === typeFilter; });
        if (catFilter) transactions = transactions.filter(function (t) { return t.category === catFilter; });
        if (startDate) transactions = transactions.filter(function (t) { return t.date >= startDate; });
        if (endDate) transactions = transactions.filter(function (t) { return t.date <= endDate; });

        // Calculate summary
        var totalIncome = 0, totalExpenses = 0;
        transactions.forEach(function (t) {
            if (t.type === 'income') totalIncome += t.amount;
            else totalExpenses += t.amount;
        });
        var netBalance = totalIncome - totalExpenses;
        var symbol = getCurrencySymbol(state.user ? state.user.defaultCurrency : 'USD');

        document.getElementById('reportIncome').textContent = symbol + formatNumber(totalIncome);
        document.getElementById('reportExpenses').textContent = symbol + formatNumber(totalExpenses);
        var balEl = document.getElementById('reportBalance');
        balEl.textContent = symbol + formatNumber(netBalance);
        balEl.className = 'stat-card-value ' + (netBalance >= 0 ? 'text-success' : 'text-danger');

        // Report charts
        renderReportBarChart(transactions);
        renderReportPieChart(transactions);

        // Report table
        renderReportTable(transactions);
    }

    function renderReportBarChart(transactions) {
        var canvas = document.getElementById('reportBarChart');
        if (!canvas) return;

        var monthlyData = {};
        transactions.forEach(function (t) {
            var month = t.date.substring(0, 7);
            if (!monthlyData[month]) monthlyData[month] = { income: 0, expense: 0 };
            if (t.type === 'income') monthlyData[month].income += t.amount;
            else monthlyData[month].expense += t.amount;
        });

        var sortedMonths = Object.keys(monthlyData).sort().slice(-12);
        var labels = sortedMonths.map(function (m) {
            var parts = m.split('-');
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[parseInt(parts[1], 10) - 1] + ' ' + parts[0].slice(2);
        });

        if (state.charts.reportBar) state.charts.reportBar.destroy();

        state.charts.reportBar = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Income', data: sortedMonths.map(function (m) { return monthlyData[m].income; }), backgroundColor: '#22C55E', borderRadius: 4 },
                    { label: 'Expenses', data: sortedMonths.map(function (m) { return monthlyData[m].expense; }), backgroundColor: '#EF4444', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } } },
                scales: { y: { beginAtZero: true, grid: { color: '#F3F4F6' } }, x: { grid: { display: false } } }
            }
        });
    }

    function renderReportPieChart(transactions) {
        var canvas = document.getElementById('reportPieChart');
        if (!canvas) return;

        var catData = {};
        transactions.forEach(function (t) {
            if (t.type === 'expense') {
                if (!catData[t.category]) catData[t.category] = 0;
                catData[t.category] += t.amount;
            }
        });

        var labels = Object.keys(catData);
        var data = labels.map(function (l) { return catData[l]; });
        var colors = labels.map(function (l) {
            var cat = state.categories.find(function (c) { return c.name === l; });
            return cat ? cat.color : '#9CA3AF';
        });

        if (state.charts.reportPie) state.charts.reportPie.destroy();

        if (labels.length === 0) {
            labels = ['No Data'];
            data = [1];
            colors = ['#E5E7EB'];
        }

        state.charts.reportPie = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{ data: data, backgroundColor: colors, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 12 } } },
                cutout: '60%'
            }
        });
    }

    function renderReportTable(transactions) {
        var tbody = document.getElementById('reportTableBody');
        var emptyEl = document.getElementById('reportEmpty');
        if (!tbody) return;

        if (transactions.length === 0) {
            tbody.innerHTML = '';
            if (emptyEl) emptyEl.style.display = '';
            return;
        }

        if (emptyEl) emptyEl.style.display = 'none';

        // Sort by date desc
        transactions.sort(function (a, b) { return b.date.localeCompare(a.date); });

        var html = '';
        transactions.forEach(function (t) {
            var symbol = getCurrencySymbol(t.currency);
            html += '<tr>';
            html += '<td>' + escapeHtml(t.date) + '</td>';
            html += '<td>' + escapeHtml(t.name) + '</td>';
            html += '<td>' + escapeHtml(t.category) + '</td>';
            html += '<td><span class="badge badge-' + t.type + '">' + capitalize(t.type) + '</span></td>';
            html += '<td class="' + (t.type === 'income' ? 'text-success' : '') + '">' + symbol + formatNumber(t.amount) + '</td>';
            html += '<td>' + escapeHtml(t.currency) + '</td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;
    }

    // ==================== CATEGORIES ====================
    function renderCategories() {
        var container = document.getElementById('categoriesGrid');
        if (!container) return;

        var categories = normalizeCategories(state.categories);
        if (categories.length === 0) {
            container.innerHTML = '<div class="empty-state"><h3>No categories</h3><p>Add your first category</p></div>';
            return;
        }

        var expenseCategories = categories.filter(function (cat) { return cat.type === 'expense'; });
        var incomeCategories = categories.filter(function (cat) { return cat.type === 'income'; });

        container.innerHTML = renderCategoryGroup('Expense Categories', 'expense', expenseCategories) + renderCategoryGroup('Income Categories', 'income', incomeCategories);
    }

    function renderCategoryGroup(title, type, categories) {
        var sortedCategories = categories.slice().sort(function (left, right) {
            return left.name.localeCompare(right.name);
        });
        var html = '<section class="category-group category-group-' + type + '">';
        html += '<div class="category-group-header">';
        html += '<h3 class="category-group-title">' + title + '</h3>';
        html += '<span class="category-group-count">' + sortedCategories.length + '</span>';
        html += '</div>';

        if (sortedCategories.length === 0) {
            html += '<div class="category-group-empty">No ' + type + ' categories yet.</div>';
            html += '</section>';
            return html;
        }

        html += '<div class="category-group-grid">';
        sortedCategories.forEach(function (cat) {
            html += '<div class="category-card">';
            html += '<div class="category-icon-box" style="background:' + cat.color + '20;color:' + cat.color + ';" aria-hidden="true">' + getCategoryIconSvg(cat.icon) + '</div>';
            html += '<div class="category-info">';
            html += '<div class="category-name">' + escapeHtml(cat.name) + '</div>';
            html += '<div class="category-meta">';
            html += '<span class="category-type-badge ' + cat.type + '">' + capitalize(cat.type) + '</span>';
            if (cat.isDefault) html += '<span class="category-badge">Default</span>';
            html += '</div>';
            html += '</div>';
            html += '<div class="category-actions">';
            html += '<button class="btn-icon" onclick="editCategory(\'' + cat.id + '\')" aria-label="Edit ' + escapeHtml(cat.name) + '" title="Edit"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M7 12h5.5M9.5 2.5a1.5 1.5 0 012.12 2.12L4.5 11.74l-3 .76.76-3L9.5 2.5z"/></svg></button>';
            html += '<button class="btn-icon delete" onclick="deleteCategory(\'' + cat.id + '\')" aria-label="Delete ' + escapeHtml(cat.name) + '" title="Delete"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M4.5 3.5V2.5a1 1 0 011-1h3a1 1 0 011 1v1M5.5 6v4M8.5 6v4"/><path d="M3 3.5l.5 8a1.5 1.5 0 001.5 1.5h4a1.5 1.5 0 001.5-1.5l.5-8"/></svg></button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        html += '</section>';

        return html;
    }

    function getCategoryIconSvg(icon) {
        var iconMap = {
            'utensils': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v7"/><path d="M9 3v7"/><path d="M7.5 10v7"/><path d="M13.5 3c0 2.8-1 4.5-2.5 5.2V17"/></svg>',
            'car': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11l1.5-4h9L16 11"/><path d="M3.5 11h13v3H3.5z"/><circle cx="6" cy="14.5" r="1"/><circle cx="14" cy="14.5" r="1"/></svg>',
            'bolt': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M11.5 2L6 10h4l-1.5 8L14 10h-4L11.5 2z"/></svg>',
            'film': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="14" height="12" rx="2"/><path d="M7 4v12M13 4v12"/><path d="M3 8h4M3 12h4M13 8h4M13 12h4"/></svg>',
            'bag-shopping': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h10l-1 9H6L5 7z"/><path d="M7.5 8V6a2.5 2.5 0 015 0v2"/></svg>',
            'heart-pulse': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 16l-5.3-5.1a3.3 3.3 0 114.7-4.7L10 7l.6-.8a3.3 3.3 0 114.7 4.7L10 16z"/><path d="M4.5 10h2l1.2-2 1.6 4 1.2-2h3.2"/></svg>',
            'graduation-cap': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 7.5L10 4l7.5 3.5L10 11 2.5 7.5z"/><path d="M5.5 9v3.5c1.2 1 2.7 1.5 4.5 1.5s3.3-.5 4.5-1.5V9"/><path d="M17.5 8v4"/></svg>',
            'wallet': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6.5A1.5 1.5 0 015.5 5h8A1.5 1.5 0 0115 6.5v7A1.5 1.5 0 0113.5 15h-8A1.5 1.5 0 014 13.5v-7z"/><path d="M4 8.5h11"/><circle cx="12.7" cy="11.3" r="0.9" fill="currentColor" stroke="none"/></svg>',
            'money-bill': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="14" height="10" rx="2"/><circle cx="10" cy="10" r="2.2"/><path d="M6 7.5h.01M14 12.5h.01"/></svg>',
            'house': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 9.5L10 4l6.5 5.5"/><path d="M5.5 8.5V16h9V8.5"/></svg>',
            'plane': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3L3 9l5 2 2 5 7-13z"/></svg>',
            'gift': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="14" height="9" rx="2"/><path d="M10 8v9M3 11h14"/><path d="M10 8H7.5A2 2 0 117.5 4c1.7 0 2.5 2 2.5 4z"/><path d="M10 8h2.5A2 2 0 1012.5 4C10.8 4 10 6 10 8z"/></svg>',
            'tag': '<svg width="18" height="18" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3H4v5l7.5 7.5a2 2 0 002.8 0l3.2-3.2a2 2 0 000-2.8L10 3z"/><circle cx="6.5" cy="6.5" r="0.9" fill="currentColor" stroke="none"/></svg>'
        };

        return iconMap[icon] || iconMap.tag;
    }

    window.editCategory = function (id) {
        var cat = state.categories.find(function (c) { return c.id === id; });
        if (!cat) return;

        document.getElementById('catEditId').value = cat.id;
        document.getElementById('catName').value = cat.name;
        document.getElementById('catType').value = getCategoryType(cat);
        document.getElementById('catIcon').value = cat.icon;
        document.getElementById('catColor').value = cat.color;
        document.getElementById('categoryModalTitle').textContent = 'Edit Category';
        openModal('categoryModal');
    };

    window.deleteCategory = function (id) {
        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this category?';
        var confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.onclick = function () {
            apiRequest('DELETE', 'app/categories.php', { id: id }, function (response) {
                if (response.success) {
                    showToast('Category deleted', 'success');
                    closeModal('deleteModal');
                    state.categories = state.categories.filter(function (c) { return c.id !== id; });
                    renderCategories();
                    populateCategorySelects();
                } else {
                    showToast(response.message || 'Failed to delete', 'error');
                }
            });
        };
        openModal('deleteModal');
    };

    function handleSaveCategory() {
        var editId = document.getElementById('catEditId').value;
        var name = document.getElementById('catName').value.trim();
        var type = document.getElementById('catType').value;
        var icon = document.getElementById('catIcon').value;
        var color = document.getElementById('catColor').value;

        if (!name) {
            showToast('Category name is required', 'error');
            return;
        }

        if (editId) {
            // Update
            apiRequest('PUT', 'app/categories.php', { id: editId, name: name, type: type, icon: icon, color: color }, function (response) {
                if (response.success) {
                    showToast('Category updated', 'success');
                    closeModal('categoryModal');
                    var cat = state.categories.find(function (c) { return c.id === editId; });
                    if (cat) {
                        cat.name = name;
                        cat.type = type;
                        cat.icon = icon;
                        cat.color = color;
                    }
                    renderCategories();
                    populateCategorySelects();
                } else {
                    showToast(response.message || 'Failed to update', 'error');
                }
            });
        } else {
            // Create
            apiRequest('POST', 'app/categories.php', { name: name, type: type, icon: icon, color: color }, function (response) {
                if (response.success) {
                    showToast('Category added', 'success');
                    closeModal('categoryModal');
                    if (response.category) {
                        state.categories.push(normalizeCategories([response.category])[0]);
                    } else {
                        // Reload
                        apiRequest('GET', 'app/categories.php', null, function (r) {
                            if (r.success) state.categories = normalizeCategories(r.categories);
                            renderCategories();
                            populateCategorySelects();
                        });
                        return;
                    }
                    renderCategories();
                    populateCategorySelects();
                } else {
                    showToast(response.message || 'Failed to add', 'error');
                }
            });
        }
    }

    // ==================== ACCOUNT ====================
    function loadAccountData() {
        if (!state.user) return;
        document.getElementById('accFirstName').value = state.user.firstName || '';
        document.getElementById('accLastName').value = state.user.lastName || '';
        document.getElementById('accEmail').value = state.user.email || '';
        if (state.user.defaultCurrency) {
            var currSelect = document.getElementById('accCurrency');
            if (currSelect) currSelect.value = state.user.defaultCurrency;
        }
        var created = document.getElementById('accCreatedAt');
        var updated = document.getElementById('accUpdatedAt');
        if (created && state.user.createdAt) created.textContent = new Date(state.user.createdAt).toLocaleDateString();
        if (updated && state.user.updatedAt) updated.textContent = new Date(state.user.updatedAt).toLocaleDateString();
    }

    function handleSaveAccount(e) {
        e.preventDefault();

        var previousCurrency = state.user ? state.user.defaultCurrency : '';

        var data = {
            firstName: document.getElementById('accFirstName').value.trim(),
            lastName: document.getElementById('accLastName').value.trim(),
            email: document.getElementById('accEmail').value.trim(),
            defaultCurrency: document.getElementById('accCurrency').value
        };

        var password = document.getElementById('accPassword').value;
        if (password) data.password = password;

        if (!data.firstName || !data.lastName || !data.email) {
            showAlertInPanel('accountAlert', 'Please fill in all required fields', 'error');
            return;
        }

        var btn = document.getElementById('saveAccountBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        apiRequest('PUT', 'app/account.php', data, function (response) {
            btn.disabled = false;
            btn.textContent = 'Save Changes';

            if (response.success) {
                showToast('Profile updated successfully', 'success');
                showAlertInPanel('accountAlert', 'Profile updated successfully', 'success');
                // Update local state
                state.user.firstName = data.firstName;
                state.user.lastName = data.lastName;
                state.user.email = data.email;
                state.user.defaultCurrency = data.defaultCurrency;
                document.getElementById('accPassword').value = '';

                // If default currency changed, reload transactions so converted values show immediately.
                if (previousCurrency && previousCurrency !== data.defaultCurrency) {
                    populateCurrencySelects();
                    apiRequest('GET', 'app/transactions.php', null, function (r) {
                        if (r && r.success) {
                            state.transactions = r.transactions || [];
                            renderOverview();
                            renderTransactionsList();
                            renderReports();
                        }
                    });
                }
            } else {
                showAlertInPanel('accountAlert', response.message || 'Failed to update', 'error');
            }
        }, function () {
            btn.disabled = false;
            btn.textContent = 'Save Changes';
            showAlertInPanel('accountAlert', 'Network error', 'error');
        });
    }

    function handleDeleteAccount() {
        if (state.isGuest) {
            showToast('Guest Mode account cannot be deleted', 'error');
            return;
        }

        document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete your account? This will permanently delete your profile, categories, and transactions.';
        var confirmBtn = document.getElementById('confirmDeleteBtn');
        confirmBtn.onclick = function () {
            apiRequest('POST', 'app/account.php', { action: 'delete' }, function (response) {
                if (response.success) {
                    showToast('Account deleted', 'success');
                    closeModal('deleteModal');
                    setTimeout(function () {
                        window.location.href = 'index.php';
                    }, 400);
                } else {
                    showToast(response.message || 'Failed to delete account', 'error');
                }
            }, function () {
                showToast('Network error', 'error');
            });
        };
        openModal('deleteModal');
    }

    // ==================== API REQUEST HELPER ====================
    function apiRequest(method, url, data, onSuccess, onError) {
        var xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }

        xhr.onload = function () {
            try {
                var response = JSON.parse(xhr.responseText);
                if (onSuccess) onSuccess(response);
            } catch (e) {
                if (onError) onError();
            }
        };

        xhr.onerror = function () {
            if (onError) onError();
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    }

    // ==================== MODAL HELPERS ====================
    window.openModal = function (id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('show');
            // Focus first input
            setTimeout(function () {
                var input = modal.querySelector('input:not([type=hidden]), select');
                if (input) input.focus();
            }, 100);
        }
    };

    window.closeModal = function (id) {
        var modal = document.getElementById(id);
        if (modal) modal.classList.remove('show');
    };

    // ==================== TOAST NOTIFICATIONS ====================
    function showToast(message, type) {
        var container = document.getElementById('toastContainer');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'toast ' + (type || '');
        toast.setAttribute('role', 'alert');
        toast.textContent = message;

        container.appendChild(toast);

        // Auto remove after 3 seconds
        setTimeout(function () {
            toast.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // ==================== ALERT HELPERS ====================
    function showAlertInPanel(elementId, message, type) {
        var el = document.getElementById(elementId);
        if (!el) return;
        el.textContent = message;
        el.className = 'alert show alert-' + type;
    }

    function hideAlertInPanel(elementId) {
        var el = document.getElementById(elementId);
        if (el) el.className = 'alert';
    }

    // ==================== UTILITY FUNCTIONS ====================
    function formatNumber(num) {
        return Math.abs(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function getCurrencySymbol(code) {
        var cur = state.currencies.find(function (c) { return c.code === code; });
        return cur ? cur.symbol : '$';
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function formatDateRelative(dateStr) {
        var today = new Date().toISOString().split('T')[0];
        var yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];

        if (dateStr === today) return 'Today';
        if (dateStr === yesterday) return 'Yesterday';

        var d = new Date(dateStr + 'T00:00:00');
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[d.getMonth()] + ' ' + d.getDate();
    }

    // ==================== HELPER: Get currencies function for account.php ====================
    function getCurrencies() {
        return state.currencies;
    }

})();
