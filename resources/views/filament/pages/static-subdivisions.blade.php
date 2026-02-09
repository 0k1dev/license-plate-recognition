<x-filament-panels::page>
    <div
        x-data="{
            allRows: [],
            rows: [],
            search: '',
            isLoading: true,
            sortCol: 'updated_at',
            sortAsc: false,
            limit: 100,
            
            async init() {
                try {
                    let res = await fetch('/data/subdivisions.json?v=' + new Date().getTime());
                    this.allRows = await res.json();
                    this.filter();
                    this.isLoading = false;
                } catch (e) {
                    console.error(e);
                    this.isLoading = false;
                }
                
                this.$watch('search', () => this.filter());
            },
            
            filter() {
                let q = this.search.toLowerCase().trim();
                let result = this.allRows;
                
                if (q) {
                    result = result.filter(r => 
                        (r.name && r.name.toLowerCase().includes(q)) || 
                        (r.parent_name && r.parent_name.toLowerCase().includes(q)) ||
                        (r.division_type && r.division_type.toLowerCase().includes(q))
                    );
                }
                
                // Sort
                result.sort((a, b) => {
                    let valA = a[this.sortCol] || '';
                    let valB = b[this.sortCol] || '';
                    
                    if (typeof valA === 'string') valA = valA.toLowerCase();
                    if (typeof valB === 'string') valB = valB.toLowerCase();

                    if (valA < valB) return this.sortAsc ? -1 : 1;
                    if (valA > valB) return this.sortAsc ? 1 : -1;
                    return 0;
                });

                this.rows = result.slice(0, this.limit); 
            },
            
            sortBy(col) {
                if (this.sortCol === col) this.sortAsc = !this.sortAsc;
                else {
                    this.sortCol = col;
                    this.sortAsc = true;
                }
                this.filter();
            }
        }"
        x-on:data-refreshed.window="init()"
        class="fi-ta-ctn ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
        <!-- Header Controls -->
        <div class="fi-ta-header p-4 border-b border-gray-200 dark:border-white/10 flex flex-wrap gap-4 items-center justify-between">
            <div class="flex-1 min-w-[200px] max-w-sm">
                <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 transition duration-75 bg-white dark:bg-white/5 ring-gray-950/10 focus-within:ring-2 focus-within:ring-primary-600 dark:ring-white/20 dark:focus-within:ring-primary-500 overflow-hidden">
                    <div class="px-3 py-2 text-gray-400">
                        <x-heroicon-m-magnifying-glass class="w-5 h-5" />
                    </div>
                    <input
                        type="text"
                        x-model.debounce.300ms="search"
                        placeholder="Tìm kiếm..."
                        class="w-full border-0 bg-transparent py-1.5 px-0 text-gray-950 focus:ring-0 sm:text-sm sm:leading-6 dark:text-white placeholder-gray-400">
                </div>
            </div>

            <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                <span x-show="isLoading" class="flex items-center gap-2">
                    <x-heroicon-m-arrow-path class="w-4 h-4 animate-spin" /> Đang tải...
                </span>
                <span x-show="!isLoading">
                    Hiển thị <span class="font-semibold text-primary-600" x-text="rows.length"></span> / <span x-text="allRows.length"></span>
                </span>
            </div>
        </div>

        <!-- Table Content -->
        <div class="fi-ta-content overflow-x-auto">
            <table class="fi-ta-table w-full text-start divide-y divide-gray-200 dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th @click="sortBy('parent_name')" class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 text-start text-sm font-semibold text-gray-950 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 select-none">
                            Tỉnh/TP <span x-show="sortCol === 'parent_name'" x-text="sortAsc ? '▲' : '▼'"></span>
                        </th>
                        <th @click="sortBy('name')" class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 select-none">
                            Tên <span x-show="sortCol === 'name'" x-text="sortAsc ? '▲' : '▼'"></span>
                        </th>
                        <th @click="sortBy('level')" class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 select-none">
                            Cấp
                        </th>
                        <th @click="sortBy('division_type')" class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 select-none">
                            Loại
                        </th>
                        <th @click="sortBy('updated_at')" class="fi-ta-header-cell px-3 py-3.5 sm:last-of-type:pe-6 text-start text-sm font-semibold text-gray-950 dark:text-white cursor-pointer hover:bg-gray-100 dark:hover:bg-white/5 select-none">
                            Cập nhật <span x-show="sortCol === 'updated_at'" x-text="sortAsc ? '▲' : '▼'"></span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    <template x-for="row in rows" :key="row.id">
                        <tr class="fi-ta-row hover:bg-gray-50 dark:hover:bg-white/5 transition duration-75">

                            <!-- Col 1 -->
                            <td class="fi-ta-cell p-0 first-of-type:ps-1 sm:first-of-type:ps-3">
                                <div class="fi-ta-text grid gap-y-1 px-3 py-4">
                                    <div class="text-sm font-medium text-gray-950 dark:text-white" x-text="row.parent_name"></div>
                                </div>
                            </td>

                            <!-- Col 2 -->
                            <td class="fi-ta-cell p-0">
                                <div class="fi-ta-text grid gap-y-1 px-3 py-4">
                                    <div class="text-sm font-bold text-primary-600 dark:text-primary-400" x-text="row.name"></div>
                                </div>
                            </td>

                            <!-- Col 3 -->
                            <td class="fi-ta-cell p-0">
                                <div class="fi-ta-text grid gap-y-1 px-3 py-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="row.level"></div>
                                </div>
                            </td>

                            <!-- Col 4 -->
                            <td class="fi-ta-cell p-0">
                                <div class="fi-ta-text grid gap-y-1 px-3 py-4">
                                    <div class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20" x-text="row.division_type"></div>
                                </div>
                            </td>

                            <!-- Col 5 -->
                            <td class="fi-ta-cell p-0 last-of-type:pe-1 sm:last-of-type:pe-3">
                                <div class="fi-ta-text grid gap-y-1 px-3 py-4">
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="row.updated_at"></div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>