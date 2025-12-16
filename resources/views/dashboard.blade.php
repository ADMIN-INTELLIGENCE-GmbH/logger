@extends('layouts.logger')

@section('title', 'Dashboard')

@section('content')
<div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Overview of all projects and server status</p>
        </div>
        <div x-data="autoRefresh()" class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" x-model="enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                </label>
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Auto-refresh</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400" x-show="enabled" x-text="'Next: ' + countdown + 's'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                    <i class="mdi mdi-folder-multiple text-2xl text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Projects</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $overallStats['total_projects'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                    <i class="mdi mdi-file-document-multiple text-2xl text-blue-600 dark:text-blue-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Logs (24h)</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['total_logs_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 bg-red-100 dark:bg-red-900 rounded-lg">
                    <i class="mdi mdi-alert-circle text-2xl text-red-600 dark:text-red-400"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Errors (24h)</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($overallStats['total_errors_24h']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 p-3 {{ $overallStats['overall_error_rate'] > 10 ? 'bg-red-100 dark:bg-red-900' : 'bg-green-100 dark:bg-green-900' }} rounded-lg">
                    <i class="mdi mdi-chart-bar text-2xl {{ $overallStats['overall_error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Error Rate</h3>
                    <p class="text-2xl font-bold {{ $overallStats['overall_error_rate'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $overallStats['overall_error_rate'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Section -->
    <div x-data='projectList(@json($projects, JSON_HEX_APOS), @json($hiddenMetrics))'>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">All Projects</h2>
            
            <div class="flex items-center gap-3">
                <!-- Metrics Settings -->
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        <i class="mdi mdi-tune-variant"></i>
                        Metrics
                    </button>
                    
                    <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10">
                        <div class="p-4">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Show Metrics</h3>
                            <div class="space-y-2">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('php_version')" @change="toggleMetric('php_version')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">PHP Version</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('laravel_version')" @change="toggleMetric('laravel_version')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Laravel Version</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('log_shipper_version')" @change="toggleMetric('log_shipper_version')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Log Shipper Version</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('debug_mode')" @change="toggleMetric('debug_mode')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Debug Mode</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('cpu')" @change="toggleMetric('cpu')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">CPU Usage</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('memory')" @change="toggleMetric('memory')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Memory Usage</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('disk')" @change="toggleMetric('disk')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Disk Usage</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('queue')" @change="toggleMetric('queue')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Queue Size</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('composer')" @change="toggleMetric('composer')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Composer Status</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('npm')" @change="toggleMetric('npm')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">npm Status</span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" :checked="!hiddenMetrics.includes('updated')" @change="toggleMetric('updated')" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Last Updated</span>
                                </label>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <button @click="resetMetrics()" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                    Show all metrics
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- View Toggle -->
                <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                    <button @click="view = 'grid'" :class="{ 'bg-white dark:bg-gray-600 shadow-sm': view === 'grid', 'text-gray-500 dark:text-gray-400': view !== 'grid' }" class="p-2 rounded-md transition-all">
                        <i class="mdi mdi-view-grid text-xl"></i>
                    </button>
                    <button @click="view = 'table'" :class="{ 'bg-white dark:bg-gray-600 shadow-sm': view === 'table', 'text-gray-500 dark:text-gray-400': view !== 'table' }" class="p-2 rounded-md transition-all">
                        <i class="mdi mdi-view-list text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="mb-6">
            <div class="relative">
                <input type="text" x-model="searchQuery" placeholder="Search projects by name or tag..." class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="searchQuery" x-cloak>
                Showing <span x-text="filteredProjects.length"></span> of <span x-text="projects.length"></span> projects
            </p>
        </div>

        <!-- Grid View -->
        <div x-show="view === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="item in sortedFilteredProjects" :key="item.project.id">
                <a :href="item.dashboard_url" class="relative group block bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow overflow-hidden">
                    
                    <div class="p-6 h-full flex flex-col gap-4">
                        <!-- Fixed Header -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="space-y-2 min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate" x-text="item.project.name"></h3>
                                    <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase tracking-wide" 
                                          x-text="item.project.server_stats && item.project.server_stats.app_env ? item.project.server_stats.app_env : 'Unknown'"></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span x-text="formatNumber(item.total_logs_24h) + ' logs'"></span>
                                    <span>•</span>
                                    <span :class="item.error_logs_24h > 0 ? 'text-red-600 dark:text-red-400 font-medium' : ''" 
                                          x-text="formatNumber(item.error_logs_24h) + ' err'"></span>
                                    <span>•</span>
                                    <span :class="item.error_rate > 10 ? 'text-red-600 dark:text-red-400 font-medium' : ''" 
                                          x-text="item.error_rate + '%'"></span>
                                </div>
                                <div class="flex flex-wrap gap-1.5" x-show="item.tags && item.tags.length > 0">
                                    <template x-for="tag in item.tags" :key="tag">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300" x-text="tag"></span>
                                    </template>
                                </div>
                            </div>
                            <div class="text-right space-y-1 flex-shrink-0">
                                <div class="text-sm font-medium"
                                    :class="item.project.is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'"
                                    x-text="item.project.is_active ? 'Active' : 'Inactive'"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="lastUpdatedLabel(item)"></div>
                            </div>
                        </div>

                        <!-- Dynamic Metric Slots -->
                        <template x-if="metricSlots(item).length > 0">
                            <div class="space-y-1.5">
                                <template x-for="metric in metricSlots(item)" :key="metric.key">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400" x-text="metric.label"></span>
                                        <span class="text-gray-900 dark:text-white font-medium" x-text="metric.display"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="metricSlots(item).length === 0">
                            <div class="text-sm text-gray-500 dark:text-gray-400 italic text-center py-2">No metrics selected</div>
                        </template>

                        <!-- Extra Metrics Indicator -->
                        <div class="flex items-center justify-between text-sm text-indigo-600 dark:text-indigo-400 font-medium" x-show="extraMetricCount(item) > 0">
                            <span>+ <span x-text="extraMetricCount(item)"></span> more metrics</span>
                            <span>→</span>
                        </div>
                    </div>
                </a>
            </template>
            
            <div x-show="filteredProjects.length === 0" class="col-span-full text-center py-12">
                <p class="text-gray-500 dark:text-gray-400" x-text="searchQuery ? 'No projects match your search.' : 'No active projects found.'"></p>
            </div>
        </div>

        <!-- Table View -->
        <div x-show="view === 'table'" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th @click="sortBy('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                Project
                                <span x-show="sortCol === 'name'" x-text="sortAsc ? '↑' : '↓'"></span>
                            </th>
                            <th @click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                Status
                                <span x-show="sortCol === 'status'" x-text="sortAsc ? '↑' : '↓'"></span>
                            </th>
                            <th @click="sortBy('logs')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                Logs (24h)
                                <span x-show="sortCol === 'logs'" x-text="sortAsc ? '↑' : '↓'"></span>
                            </th>
                            <th @click="sortBy('errors')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                Errors
                                <span x-show="sortCol === 'errors'" x-text="sortAsc ? '↑' : '↓'"></span>
                            </th>
                            <th @click="sortBy('rate')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                Error Rate
                                <span x-show="sortCol === 'rate'" x-text="sortAsc ? '↑' : '↓'"></span>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Server Stats
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="item in sortedFilteredProjects" :key="item.project.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer" @click="window.location.href = item.dashboard_url">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="item.project.name"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="item.project.is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'"
                                        x-text="item.project.is_active ? 'Active' : 'Inactive'">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="formatNumber(item.total_logs_24h)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm" 
                                    :class="item.error_logs_24h > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400'"
                                    x-text="formatNumber(item.error_logs_24h)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"
                                    :class="item.error_rate > 10 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-500 dark:text-gray-400'"
                                    x-text="item.error_rate + '%'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <template x-if="item.project.server_stats">
                                        <div class="flex space-x-4">
                                            <span x-show="item.project.server_stats.system && item.project.server_stats.system.server_memory" 
                                                  title="Memory Usage"
                                                  :class="item.project.server_stats.system.server_memory.percent_used > 80 ? 'text-red-600 dark:text-red-400' : ''">
                                                Mem: <span x-text="Math.round(item.project.server_stats.system.server_memory.percent_used) + '%'"></span>
                                            </span>
                                            <span x-show="item.project.server_stats.system && item.project.server_stats.system.disk_space" 
                                                  title="Disk Usage"
                                                  :class="item.project.server_stats.system.disk_space.percent_used > 80 ? 'text-red-600 dark:text-red-400' : ''">
                                                Disk: <span x-text="Math.round(item.project.server_stats.system.disk_space.percent_used) + '%'"></span>
                                            </span>
                                        </div>
                                    </template>
                                    <span x-show="!item.project.server_stats" class="italic text-xs">No stats</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('autoRefresh', () => ({
        enabled: localStorage.getItem('dashboard_auto_refresh') !== 'false', // Default to true
        interval: 30, // seconds
        countdown: 30,
        timer: null,
        countdownTimer: null,
        
        init() {
            this.$watch('enabled', value => {
                localStorage.setItem('dashboard_auto_refresh', value);
                if (value) {
                    this.start();
                } else {
                    this.stop();
                }
            });
            
            if (this.enabled) {
                this.start();
            }
        },
        
        start() {
            this.countdown = this.interval;
            this.timer = setInterval(() => {
                window.location.reload();
            }, this.interval * 1000);
            
            this.countdownTimer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.countdown = this.interval;
                }
            }, 1000);
        },
        
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
                this.countdownTimer = null;
            }
        }
    }));
    
    Alpine.data('projectList', (projects, initialHiddenMetrics) => ({
        projects: projects,
        view: localStorage.getItem('dashboard_view') || 'grid',
        sortCol: 'name',
        sortAsc: true,
        searchQuery: '',
        hiddenMetrics: initialHiddenMetrics || [],
        
        init() {
            this.$watch('view', value => localStorage.setItem('dashboard_view', value));
        },
        
        toggleMetric(metricName) {
            const index = this.hiddenMetrics.indexOf(metricName);
            if (index > -1) {
                // Remove from hidden (show it)
                this.hiddenMetrics.splice(index, 1);
            } else {
                // Add to hidden (hide it)
                this.hiddenMetrics.push(metricName);
            }
            this.savePreferences(this.hiddenMetrics);
        },
        
        async savePreferences(hiddenMetrics) {
            try {
                await fetch('{{ route("dashboard.preferences.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ hidden_metrics: hiddenMetrics })
                });
            } catch (error) {
                console.error('Failed to save preferences:', error);
            }
        },
        
        resetMetrics() {
            this.hiddenMetrics = [];
            this.savePreferences(this.hiddenMetrics);
        },

        get filteredProjects() {
            if (!this.searchQuery) {
                return this.projects;
            }
            
            const query = this.searchQuery.toLowerCase();
            return this.projects.filter(item => {
                // Search in project name
                const matchesName = item.project.name.toLowerCase().includes(query);
                
                // Search in tags
                const matchesTags = item.tags && item.tags.some(tag => 
                    tag.toLowerCase().includes(query)
                );
                
                return matchesName || matchesTags;
            });
        },

        get sortedFilteredProjects() {
            return [...this.filteredProjects].sort((a, b) => {
                let valA, valB;
                
                switch(this.sortCol) {
                    case 'name':
                        valA = (a.project.name || '').toLowerCase();
                        valB = (b.project.name || '').toLowerCase();
                        break;
                    case 'status':
                        valA = a.project.is_active ? 1 : 0;
                        valB = b.project.is_active ? 1 : 0;
                        break;
                    case 'logs':
                        valA = a.total_logs_24h;
                        valB = b.total_logs_24h;
                        break;
                    case 'errors':
                        valA = a.error_logs_24h;
                        valB = b.error_logs_24h;
                        break;
                    case 'rate':
                        valA = parseFloat(a.error_rate);
                        valB = parseFloat(b.error_rate);
                        break;
                    default:
                        valA = a.project.name;
                        valB = b.project.name;
                }
                
                if (valA < valB) return this.sortAsc ? -1 : 1;
                if (valA > valB) return this.sortAsc ? 1 : -1;
                return 0;
            });
        },
        
        sortBy(col) {
            if (this.sortCol === col) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortCol = col;
                this.sortAsc = true;
            }
        },
        
        formatNumber(num) {
            return new Intl.NumberFormat().format(num);
        },
        
        formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        },
        
        timeAgo(dateString) {
            if (!dateString) return 'Offline';
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + 'y ago';
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + 'mo ago';
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + 'd ago';
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + 'h ago';
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + 'm ago';
            return 'Just now';
        },
        
        // Check if project data is stale (>5 minutes old)
        isStale(item) {
            if (!item.project.last_server_stats_at) return true;
            const last = new Date(item.project.last_server_stats_at);
            const diffSeconds = (Date.now() - last.getTime()) / 1000;
            return diffSeconds > 300; // 5 minutes
        },
        
        lastUpdatedLabel(item) {
            if (!item.project.last_server_stats_at) return 'Offline';
            const label = this.timeAgo(item.project.last_server_stats_at);
            return this.isStale(item) ? label + ' (stale)' : label;
        },
        
        // Health strip color based on critical metrics
        healthStripClass(item) {
            const stats = item.project.server_stats || {};
            const sys = stats.system || {};
            const queue = stats.queue || {};
            const dbStatus = stats.database && stats.database.status;
            
            // Critical: DB disconnected
            if (dbStatus && dbStatus !== 'connected') return 'bg-red-500';
            
            // Warning: High CPU or large queue
            if ((sys.cpu_usage ?? 0) >= 90 || (queue.size ?? 0) > 100) return 'bg-amber-500';
            
            // Stale data
            if (this.isStale(item)) return 'bg-gray-300 dark:bg-gray-600';
            
            // Healthy
            return 'bg-green-500';
        },
        
        cardOpacityClass(item) {
            return '';
        },
        
        // Safely clamp percentage values
        clampPercent(value) {
            const safe = Number(value) || 0;
            return Math.min(100, Math.max(0, Math.round(safe * 10) / 10));
        },
        
        // Generate metric slots based on selected metrics
        visibleMetrics(item) {
            const metrics = [];
            const stats = item.project.server_stats || {};
            const sys = stats.system || {};
            const queue = stats.queue || {};
            const hidden = this.hiddenMetrics || [];
            
            const add = (key, metric) => {
                if (hidden.includes(key)) return;
                if (metric.value === undefined || metric.value === null || metric.value === '') return;
                metrics.push({ key, ...metric });
            };
            
            const barColor = (percent) => {
                if (percent >= 90) return 'bg-red-500';
                if (percent >= 75) return 'bg-yellow-500';
                return 'bg-green-500';
            };

            // Gauge metrics
            if (sys.cpu_usage !== undefined) {
                const percent = this.clampPercent(sys.cpu_usage);
                add('cpu', { 
                    type: 'gauge', 
                    label: 'CPU', 
                    percent, 
                    barColor: barColor(percent), 
                    display: percent + '%',
                    value: percent
                });
            }

            if (sys.server_memory && sys.server_memory.percent_used !== undefined) {
                const percent = this.clampPercent(sys.server_memory.percent_used);
                add('memory', { 
                    type: 'gauge', 
                    label: 'Memory', 
                    percent, 
                    barColor: barColor(percent), 
                    display: percent + '%',
                    value: percent
                });
            }

            if (sys.disk_space && sys.disk_space.percent_used !== undefined) {
                const percent = this.clampPercent(sys.disk_space.percent_used);
                add('disk', { 
                    type: 'gauge', 
                    label: 'Disk', 
                    percent, 
                    barColor: barColor(percent), 
                    display: percent + '%',
                    value: percent
                });
            }

            // Counter metrics
            if (queue.size !== undefined) {
                add('queue', { 
                    type: 'counter', 
                    label: 'Jobs', 
                    value: queue.size, 
                    display: this.formatNumber(queue.size) 
                });
            }

            const composerAudit = sys.composer_audit;
            const composerOut = sys.composer_outdated;
            if (composerAudit !== undefined || composerOut !== undefined) {
                const parts = [];
                if (composerAudit !== undefined) parts.push((composerAudit || 0) + ' vuln');
                if (composerOut !== undefined) parts.push((composerOut || 0) + ' out');
                const display = parts.join(' / ') || '0';
                add('composer', { 
                    type: 'counter', 
                    label: 'Composer', 
                    value: composerAudit ?? composerOut ?? 0, 
                    display 
                });
            }

            const npmAudit = sys.npm_audit;
            const npmOut = sys.npm_outdated;
            if (npmAudit !== undefined || npmOut !== undefined) {
                const parts = [];
                if (npmAudit !== undefined) parts.push((npmAudit || 0) + ' vuln');
                if (npmOut !== undefined) parts.push((npmOut || 0) + ' out');
                const display = parts.join(' / ') || '0';
                add('npm', { 
                    type: 'counter', 
                    label: 'npm', 
                    value: npmAudit ?? npmOut ?? 0, 
                    display 
                });
            }

            // Text/Value metrics
            if (sys.php_version) add('php_version', { type: 'text', label: 'PHP', value: sys.php_version, display: sys.php_version });
            if (sys.laravel_version) add('laravel_version', { type: 'text', label: 'Laravel', value: sys.laravel_version, display: sys.laravel_version });
            
            const shipper = item.log_shipper_version || item.project.log_shipper_version || stats.log_shipper_version;
            if (shipper) add('log_shipper_version', { type: 'text', label: 'Log Shipper', value: shipper, display: shipper });
            
            const debug = item.app_debug ?? item.project.app_debug ?? stats.app_debug;
            if (debug !== undefined && debug !== null) {
                add('debug_mode', { type: 'text', label: 'Debug', value: debug, display: debug ? 'Enabled' : 'Disabled' });
            }
            
            if (item.project.last_server_stats_at) {
                add('updated', { type: 'text', label: 'Updated', value: item.project.last_server_stats_at, display: this.timeAgo(item.project.last_server_stats_at) });
            }
            
            return metrics;
        },
        
        // Determine layout based on metric count
        layoutForMetrics(item) {
            const count = this.visibleMetrics(item).length;
            if (count <= 4) {
                return { limit: 4, gridClass: 'grid-cols-2 grid-rows-2' };
            }
            return { limit: 6, gridClass: 'grid-cols-3 grid-rows-2' };
        },
        
        metricSlots(item) {
            const layout = this.layoutForMetrics(item);
            return this.visibleMetrics(item).slice(0, layout.limit);
        },
        
        metricsGridClass(item) {
            return this.layoutForMetrics(item).gridClass;
        },
        
        extraMetricCount(item) {
            const layout = this.layoutForMetrics(item);
            const total = this.visibleMetrics(item).length;
            return total > layout.limit ? total - layout.limit : 0;
        }
    }));
});
</script>
@endsection
