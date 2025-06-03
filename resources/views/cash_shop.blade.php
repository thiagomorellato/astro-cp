@extends('layouts.app')

@section('content')
<div x-data="cashShop()" x-init="init()" class="max-w-4xl mx-auto p-4 sm:p-6 text-white">

    <div class="bg-white/10 backdrop-blur-md p-4 sm:p-6 rounded-xl shadow-lg border border-white/20 mb-8">
        <h2 class="text-lg font-semibold mb-4 text-center text-yellow-400">Cash Shop Manager</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('cash.shop.import') }}">
                @csrf
                <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-3 rounded-lg font-semibold transition text-sm sm:text-base">
                    Update Items DB (CSV)
                </button>
            </form>

            <form method="GET" action="{{ route('cash.shop.exportYaml') }}">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-3 rounded-lg font-semibold transition text-sm sm:text-base">
                    Export YAML to Server
                </button>
            </form>

            <button
                @click="addItemModalOpen = true"
                class="w-full bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded-lg text-white font-semibold transition text-sm sm:text-base"
            >
                + Add Items to Tab
            </button>
            
            <button
                @click="openClearTabModal()"
                x-show="!loading && items.length > 0"
                class="w-full bg-red-600 hover:bg-red-700 px-3 py-2 rounded-lg text-white font-semibold transition text-sm sm:text-base"
            >
                Clear Tab: <span x-text="activeTab"></span>
            </button>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap gap-2 mb-4">
            <template x-for="tabName in tabs" :key="tabName">
                <button
                    @click="changeTab(tabName)"
                    :class="{
                        'bg-yellow-500 text-black': tabName === activeTab,
                        'bg-white/20 hover:bg-white/30 text-white': tabName !== activeTab
                    }"
                    class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg font-semibold transition text-xs sm:text-sm"
                    x-text="tabName"
                ></button>
            </template>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <template x-if="loading">
                <div class="col-span-full text-center text-gray-300 py-8">Loading items...</div>
            </template>
            <template x-if="!loading && items.length === 0">
                <div class="col-span-full text-center text-gray-300 py-8">No items found in this tab.</div>
            </template>

            <template x-for="item in items" :key="item.id + '-' + item.tab"> <div class="bg-white/10 p-3 rounded-lg text-center border border-white/10 relative group flex flex-col justify-between">
                    <div>
                        <div class="font-bold mb-0.5 text-sm truncate" x-text="item.name || item.aegisname" :title="item.name || item.aegisname"></div>
                        <div class="text-xs text-gray-400 mb-0.5">Aegis: <span x-text="item.aegisname"></span></div>
                        <div class="text-xs text-gray-300">Item ID: <span x-text="item.id"></span></div> </div>
                    <div class="mt-1">
                        <div class="text-sm text-yellow-400">Price: <span x-text="item.price"></span></div>
                    </div>
                    <button
                        @click="openDeleteItemModal(item)"
                        title="Delete Item"
                        class="absolute top-1 right-1 text-red-400 hover:text-red-600 p-1 rounded-full opacity-60 group-hover:opacity-100 transition-opacity"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <div class="flex justify-center items-center gap-4 mt-6" x-show="!loading && totalPages > 1">
            <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed text-sm font-semibold"
            >Prev</button>
            <span class="text-sm">Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>
            <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed text-sm font-semibold"
            >Next</button>
        </div>
    </div>

    <div
        x-show="addItemModalOpen"
        x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
        @click.self="addItemModalOpen = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <h3 class="text-lg font-bold text-blue-500 mb-4">Add Items to Tab</h3>
            <form method="POST" action="{{ route('cash.shop.addItems') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="add_item_tab" class="text-sm text-gray-300 font-semibold block mb-1">Select Tab:</label>
                        <select name="tab" id="add_item_tab" x-model="addItemForm.tab" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white focus:ring-blue-500 focus:border-blue-500">
                            <template x-for="tabOption in tabs" :key="tabOption">
                                <option :value="tabOption" x-text="tabOption"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="bulk_items_input" class="text-sm text-gray-300 font-semibold block mb-1">Items (ID:PRICE,ID:PRICE...)</label>
                        <textarea name="bulk_items" id="bulk_items_input" x-model="addItemForm.bulk_items" rows="3" class="w-full bg-gray-800 border border-gray-700 rounded-lg p-2 text-white placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g., 501:1000,1201:2500"></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="addItemModalOpen = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">Cancel</button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Add Items</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div
        x-show="deleteItemModalOpen" x-transition
        class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
        @click.self="closeDeleteItemModal()" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <h3 class="text-lg font-bold text-red-500 mb-2">Confirm Deletion</h3>
            <p class="text-sm text-gray-300 mb-4">
                Are you sure you want to delete item
                <strong x-text="itemToDelete?.name || itemToDelete?.aegisname" class="text-yellow-400"></strong>
                (ID: <span x-text="itemToDelete?.id" class="text-yellow-400"></span>) from tab <strong x-text="activeTab" class="text-yellow-400"></strong>?
            </p>
            <p class="text-xs text-gray-500">This action cannot be undone.</p>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="closeDeleteItemModal()" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">Cancel</button>
                <button type="button" @click="confirmDeleteItem()" class="bg-red-600 hover:bg-red-700 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Delete Item</button>
            </div>
        </div>
    </div>

    <div
        x-show="clearTabModalOpen" x-transition
        class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
        @click.self="clearTabModalOpen = false" x-cloak
    >
        <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl" @click.stop>
            <h3 class="text-lg font-bold text-red-500 mb-2">Confirm Clear Tab</h3>
            <p class="text-sm text-gray-300 mb-4">
                Are you sure you want to delete all items from tab
                <strong x-text="activeTab" class="text-yellow-400"></strong>?
            </p>
            <p class="text-xs text-gray-500">This action will remove all items listed under this tab and cannot be undone.</p>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="clearTabModalOpen = false" class="text-sm text-gray-400 hover:text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">Cancel</button>
                <button type="button" @click="confirmClearTab()" class="bg-red-600 hover:bg-red-700 px-4 py-2 text-sm rounded-lg text-white font-semibold transition">Clear Tab Items</button>
            </div>
        </div>
    </div>
    
    <div x-data="{ show: false, message: '', type: 'info' }"
         @alpine-notification.window="show = true; message = $event.detail.message; type = $event.detail.type; setTimeout(() => show = false, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform translate-y-2"
         :class="{ 'bg-green-600': type === 'success', 'bg-red-600': type === 'error', 'bg-yellow-500 text-black': type === 'warning', 'bg-blue-500': type === 'info' }"
         class="fixed bottom-5 right-5 text-white p-3 rounded-lg shadow-lg text-sm font-medium"
         style="z-index: 1000;"
         x-cloak
    >
        <span x-text="message"></span>
    </div>

</div>

<script>
// Certifique-se que esta meta tag está no seu <head> em layouts.app.blade.php:
// <meta name="csrf-token" content="{{ csrf_token() }}">
function cashShop() {
    return {
        tabs: @json($tabs),
        activeTab: @json($tabs[0] ?? 'New'),
        items: [],
        currentPage: 1,
        totalPages: 1,
        loading: true,
        
        addItemModalOpen: false,
        addItemForm: {
            tab: @json($tabs[0] ?? 'New'),
            bulk_items: '',
        },

        deleteItemModalOpen: false,
        itemToDelete: null,

        clearTabModalOpen: false,
        
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',

        init() {
            // Garante que activeTab e addItemForm.tab sejam inicializados corretamente
            if (this.tabs.length > 0) {
                this.activeTab = this.tabs.includes(this.activeTab) ? this.activeTab : this.tabs[0];
                this.addItemForm.tab = this.activeTab;
            } else {
                this.showNotification('Cash Shop tabs are not configured.', 'warning');
                this.loading = false;
                return;
            }
            
            this.fetchItems();

            // Mostrar mensagens flash do Laravel via sistema de notificação Alpine
            const laravelSuccess = @json(session('success'));
            const laravelError = @json(session('error'));
            const laravelWarning = @json(session('warning'));

            if (laravelSuccess) this.showNotification(laravelSuccess, 'success');
            if (laravelError) this.showNotification(laravelError, 'error');
            if (laravelWarning) this.showNotification(laravelWarning, 'warning');
        },
        
        showNotification(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('alpine-notification', { detail: { message, type } }));
        },

        fetchItems() {
            this.loading = true;
            if (!this.activeTab) {
                this.loading = false;
                this.items = [];
                this.totalPages = 1;
                this.currentPage = 1;
                this.showNotification('No active tab to fetch items from.', 'warning');
                return;
            }
            fetch(`/cash-shop/items?tab=${encodeURIComponent(this.activeTab)}&page=${this.currentPage}`)
                .then(res => {
                    if (!res.ok) return res.json().then(err => { throw new Error(err.message || `HTTP error ${res.status}`) });
                    return res.json();
                })
                .then(data => {
                    if (data.error) throw new Error(data.message || data.error);
                    this.items = data.items ?? [];
                    this.totalPages = data.totalPages ?? 1;
                    this.currentPage = data.currentPage ?? 1;
                    if (this.currentPage > this.totalPages && this.totalPages > 0) { // Se a página atual exceder o total
                        this.currentPage = this.totalPages;
                        this.fetchItems(); // Buscar a última página válida
                    } else if (this.items.length === 0 && this.currentPage > 1) { // Se a página atual ficou vazia e não é a primeira
                        this.currentPage--;
                        this.fetchItems();
                    }
                })
                .catch(error => {
                    this.items = [];
                    this.totalPages = 1;
                    this.currentPage = 1;
                    console.error('Failed to load items:', error);
                    this.showNotification(`Error loading items: ${error.message}`, 'error');
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        changeTab(tabName) {
            if (this.activeTab !== tabName) {
                this.activeTab = tabName;
                this.currentPage = 1; 
                this.addItemForm.tab = tabName; 
                this.fetchItems();
            }
        },

        prevPage() { if (this.currentPage > 1) { this.currentPage--; this.fetchItems(); }},
        nextPage() { if (this.currentPage < this.totalPages) { this.currentPage++; this.fetchItems(); }},

        openDeleteItemModal(item) {
            this.itemToDelete = item; // item.id é o item_id
            this.deleteItemModalOpen = true;
        },
        closeDeleteItemModal() {
            this.itemToDelete = null;
            this.deleteItemModalOpen = false;
        },
        confirmDeleteItem() {
            if (!this.itemToDelete || !this.itemToDelete.id || !this.activeTab) return;

            // A rota é /cash-shop/item/{itemId}/tab/{tabName}
            // itemToDelete.id é o itemId
            // this.activeTab é o tabName
            fetch(`/cash-shop/item/${this.itemToDelete.id}/tab/${encodeURIComponent(this.activeTab)}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json().then(data => ({ ok: res.ok, status: res.status, data })))
            .then(({ ok, status, data }) => {
                if (ok && data.success) {
                    this.showNotification(data.message || 'Item deleted successfully.', 'success');
                    this.fetchItems(); 
                } else {
                    throw new Error(data.message || `Failed to delete item (status ${status})`);
                }
            })
            .catch(error => {
                console.error('Error deleting item:', error);
                this.showNotification(error.message || 'An error occurred while deleting the item.', 'error');
            })
            .finally(() => {
                this.closeDeleteItemModal();
            });
        },

        openClearTabModal() {
            if (this.items.length === 0 && !this.loading) {
                this.showNotification(`Tab '${this.activeTab}' is already empty.`, 'info');
                return;
            }
            this.clearTabModalOpen = true;
        },
        confirmClearTab() {
            if (!this.activeTab) return;
            fetch(`/cash-shop/tab/${encodeURIComponent(this.activeTab)}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json().then(data => ({ ok: res.ok, status: res.status, data })))
            .then(({ ok, status, data }) => {
                if (ok && data.success) {
                    this.showNotification(data.message || `Tab '${this.activeTab}' cleared.`, 'success');
                    this.fetchItems(); 
                } else {
                    throw new Error(data.message || `Failed to clear tab (status ${status})`);
                }
            })
            .catch(error => {
                console.error('Error clearing tab:', error);
                this.showNotification(error.message || 'An error occurred while clearing the tab.', 'error');
            })
            .finally(() => {
                this.clearTabModalOpen = false;
            });
        }
    }
}
</script>
@endsection