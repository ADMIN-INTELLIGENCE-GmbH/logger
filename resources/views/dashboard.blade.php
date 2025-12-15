@extends('layouts.logger')

@section('title', 'Dashboard')

@section('content')
<div>
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Overview of all projects and server status</p>
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
    <div x-data='projectList(@json($projects, JSON_HEX_APOS))'>
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">All Projects</h2>
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

        <div class="mb-6 flex items-center justify-between">
            <div></div>
            
            <!-- View Toggle -->
            <div class="flex items-center bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <button @click="view = 'grid'" :class="{ 'bg-white dark:bg-gray-600 shadow-sm': view === 'grid', 'text-gray-500 dark:text-gray-400': view !== 'grid' }" class="p-2 rounded-md transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                </button>
                <button @click="view = 'table'" :class="{ 'bg-white dark:bg-gray-600 shadow-sm': view === 'table', 'text-gray-500 dark:text-gray-400': view !== 'table' }" class="p-2 rounded-md transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Grid View -->
        <div x-show="view === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="item in sortedFilteredProjects" :key="item.project.id">
                <a :href="item.dashboard_url" class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <!-- Project Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="item.project.name"></h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="item.project.is_active ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300'"
                                x-text="item.project.is_active ? 'Active' : 'Inactive'">
                            </span>
                        </div>

                        <!-- Tags -->
                        <div x-show="item.tags && item.tags.length > 0" class="mb-3 flex flex-wrap gap-2">
                            <template x-for="tag in item.tags" :key="tag">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200" x-text="tag"></span>
                            </template>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Logs (24h)</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white" x-text="formatNumber(item.total_logs_24h)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Errors</p>
                                <p class="text-lg font-semibold" 
                                   :class="item.error_logs_24h > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                   x-text="formatNumber(item.error_logs_24h)"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Rate</p>
                                <p class="text-lg font-semibold"
                                   :class="item.error_rate > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                   x-text="item.error_rate + '%'"></p>
                            </div>
                        </div>

                        <!-- Server Stats -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2" x-show="item.project.server_stats">
                            <template x-if="item.project.server_stats && item.project.server_stats.system">
                                <div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.cpu_usage !== undefined">
                                        <span class="text-gray-500 dark:text-gray-400">CPU Usage:</span>
                                        <span class="font-medium" 
                                              :class="item.project.server_stats.system.cpu_usage > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                              x-text="Math.round(item.project.server_stats.system.cpu_usage * 10) / 10 + '%'"></span>
                                    </div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.memory_usage">
                                        <span class="text-gray-500 dark:text-gray-400">Memory (App):</span>
                                        <span class="font-medium text-gray-900 dark:text-white" x-text="formatBytes(item.project.server_stats.system.memory_usage)"></span>
                                    </div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.server_memory">
                                        <span class="text-gray-500 dark:text-gray-400">Memory (Server):</span>
                                        <span class="font-medium" 
                                              :class="item.project.server_stats.system.server_memory.percent_used > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                              x-text="Math.round(item.project.server_stats.system.server_memory.percent_used) + '%'"></span>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="item.project.server_stats && item.project.server_stats.system && item.project.server_stats.system.disk_space">
                                <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.disk_space.percent_used">
                                    <span class="text-gray-500 dark:text-gray-400">Disk:</span>
                                    <span class="font-medium"
                                          :class="item.project.server_stats.system.disk_space.percent_used > 80 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'"
                                          x-text="Math.round(item.project.server_stats.system.disk_space.percent_used) + '%'"></span>
                                </div>
                            </template>

                            <template x-if="item.project.server_stats && item.project.server_stats.queue">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Queue:</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="(item.project.server_stats.queue.size || 0) + ' jobs'"></span>
                                </div>
                            </template>

                            <!-- Security & Updates -->
                            <template x-if="item.project.server_stats && item.project.server_stats.system">
                                <div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.composer_audit !== undefined">
                                        <span class="text-gray-500 dark:text-gray-400">Composer Vulnerabilities:</span>
                                        <span class="font-medium" 
                                              :class="item.project.server_stats.system.composer_audit > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                              x-text="item.project.server_stats.system.composer_audit"></span>
                                    </div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.npm_audit !== undefined">
                                        <span class="text-gray-500 dark:text-gray-400">npm Vulnerabilities:</span>
                                        <span class="font-medium" 
                                              :class="item.project.server_stats.system.npm_audit > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                              x-text="item.project.server_stats.system.npm_audit"></span>
                                    </div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.composer_outdated !== undefined && item.project.server_stats.system.composer_outdated > 0">
                                        <span class="text-gray-500 dark:text-gray-400">Composer Outdated:</span>
                                        <span class="font-medium text-yellow-600 dark:text-yellow-400" x-text="item.project.server_stats.system.composer_outdated"></span>
                                    </div>
                                    <div class="flex justify-between text-xs" x-show="item.project.server_stats.system.npm_outdated !== undefined && item.project.server_stats.system.npm_outdated > 0">
                                        <span class="text-gray-500 dark:text-gray-400">npm Outdated:</span>
                                        <span class="font-medium text-yellow-600 dark:text-yellow-400" x-text="item.project.server_stats.system.npm_outdated"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="item.project.last_server_stats_at">
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500 dark:text-gray-400">Updated:</span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="timeAgo(item.project.last_server_stats_at)"></span>
                                </div>
                            </template>
                        </div>
                        
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4" x-show="!item.project.server_stats">
                            <p class="text-xs text-gray-500 dark:text-gray-400 italic">No server stats available</p>
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
    Alpine.data('projectList', (projects) => ({
        projects: projects,
        view: localStorage.getItem('dashboard_view') || 'grid',
        sortCol: 'name',
        sortAsc: true,
        searchQuery: '',
        
        init() {
            this.$watch('view', value => localStorage.setItem('dashboard_view', value));
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
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + "y ago";
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + "mo ago";
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + "d ago";
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + "h ago";
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + "m ago";
            return "Just now";
        }
    }));
});
</script>
@endsection
