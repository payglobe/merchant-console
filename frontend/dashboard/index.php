<?php
// Disable caching to always load fresh JavaScript
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="it" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayGlobe Merchant Console - Dashboard</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- ECharts -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .animate-slide-in-up {
            animation: slideInUp 0.5s ease-out forwards;
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .animate-pulse-slow {
            animation: pulse-slow 2s ease-in-out infinite;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>

<body class="h-full bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50" x-data="dashboard()" >

    <!-- Login Screen -->
    <div x-show="!isAuthenticated" x-cloak class="min-h-screen flex items-center justify-center p-4 animate-fade-in">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8 animate-slide-in-up">
                <h1 class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 mb-2">
                    PayGlobe
                </h1>
                <p class="text-gray-600 text-lg">Merchant Console v2.0</p>
            </div>

            <!-- Login Card -->
            <div class="glass-effect rounded-2xl shadow-2xl p-8 animate-slide-in-up" style="animation-delay: 0.1s">
                <div class="flex items-center justify-center mb-6">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-500 p-4 rounded-full">
                        <i data-lucide="lock" class="w-8 h-8 text-white"></i>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Accedi al tuo account</h2>

                <!-- Error Message -->
                <div x-show="loginError" x-cloak class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start animate-fade-in">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mr-2 mt-0.5"></i>
                    <span class="text-sm text-red-800" x-text="loginError"></span>
                </div>

                <form @submit.prevent="login()">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" x-model="loginForm.email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                               placeholder="tuo@email.com">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input type="password" x-model="loginForm.password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                               placeholder="••••••••">
                    </div>

                    <button type="submit" :disabled="loading"
                            class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading">Accedi</span>
                        <span x-show="loading" class="flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Accesso in corso...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-600 animate-slide-in-up" style="animation-delay: 0.2s">
                <p>Powered by Payglobe</p>
            </div>
        </div>
    </div>

    <!-- Dashboard Screen -->
    <div x-show="isAuthenticated && !passwordChangeModal.show" x-cloak class="min-h-screen">

        <!-- Header -->
        <header class="glass-effect shadow-lg sticky top-0 z-50 animate-slide-in-up">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-500 p-2 rounded-lg">
                            <i data-lucide="bar-chart-3" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                                <svg class="inline h-6 mr-2" viewBox="0 0 761.13 177.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                  <defs>
                                    <style>
                                      .cls-1 { fill: url(#Nuovo_campione_sfumatura_2); }
                                      .cls-2 { fill: #06112c; }
                                      .cls-3 { fill: url(#Nuovo_campione_sfumatura_2-2); }
                                    </style>
                                    <linearGradient id="Nuovo_campione_sfumatura_2" x1="74.71" y1="17.34" x2="109.39" y2="17.34" gradientUnits="userSpaceOnUse">
                                      <stop offset="0" stop-color="#488dec"/>
                                      <stop offset="1" stop-color="#9a1bf1"/>
                                    </linearGradient>
                                    <linearGradient id="Nuovo_campione_sfumatura_2-2" x1="0" y1="106.75" x2="154.73" y2="106.75" xlink:href="#Nuovo_campione_sfumatura_2"/>
                                  </defs>
                                  <g>
                                    <g>
                                      <path class="cls-2" d="m431.25,46.38c20.22,0,30.1,10,30.1,10l-8.73,10.34s-7.7-6.78-21.03-6.78c-20.68,0-34.01,17.12-34.01,34.7,0,14.36,9.65,22.52,22.4,22.52s22.29-8.85,22.29-8.85l1.84-9.19h-12.64l2.53-12.75h25.85l-8.39,42.97h-12.64l.8-4.02c.34-1.72.81-3.45.81-3.45h-.23s-8.5,8.85-23.32,8.85c-18.73,0-34.58-13.44-34.58-35.16,0-26.66,21.83-49.18,48.94-49.18"/>
                                      <polygon class="cls-2" points="484.11 47.76 498.93 47.76 485.49 116.59 520.76 116.59 518.11 129.34 468.25 129.34 484.11 47.76"/>
                                      <path class="cls-2" d="m577.86,46.39c22.4,0,37.23,14.71,37.23,35.04,0,26.65-23.78,49.29-48.37,49.29-22.52,0-37.11-15.05-37.11-35.85,0-26.2,23.44-48.49,48.26-48.49m-11.03,70.77c16.31,0,32.97-15.51,32.97-34.81,0-13.33-9.08-22.4-22.06-22.4-16.66,0-32.97,15.28-32.97,34.12,0,13.67,9.08,23.09,22.06,23.09"/>
                                      <path class="cls-2" d="m637.85,47.76h26.2c4.71,0,8.85.46,12.29,1.72,7.47,2.64,11.83,8.73,11.83,16.77,0,8.62-5.28,16.43-13.33,20.11v.23c6.55,2.41,9.88,8.62,9.88,15.85,0,12.52-7.81,21.26-18.27,24.93-3.91,1.38-8.16,1.95-12.41,1.95h-32.05l15.85-81.57Zm17.23,68.82c2.53,0,4.83-.46,6.78-1.5,4.59-2.41,7.7-7.35,7.7-12.98s-3.56-9.08-9.88-9.08h-15.85l-4.6,23.56h15.85Zm5.4-35.5c7.24,0,12.41-5.86,12.41-12.87,0-4.48-2.64-7.7-8.62-7.7h-14.13l-4.02,20.57h14.36Z"/>
                                      <polygon class="cls-2" points="712.42 47.76 761.13 47.76 758.61 60.52 724.6 60.52 720.46 81.89 747.92 81.89 745.39 94.64 717.93 94.64 713.68 116.59 749.53 116.59 747.12 129.34 696.45 129.34 712.42 47.76"/>
                                      <path class="cls-2" d="m189.47,48.68h29.64c14.82,0,25.51,10,25.51,25.39s-10.68,25.73-25.51,25.73h-18.27v29.99h-11.37V48.68Zm27.8,41.25c9.77,0,15.74-6.09,15.74-15.85s-5.97-15.51-15.63-15.51h-16.54v31.37h16.43Z"/>
                                      <path class="cls-2" d="m297.02,106.47h-30.56l-8.04,23.32h-11.72l29.18-81.12h11.95l29.18,81.12h-11.83l-8.16-23.32Zm-15.28-46.65s-1.84,7.35-3.22,11.49l-9.08,25.73h24.59l-8.96-25.73c-1.38-4.14-3.1-11.49-3.1-11.49h-.23Z"/>
                                      <path class="cls-2" d="m341.14,95.44l-27.23-46.76h12.87l15.05,26.66c2.53,4.48,4.94,10.23,4.94,10.23h.23s2.41-5.63,4.94-10.23l14.82-26.66h12.87l-27.11,46.76v34.35h-11.38v-34.35Z"/>
                                    </g>
                                    <g>
                                      <path class="cls-1" d="m103.24,30.59c7.31-6.18,8.23-17.12,2.06-24.44-6.19-7.32-17.12-8.24-24.44-2.06-7.31,6.18-8.24,17.12-2.06,24.44,6.18,7.31,17.12,8.24,24.44,2.06Z"/>
                                      <path class="cls-3" d="m139.67,142.35c28.74-41.92,13.35-99.92-20.92-105.49-41.38-6.95-66.36,65.65-58.42,65.09,0,0,0,0,0,0,4.6-.32,16.89-20,31.61-32.54,5.54-4.7,15.12-11.34,21.88-8.72,6.4,2.54,9.62,12.45,10.5,19.65,2.1,16.81-4.75,32.71-9.57,38.9-13.07,16.81-31.6,26.31-46.84,27.13-24.12,1.29-45.56-14.91-52.06-37.52-6.55-22.71,2.28-50.29,26.54-68.58,0,0,0,0,0,0,3.45-2.59,5.56-3.81,5.55-3.83,0,0-33.33,11.61-44.62,47.17-18.78,58.97,45.13,114.34,106.28,85.75,5.61-2.72,15.19-9.32,23.24-18.33-.03.04-.07.07-.1.11.15-.16.31-.33.45-.49,2.36-2.67,4.51-5.44,6.45-8.28"/>
                                    </g>
                                  </g>
                                </svg>
                                Dashboard
                            </h1>
                            <div class="flex items-center space-x-2">
                                <p class="text-sm text-gray-600" x-text="user ? user.email : ''"></p>
                                <span x-show="user && user.isAdmin" class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded flex items-center space-x-1">
                                    <i data-lucide="shield-check" class="w-3 h-3"></i>
                                    <span>ADMIN</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="logout()"
                            class="flex items-center space-x-2 px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg transition">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Esci</span>
                    </button>
                </div>

                <!-- Navigation Menu -->
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <nav class="flex flex-wrap gap-2">
                        <button @click="currentPage = 'dashboard'"
                                :class="currentPage === 'dashboard' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="flex items-center space-x-2 px-4 py-2 rounded-lg transition font-medium">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                            <span>Dashboard</span>
                        </button>
                        <button @click="currentPage = 'statistics'"
                                :class="currentPage === 'statistics' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="flex items-center space-x-2 px-4 py-2 rounded-lg transition font-medium">
                            <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                            <span>Statistiche</span>
                        </button>
                        <button @click="currentPage = 'stores'"
                                :class="currentPage === 'stores' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                class="flex items-center space-x-2 px-4 py-2 rounded-lg transition font-medium">
                            <i data-lucide="store" class="w-4 h-4"></i>
                            <span>Negozi</span>
                        </button>

                        <!-- Admin Only Menus -->
                        <template x-if="user && user.isAdmin">
                            <div class="flex gap-2 ml-4 pl-4 border-l border-gray-300">
                                <button @click="currentPage = 'users'; loadUsers()"
                                        :class="currentPage === 'users' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
                                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition font-medium">
                                    <i data-lucide="users" class="w-4 h-4"></i>
                                    <span>Utenti</span>
                                </button>
                                <button @click="currentPage = 'activation'; loadActivationCodes()"
                                        :class="currentPage === 'activation' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
                                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition font-medium">
                                    <i data-lucide="key" class="w-4 h-4"></i>
                                    <span>Codici Attivazione</span>
                                </button>
                                <button @click="currentPage = 'bin-table'"
                                        :class="currentPage === 'bin-table' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100'"
                                        class="flex items-center space-x-2 px-4 py-2 rounded-lg transition font-medium">
                                    <i data-lucide="database" class="w-4 h-4"></i>
                                    <span>Aggiorna BIN Table</span>
                                </button>
                            </div>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Dashboard Section -->
            <div x-show="currentPage === 'dashboard'" x-cloak>

            <!-- Filters -->
            <div class="glass-effect rounded-2xl shadow-lg p-6 mb-8 animate-slide-in-up" style="animation-delay: 0.1s">
                <div class="flex items-center mb-4">
                    <i data-lucide="filter" class="w-5 h-5 text-gray-600 mr-2"></i>
                    <h2 class="text-lg font-semibold text-gray-900">Filtri</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Inizio</label>
                        <input type="date" x-model="filters.startDate"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data Fine</label>
                        <input type="date" x-model="filters.endDate"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Punto Vendita</label>
                        <select x-model="filters.filterStore"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                            <option value="">-- Tutti i negozi --</option>
                            <template x-for="store in stores" :key="store.terminalIds">
                                <option :value="store.terminalIds">
                                    <span x-text="(store.insegna || store.ragioneSociale) + (store.indirizzo ? ' - ' + store.indirizzo : '') + (store.citta ? ' - ' + store.citta : '') + (store.terminalCount > 1 ? ' (' + store.terminalCount + ' terminali)' : '')"></span>
                                </option>
                            </template>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button @click="loadAllData()" :disabled="loading"
                                class="w-full flex items-center justify-center space-x-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg transition transform hover:scale-105 disabled:opacity-50">
                            <i data-lucide="refresh-cw" class="w-4 h-4" :class="{ 'animate-spin': loading }"></i>
                            <span>Applica</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <template x-for="(stat, index) in statsCards" :key="index">
                    <div class="stat-card glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up"
                         :style="`animation-delay: ${0.2 + index * 0.1}s`">
                        <div class="flex items-center justify-between mb-4">
                            <div :class="`p-3 rounded-lg ${stat.bgColor}`">
                                <i :data-lucide="stat.icon" class="w-6 h-6" :class="stat.iconColor"></i>
                            </div>
                        </div>
                        <h3 class="text-gray-600 text-sm font-medium mb-1" x-text="stat.title"></h3>
                        <p class="text-3xl font-bold text-gray-900" x-text="stat.value"></p>
                        <p class="text-sm mt-2" :class="stat.trend >= 0 ? 'text-green-600' : 'text-red-600'" x-show="stat.trend !== undefined">
                            <span x-text="stat.trend >= 0 ? '↑' : '↓'"></span>
                            <span x-text="Math.abs(stat.trend) + '%'"></span>
                        </p>
                    </div>
                </template>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Circuit Chart -->
                <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.6s">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="pie-chart" class="w-5 h-5 mr-2 text-blue-600"></i>
                        Distribuzione Circuiti
                    </h3>
                    <div id="circuitChart" class="w-full" style="height: 300px;"></div>
                </div>

                <!-- Trend Chart -->
                <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.7s">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="trending-up" class="w-5 h-5 mr-2 text-purple-600"></i>
                        Trend Giornaliero
                    </h3>
                    <div id="trendChart" class="w-full" style="height: 300px;"></div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.8s">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i data-lucide="list" class="w-5 h-5 mr-2 text-green-600"></i>
                        Transazioni Recenti
                    </h3>
                    <button @click="exportToCSV()" :disabled="transactions.length === 0"
                            class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        <span>Esporta CSV</span>
                    </button>
                    <input type="text" x-model="transactionSearch" placeholder="🔍 Cerca transazione..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" style="width: 250px;">
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Ora</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">POSID</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Store</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Importo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PAN</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Circuito</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Settlement</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Esito</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template x-for="(tx, index) in filteredTransactions" :key="index">
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900" x-text="formatDate(tx.transactionDate)"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600" x-text="tx.posid"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600" x-text="tx.storeInsegna || tx.storeRagioneSociale"></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800"
                                              x-text="translateTransactionType(tx.transactionType)"></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold" :class="tx.isRefund ? 'text-red-600' : 'text-gray-900'" x-text="formatCurrency(tx.amount)"></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 font-mono" x-text="tx.pan || '-'"></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded"
                                              :style="`background-color: ${getCircuitColor(tx.cardBrand).bg}; color: ${getCircuitColor(tx.cardBrand).text}`"
                                              x-text="translateCircuitCode(tx.cardBrand)"></span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span :class="tx.settlementFlag === '1' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                              class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            <span x-text="tx.settlementFlag === '1' ? 'OK' : 'NO'"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                                        <template x-if="tx.settlementFlag === '1'">
                                            <span class="text-green-600 font-medium text-xs">Approvato</span>
                                        </template>
                                        <template x-if="tx.settlementFlag !== '1'">
                                            <span :class="tx.responseCode === '00' || tx.responseCode === '000' ? 'text-green-600' : 'text-red-600'"
                                                  class="font-medium text-xs"
                                                  x-text="getResponseCodeDescription(tx.responseCode)"></span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4">
                    <button @click="prevPage()" :disabled="page === 0"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Precedente
                    </button>
                    <span class="text-sm text-gray-600">Pagina <span x-text="page + 1"></span> di <span x-text="totalPages"></span></span>
                    <button @click="nextPage()" :disabled="page >= totalPages - 1"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Successiva
                    </button>
                </div>
            </div>

            </div>
            <!-- End Dashboard Section -->

            <!-- Statistics Section -->
            <div x-show="currentPage === 'statistics'" x-cloak x-init="$watch('currentPage', value => { if (value === 'statistics' && stores.length > 0) { $nextTick(() => loadStatisticsCharts()) } })">

                <!-- Filters and Period Selector -->
                <div class="glass-effect rounded-2xl shadow-lg p-6 mb-8 animate-slide-in-up">
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center mb-4">
                        <i data-lucide="bar-chart-2" class="w-6 h-6 mr-2 text-purple-600"></i>
                        Analisi Statistiche Avanzate
                    </h2>

                    <div class="flex flex-col md:flex-row gap-4 items-start md:items-center">
                        <!-- Store Filter (Dropdown for <= 20 stores) -->
                        <div class="flex-1 w-full md:w-auto" x-show="stores.length <= 20">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Punto Vendita</label>
                            <select x-model="statsFilterStore" @change="loadStatisticsCharts()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none transition">
                                <option value="">-- Tutti i negozi --</option>
                                <template x-for="store in stores" :key="store.terminalIds">
                                    <option :value="store.terminalIds">
                                        <span x-text="(store.insegna || store.ragioneSociale) + (store.indirizzo ? ' - ' + store.indirizzo : '') + (store.citta ? ' - ' + store.citta : '') + (store.terminalCount > 1 ? ' (' + store.terminalCount + ' terminali)' : '')"></span>
                                    </option>
                                </template>
                            </select>
                        </div>

                        <!-- Store Filter (Search for > 20 stores) -->
                        <div class="flex-1 w-full md:w-auto" x-show="stores.length > 20">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cerca Punto Vendita</label>
                            <div class="relative">
                                <input type="text"
                                       list="storesList"
                                       x-model="statsFilterStoreSearch"
                                       @input="handleStatsStoreSearch()"
                                       @change="loadStatisticsCharts()"
                                       placeholder="Digita per cercare..."
                                       class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 outline-none transition">
                                <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-2.5"></i>
                                <datalist id="storesList">
                                    <template x-for="store in stores" :key="store.terminalIds">
                                        <option :value="(store.insegna || store.ragioneSociale) + (store.indirizzo ? ' - ' + store.indirizzo : '') + (store.citta ? ' - ' + store.citta : '')"
                                                :data-terminal-ids="store.terminalIds"></option>
                                    </template>
                                </datalist>
                                <button x-show="statsFilterStore"
                                        @click="statsFilterStore = ''; statsFilterStoreSearch = ''; loadStatisticsCharts()"
                                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                    <i data-lucide="x" class="w-5 h-5"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1" x-show="statsFilterStore === ''">
                                Tutti i negozi (<span x-text="stores.length"></span>)
                            </p>
                        </div>

                        <!-- Period Selector -->
                        <div class="flex-shrink-0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Periodo</label>
                            <div class="flex space-x-2">
                                <button @click="statsPeriod = '7d'; loadStatisticsCharts()"
                                        :class="statsPeriod === '7d' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
                                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                                    7 Giorni
                                </button>
                                <button @click="statsPeriod = '30d'; loadStatisticsCharts()"
                                        :class="statsPeriod === '30d' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
                                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                                    30 Giorni
                                </button>
                                <button @click="statsPeriod = '90d'; loadStatisticsCharts()"
                                        :class="statsPeriod === '90d' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
                                        class="px-4 py-2 rounded-lg text-sm font-medium transition">
                                    90 Giorni
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Badge -->
                    <div class="flex items-center gap-2 mt-4" x-show="statsFilterStore">
                        <span class="text-sm text-gray-600">Filtrando per:</span>
                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium flex items-center gap-2"
                              x-text="stores.find(s => s.terminalIds === statsFilterStore)?.insegna || stores.find(s => s.terminalIds === statsFilterStore)?.ragioneSociale || 'Negozio'">
                        </span>
                        <button @click="statsFilterStore = ''; statsFilterStoreSearch = ''; loadStatisticsCharts()"
                                class="text-sm text-purple-600 hover:text-purple-800 underline">
                            Rimuovi filtro
                        </button>
                    </div>
                </div>

                <!-- Advanced Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Tasso di Successo -->
                    <div class="stat-card glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-lg bg-green-50">
                                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                            </div>
                        </div>
                        <h3 class="text-gray-600 text-sm font-medium mb-1">Tasso di Successo</h3>
                        <p class="text-3xl font-bold text-gray-900" x-text="statsData.successRate || '0%'"></p>
                        <p class="text-sm text-gray-500 mt-2">Transazioni approvate</p>
                    </div>

                    <!-- Importo Massimo -->
                    <div class="stat-card glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-lg bg-blue-50">
                                <i data-lucide="arrow-up-circle" class="w-6 h-6 text-blue-600"></i>
                            </div>
                        </div>
                        <h3 class="text-gray-600 text-sm font-medium mb-1">Importo Massimo</h3>
                        <p class="text-3xl font-bold text-gray-900" x-text="formatCurrency(statsData.maxAmount || 0)"></p>
                        <p class="text-sm text-gray-500 mt-2">Singola transazione</p>
                    </div>

                    <!-- Importo Minimo -->
                    <div class="stat-card glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-lg bg-purple-50">
                                <i data-lucide="arrow-down-circle" class="w-6 h-6 text-purple-600"></i>
                            </div>
                        </div>
                        <h3 class="text-gray-600 text-sm font-medium mb-1">Importo Minimo</h3>
                        <p class="text-3xl font-bold text-gray-900" x-text="formatCurrency(statsData.minAmount || 0)"></p>
                        <p class="text-sm text-gray-500 mt-2">Singola transazione</p>
                    </div>

                    <!-- Orario di Picco -->
                    <div class="stat-card glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.3s">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-lg bg-orange-50">
                                <i data-lucide="clock" class="w-6 h-6 text-orange-600"></i>
                            </div>
                        </div>
                        <h3 class="text-gray-600 text-sm font-medium mb-1">Orario di Picco</h3>
                        <p class="text-3xl font-bold text-gray-900" x-text="statsData.peakHour || '--:--'"></p>
                        <p class="text-sm text-gray-500 mt-2">Fascia oraria più attiva</p>
                    </div>
                </div>

                <!-- Advanced Charts Row 1 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Hourly Distribution -->
                    <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.4s">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="clock" class="w-5 h-5 mr-2 text-purple-600"></i>
                            Distribuzione Oraria
                        </h3>
                        <div id="hourlyChart" class="w-full" style="height: 300px;"></div>
                    </div>

                    <!-- Weekday Distribution -->
                    <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.5s">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="calendar" class="w-5 h-5 mr-2 text-blue-600"></i>
                            Distribuzione per Giorno della Settimana
                        </h3>
                        <div id="weekdayChart" class="w-full" style="height: 300px;"></div>
                    </div>
                </div>

                <!-- Advanced Charts Row 2 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Amount Range Distribution -->
                    <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.6s">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="bar-chart-3" class="w-5 h-5 mr-2 text-green-600"></i>
                            Distribuzione per Fascia di Importo
                        </h3>
                        <div id="amountRangeChart" class="w-full" style="height: 300px;"></div>
                    </div>

                    <!-- Top 10 Banks (BIN) -->
                    <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.7s">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i data-lucide="building-2" class="w-5 h-5 mr-2 text-orange-600"></i>
                            Top 10 Banche per Volume
                        </h3>
                        <div id="topBanksChart" class="w-full" style="height: 300px;"></div>
                    </div>
                </div>

                <!-- Transaction Types Comparison -->
                <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up" style="animation-delay: 0.8s">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i data-lucide="git-compare" class="w-5 h-5 mr-2 text-purple-600"></i>
                        Confronto Tipi di Transazione
                    </h3>
                    <div id="transactionTypesChart" class="w-full" style="height: 400px;"></div>
                </div>

            </div>

            <!-- Stores Section -->
            <div x-show="currentPage === 'stores'" x-cloak x-data="{ storeSearchText: '', get filteredStores() { if (!this.storeSearchText) return stores; const search = this.storeSearchText.toLowerCase(); return stores.filter(s => { const name = (s.insegna || s.ragioneSociale || '').toLowerCase(); const address = (s.indirizzo || '').toLowerCase(); const city = (s.citta || '').toLowerCase(); return name.includes(search) || address.includes(search) || city.includes(search); }); } }">
                <div class="glass-effect rounded-2xl shadow-lg p-6 animate-slide-in-up">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <i data-lucide="store" class="w-6 h-6 mr-2 text-green-600"></i>
                            I Miei Negozi
                        </h2>
                        <div class="flex items-center gap-4">
                            <!-- Search (shown when > 20 stores) -->
                            <div class="relative" x-show="stores.length > 20">
                                <input type="text"
                                       x-model="storeSearchText"
                                       placeholder="Cerca negozio..."
                                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none transition w-64">
                                <i data-lucide="search" class="w-5 h-5 text-gray-400 absolute left-3 top-2.5"></i>
                                <button x-show="storeSearchText"
                                        @click="storeSearchText = ''"
                                        class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                            <!-- Store count -->
                            <div class="text-sm text-gray-600 whitespace-nowrap">
                                <span class="font-semibold" x-text="storeSearchText ? filteredStores.length + '/' + stores.length : stores.length"></span> negozi
                                <span x-show="storeSearchText" x-text="'trovati'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Stores Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="store in filteredStores" :key="store.terminalIds">
                            <div class="bg-white rounded-xl border-2 border-gray-200 hover:border-green-400 transition-all p-6 hover:shadow-lg">
                                <!-- Store Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-bold text-gray-900 mb-1"
                                            x-text="store.insegna || store.ragioneSociale || 'Negozio'"></h3>
                                        <p class="text-sm text-gray-600"
                                           x-text="store.ragioneSociale"
                                           x-show="store.insegna && store.ragioneSociale !== store.insegna"></p>
                                    </div>
                                    <div class="bg-green-100 p-2 rounded-lg">
                                        <i data-lucide="store" class="w-5 h-5 text-green-600"></i>
                                    </div>
                                </div>

                                <!-- Store Details -->
                                <div class="space-y-3 mb-4">
                                    <!-- Address -->
                                    <div class="flex items-start" x-show="store.indirizzo">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-gray-400 mr-2 mt-0.5 flex-shrink-0"></i>
                                        <div class="text-sm text-gray-700">
                                            <span x-text="store.indirizzo"></span>
                                            <span x-show="store.citta" x-text="', ' + store.citta"></span>
                                        </div>
                                    </div>

                                    <!-- Business Unit -->
                                    <div class="flex items-center" x-show="store.bu">
                                        <i data-lucide="building" class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0"></i>
                                        <span class="text-sm text-gray-700">BU: <span class="font-medium" x-text="store.bu"></span></span>
                                    </div>

                                    <!-- Terminal Info -->
                                    <div class="flex items-center">
                                        <i data-lucide="tablet" class="w-4 h-4 text-gray-400 mr-2 flex-shrink-0"></i>
                                        <span class="text-sm text-gray-700">
                                            <span class="font-medium" x-text="store.terminalCount || 1"></span>
                                            <span x-text="store.terminalCount > 1 ? 'terminali' : 'terminale'"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- Terminal IDs -->
                                <div class="pt-4 border-t border-gray-200">
                                    <div class="text-xs text-gray-500 mb-2">Terminal IDs:</div>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="terminalId in (store.terminalIds || '').split(',')" :key="terminalId">
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-mono"
                                                  x-text="terminalId.trim()"></span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Quick Action -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <button @click="filters.filterStore = store.terminalIds; currentPage = 'dashboard'; loadAllData()"
                                            class="w-full px-4 py-2 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg text-sm font-medium transition flex items-center justify-center space-x-2">
                                        <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                                        <span>Vedi Transazioni</span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State - No stores at all -->
                    <div x-show="stores.length === 0" class="text-center py-12">
                        <div class="bg-gray-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="store" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <p class="text-gray-600 mb-2">Nessun negozio trovato</p>
                        <p class="text-sm text-gray-500">Contatta il supporto per configurare i tuoi punti vendita</p>
                    </div>

                    <!-- Empty State - Search no results -->
                    <div x-show="stores.length > 0 && filteredStores.length === 0 && storeSearchText" class="text-center py-12">
                        <div class="bg-gray-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="search" class="w-8 h-8 text-gray-400"></i>
                        </div>
                        <p class="text-gray-600 mb-2">Nessun negozio corrisponde alla ricerca</p>
                        <p class="text-sm text-gray-500 mb-4">
                            Prova con un termine diverso o
                            <button @click="storeSearchText = ''" class="text-green-600 hover:text-green-700 font-medium underline">cancella la ricerca</button>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Users Management Section (Admin Only) -->
            <div x-show="currentPage === 'users'" x-cloak>
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i data-lucide="users" class="w-8 h-8 mr-3 text-red-600"></i>
                        Gestione Utenti
                    </h2>
                    <p class="text-gray-600 mt-2">Crea e gestisci gli utenti del sistema</p>
                </div>

                <!-- Stats Cards -->
                <div x-show="userStats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Utenti Totali</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="userStats?.totalUsers || 0"></p>
                            </div>
                            <i data-lucide="users" class="w-8 h-8 text-blue-600"></i>
                        </div>
                    </div>
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Attivi</p>
                                <p class="text-2xl font-bold text-green-600" x-text="userStats?.activeUsers || 0"></p>
                            </div>
                            <i data-lucide="check-circle" class="w-8 h-8 text-green-600"></i>
                        </div>
                    </div>
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Password da Cambiare</p>
                                <p class="text-2xl font-bold text-yellow-600" x-text="userStats?.pendingPasswordChange || 0"></p>
                            </div>
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Password Scadute</p>
                                <p class="text-2xl font-bold text-red-600" x-text="userStats?.expiredPasswords || 0"></p>
                            </div>
                            <i data-lucide="x-circle" class="w-8 h-8 text-red-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Actions Bar -->
                <div class="flex justify-between items-center mb-4">
                    <button @click="openCreateUserModal()" class="flex items-center space-x-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Nuovo Utente</span>
                    </button>
                    <div class="flex-1 max-w-md ml-4">
                        <input type="text" x-model="userSearch" @input="loadUsers()"
                               placeholder="Cerca per email, BU, ragione sociale..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>
                </div>

                <!-- Users Table -->
                <div class="glass-effect rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">BU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Ragione Sociale</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Stato</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Password</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Azioni</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="u in users" :key="u.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <span class="font-medium" x-text="u.email"></span>
                                            <span x-show="u.isAdmin" class="ml-2 px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded">ADMIN</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded" x-text="u.bu"></span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="u.ragioneSociale || 'N/D'"></td>
                                        <td class="px-6 py-4">
                                            <span :class="u.active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                                  class="px-2 py-1 text-xs font-medium rounded"
                                                  x-text="u.active ? 'Attivo' : 'Inattivo'"></span>
                                            <div x-show="u.forcePasswordChange" class="text-xs text-yellow-600 mt-1">
                                                <i data-lucide="alert-triangle" class="w-3 h-3 inline"></i> Deve cambiare password
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="u.passwordAge >= 45 ? 'text-red-600' : (u.passwordAge >= 35 ? 'text-yellow-600' : 'text-green-600')"
                                                  class="text-xs" x-text="u.passwordAge + ' giorni'"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <button @click="openEditUserModal(u.id)"
                                                        class="text-blue-600 hover:text-blue-700" title="Modifica">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="resetUserPassword(u.id, u.email)"
                                                        class="text-yellow-600 hover:text-yellow-700" title="Reset Password">
                                                    <i data-lucide="key" class="w-4 h-4"></i>
                                                </button>
                                                <button @click="deleteUser(u.id, u.email)"
                                                        class="text-red-600 hover:text-red-700" title="Elimina">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="users.length === 0" class="text-center py-12 text-gray-500">
                            Nessun utente trovato
                        </div>
                    </div>
                </div>

                <!-- User Modal -->
                <div x-show="userModal.show" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="userModal.show = false">
                    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4">
                        <h3 class="text-2xl font-bold mb-6" x-text="userModal.mode === 'create' ? 'Nuovo Utente' : 'Modifica Utente'"></h3>
                        <form @submit.prevent="saveUser()">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input type="email" x-model="userModal.user.email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div x-show="userModal.mode === 'create'" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                                <input type="password" x-model="userModal.password" :required="userModal.mode === 'create'" minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                <p class="text-xs text-gray-500 mt-1">Minimo 8 caratteri</p>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Business Unit *</label>
                                <input type="text" x-model="userModal.user.bu" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                                <p class="text-xs text-gray-500 mt-1">9999 = Admin | Altri codici = Accesso limitato</p>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ragione Sociale</label>
                                <input type="text" x-model="userModal.user.ragioneSociale" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            </div>
                            <div class="mb-4">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" x-model="userModal.user.active" class="rounded text-blue-600">
                                    <span class="text-sm">Utente Attivo</span>
                                </label>
                            </div>
                            <div class="mb-6">
                                <label class="flex items-center space-x-2">
                                    <input type="checkbox" x-model="userModal.user.forcePasswordChange" class="rounded text-blue-600">
                                    <span class="text-sm">Forza cambio password al prossimo login</span>
                                </label>
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" @click="userModal.show = false" class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">Annulla</button>
                                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">Salva</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Activation Codes Section -->
            <div x-show="currentPage === 'activation'" x-cloak>
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i data-lucide="key" class="w-8 h-8 mr-3 text-red-600"></i>
                        Codici di Attivazione PAX
                    </h2>
                    <p class="text-gray-600 mt-2">Genera codici ACT per Terminal ID - Scadenza 21 giorni</p>
                </div>

                <!-- Stats Cards -->
                <div x-show="activationCodeStats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Totali</p>
                                <p class="text-2xl font-bold text-gray-900" x-text="activationCodeStats?.total || 0"></p>
                            </div>
                            <i data-lucide="list" class="w-8 h-8 text-blue-600"></i>
                        </div>
                    </div>
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Attivi</p>
                                <p class="text-2xl font-bold text-green-600" x-text="activationCodeStats?.active || 0"></p>
                            </div>
                            <i data-lucide="play-circle" class="w-8 h-8 text-green-600"></i>
                        </div>
                    </div>
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Utilizzati</p>
                                <p class="text-2xl font-bold text-blue-600" x-text="activationCodeStats?.used || 0"></p>
                            </div>
                            <i data-lucide="check-circle" class="w-8 h-8 text-blue-600"></i>
                        </div>
                    </div>
                    <div class="glass-effect rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Scaduti</p>
                                <p class="text-2xl font-bold text-yellow-600" x-text="activationCodeStats?.expired || 0"></p>
                            </div>
                            <i data-lucide="clock" class="w-8 h-8 text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <!-- Create Form -->
                <div class="glass-effect rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold mb-4 flex items-center">
                        <i data-lucide="plus-circle" class="w-5 h-5 mr-2 text-green-600"></i>
                        Crea Nuovo Codice
                    </h3>
                    <form @submit.prevent="createActivationCode()" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Terminal ID *</label>
                            <input type="text" x-model="activationCodeForm.terminalId" required maxlength="15" pattern="[A-Za-z0-9]{6,15}"
                                   placeholder="12340002" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Business Unit</label>
                            <input type="text" x-model="activationCodeForm.bu" maxlength="10" placeholder="001"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lingua</label>
                            <select x-model="activationCodeForm.language" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                                <option value="it">Italiano</option>
                                <option value="en">English</option>
                                <option value="de">Deutsch</option>
                                <option value="fr">Français</option>
                                <option value="es">Español</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                            <input type="text" x-model="activationCodeForm.notes" placeholder="Descrizione..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                            <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium">
                                <i data-lucide="plus" class="w-4 h-4 inline mr-1"></i> Crea Codice ACT
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Filters -->
                <div class="glass-effect rounded-xl shadow-lg p-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <select x-model="activationCodeFilters.status" @change="loadActivationCodes()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="all">Tutti gli stati</option>
                            <option value="PENDING">Attivi</option>
                            <option value="USED">Utilizzati</option>
                            <option value="EXPIRED">Scaduti</option>
                        </select>
                        <input type="text" x-model="activationCodeFilters.search" @input="loadActivationCodes()" placeholder="Cerca codice, terminal, note..."
                               class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        <button @click="activationCodeFilters = { status: 'all', bu: '', search: '' }; loadActivationCodes()"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                            Reset Filtri
                        </button>
                    </div>
                </div>

                <!-- Codes Table -->
                <div class="glass-effect rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Codice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Terminal ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">BU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Lingua</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Stato</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Scadenza</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Note</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Azioni</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="code in activationCodes" :key="code.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <code class="text-sm font-mono" x-text="code.code"></code>
                                            <button @click="copyCode(code.code)" class="ml-2 text-blue-600 hover:text-blue-700">
                                                <i data-lucide="copy" class="w-4 h-4 inline"></i>
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 font-medium" x-text="code.storeTerminalId"></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded" x-text="code.bu"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded uppercase" x-text="code.language"></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="code.pending ? 'bg-green-100 text-green-700' : (code.expired ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700')"
                                                  class="px-2 py-1 text-xs font-medium rounded"
                                                  x-text="code.pending ? 'Attivo' : (code.expired ? 'Scaduto' : 'Utilizzato')"></span>
                                            <div x-show="code.daysLeft !== null && code.pending" class="text-xs text-gray-500 mt-1" x-text="code.daysLeft + ' giorni'"></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="new Date(code.expiresAt).toLocaleDateString('it-IT')"></td>
                                        <td class="px-6 py-4 text-sm text-gray-600" x-text="code.notes || '-'"></td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <a :href="'https://ricevute.payglobe.it/merchant/terminal_config_editor.php?terminal_id=' + code.storeTerminalId"
                                                   target="_blank"
                                                   class="text-blue-600 hover:text-blue-700" title="Modifica Configurazione">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </a>
                                                <a :href="'https://ricevute.payglobe.it/merchant/api/terminal/config.php?activationCode=' + code.code"
                                                   target="_blank"
                                                   class="text-green-600 hover:text-green-700" title="Visualizza JSON">
                                                    <i data-lucide="file-json" class="w-4 h-4"></i>
                                                </a>
                                                <button @click="deleteActivationCode(code.id, code.code)"
                                                        class="text-red-600 hover:text-red-700" title="Elimina">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <div x-show="activationCodes.length === 0" class="text-center py-12 text-gray-500">
                            Nessun codice trovato
                        </div>
                    </div>
                </div>
            </div>

            <!-- BIN Table Section (Admin Only) -->
            <div x-show="currentPage === 'bin-table'" x-cloak>
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i data-lucide="database" class="w-8 h-8 mr-3 text-red-600"></i>
                        Aggiorna BIN Table
                    </h2>
                    <p class="text-gray-600 mt-2">Upload file CSV per aggiornare la tabella BIN (Bank Identification Number)</p>
                </div>

                <div class="glass-effect rounded-xl shadow-lg p-8">
                    <div class="max-w-2xl mx-auto">
                        <div class="text-center mb-8">
                            <div class="bg-blue-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="upload-cloud" class="w-10 h-10 text-blue-600"></i>
                            </div>
                            <h3 class="text-xl font-semibold mb-2">Carica File CSV</h3>
                            <p class="text-gray-600">Seleziona un file CSV contenente i dati BIN aggiornati</p>
                        </div>

                        <form @submit.prevent="uploadBinTable()">
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">File CSV o ZIP *</label>
                                <input type="file" id="binFileInput" accept=".csv,.zip" @change="handleBinFileSelect($event)" required
                                       class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 transition cursor-pointer
                                              file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium
                                              file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <small class="text-gray-500 mt-2 block">Supporta file CSV diretti oppure file ZIP contenenti CSV (consigliato per file grandi)</small>
                            </div>

                            <div x-show="binUploadMessage" class="mb-6 p-4 rounded-lg" :class="binUploadMessage.startsWith('✅') ? 'bg-green-50 text-green-700 border border-green-200' : (binUploadMessage.startsWith('❌') ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-blue-50 text-blue-700 border border-blue-200')">
                                <div class="flex items-start">
                                    <div class="flex-1">
                                        <p class="font-medium" x-text="binUploadMessage"></p>
                                        <p x-show="binImportStatus === 'processing' && binProcessedRecords > 0" class="text-sm mt-1 opacity-75">
                                            <span x-text="binProcessedRecords.toLocaleString()"></span> / <span x-text="binTotalRecords > 0 ? binTotalRecords.toLocaleString() : '~'"></span> record
                                        </p>
                                    </div>
                                    <div x-show="binImportStatus === 'processing'" class="ml-3">
                                        <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                                    </div>
                                </div>
                            </div>

                            <div x-show="binUploadProgress > 0 && binUploadProgress < 100" class="mb-6">
                                <div class="flex justify-between text-sm text-gray-600 mb-2">
                                    <span>Progresso</span>
                                    <span class="font-semibold" x-text="`${binUploadProgress.toFixed(1)}%`"></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-3 rounded-full transition-all duration-500 ease-out" :style="`width: ${binUploadProgress}%`">
                                        <div class="h-full w-full opacity-25 bg-gradient-to-r from-transparent via-white to-transparent animate-pulse-slow"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex space-x-4">
                                <button type="submit" :disabled="loading || !binUploadFile"
                                        class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white rounded-lg transition font-medium">
                                    <i data-lucide="upload" class="w-5 h-5 inline mr-2"></i>
                                    Carica e Importa
                                </button>
                            </div>
                        </form>

                        <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-600 mr-2 mt-0.5"></i>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-semibold mb-1">Attenzione:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Il file deve essere in formato CSV</li>
                                        <li>L'operazione sostituirà tutti i dati esistenti</li>
                                        <li>Assicurati che il file sia corretto prima di procedere</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Password Change Modal (outside isAuthenticated div so it's visible during login) -->
    <div x-show="passwordChangeModal.show" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="shield-alert" class="w-8 h-8 text-yellow-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900">Cambio Password Richiesto</h3>
                <p class="text-gray-600 mt-2">Per motivi di sicurezza, devi cambiare la tua password prima di continuare.</p>
            </div>
            <form @submit.prevent="submitPasswordChange()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Attuale *</label>
                    <input type="password" x-model="passwordChangeModal.oldPassword" required minlength="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuova Password *</label>
                    <input type="password" x-model="passwordChangeModal.newPassword" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                    <p class="text-xs text-gray-500 mt-1">Minimo 8 caratteri</p>
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conferma Nuova Password *</label>
                    <input type="password" x-model="passwordChangeModal.confirmPassword" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div x-show="passwordChangeModal.error" class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm" x-text="passwordChangeModal.error"></div>
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    Cambia Password
                </button>
            </form>
        </div>
    </div>

    <script src="js/merchant-console-v2.5.0.js"></script>

    <!-- Auto-reload after login (polling mechanism) -->
    <script>
        (function() {
            let hadToken = !!localStorage.getItem('accessToken');
            let pollCount = 0;
            const maxPolls = 300; // 30 seconds max (300 * 100ms)

            // Clear the reload flag when page loads
            if (hadToken) {
                sessionStorage.setItem('dashboardLoaded', 'true');
            }

            // Poll every 100ms to detect when login adds tokens
            const pollInterval = setInterval(function() {
                pollCount++;

                const hasToken = !!localStorage.getItem('accessToken');
                const wasLoaded = sessionStorage.getItem('dashboardLoaded');

                // If token just appeared and we haven't reloaded yet
                if (!hadToken && hasToken && !wasLoaded) {
                    console.log('[Auto-reload] Login detected, reloading page...');
                    sessionStorage.setItem('dashboardLoaded', 'true');
                    clearInterval(pollInterval);
                    setTimeout(function() {
                        window.location.reload();
                    }, 50);
                    return;
                }

                // Update state
                hadToken = hasToken;

                // Stop polling after 30 seconds or if we already have the dashboard
                if (pollCount >= maxPolls || (hasToken && wasLoaded)) {
                    clearInterval(pollInterval);
                }
            }, 100);

            // Clear flag on logout
            window.addEventListener('storage', function(e) {
                if (e.key === 'accessToken' && !e.newValue) {
                    sessionStorage.removeItem('dashboardLoaded');
                }
            });
        })();
    </script>
</body>
</html>
