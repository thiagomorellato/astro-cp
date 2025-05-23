@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">

    <!-- Botões de Controle -->
    <div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl shadow-lg border border-white/20 mb-8">
        <h2 class="text-lg font-semibold mb-4 text-center text-yellow-400">Cash Shop Manager</h2>

        <form method="POST" action="{{ route('cash.shop.import') }}">
            @csrf
            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg font-semibold transition">
                Update DB
            </button>
        </form>

        <form method="GET" action="{{ route('cash.shop.exportYaml') }}">
            <button type="submit" class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-semibold transition">
                Export YAML
            </button>
        </form>

        <button 
            @click="addItemModal = true"
            class="mt-4 w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg text-white font-semibold text-sm transition"
        >
            + Add Item
        </button>
    </div>

    <!-- Loja por Abas -->
    <div x-data="cashShop()" x-init="init()" class="text-white">
        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 mb-4">
            <template x-for="tab in tabs" :key="tab">
                <button
                    @click="changeTab(tab)"
                    :class="{
                        'bg-yellow-500 text-black': tab === activeTab,
                        'bg-white/20 hover:bg-white/30': tab !== activeTab
                    }"
                    class="px-4 py-2 rounded-lg font-semibold transition"
                    x-text="tab"
                ></button>
            </template>
        </div>

        <!-- Grade de Itens -->
        <div class="grid grid-cols-4 gap-4">
            <template x-if="items.length === 0">
                <div class="col-span-4 text-center text-gray-300">No items found.</div>
            </template>

            <template x-for="item in items" :key="item.id">
                <div class="bg-white/10 p-4 rounded-lg text-center border border-white/10">
                    <div class="font-bold mb-1" x-text="item.aegisname"></div>
                    <div class="text-sm">ID: <span x-text="item.id"></span></div>
                    <div class="text-sm">Price: <span x-text="item.price"></span></div>
                </div>
            </template>
        </div>

        <!-- Paginação -->
        <div class="flex justify-center items-center gap-4 mt-6" x-show="totalPages > 1">
            <button
                @click="prevPage"
                :disabled="currentPage === 1"
                class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
            >Prev</button>
            <span>Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>
            <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
            >Next</button>
        </div>
    </div>
</div>

<!-- Modal para Adicionar Itens -->
<div 
    x-show="addItemModal"
    x-transition
    class="fixed inset-0 bg-black/60 flex items-center justify-center z-50"
    x-cloak
>
    <div class="bg-gray-900 text-white rounded-xl p-6 w-full max-w-md border border-white/20 shadow-xl space-y-4">
        <h3 class="text-lg font-bold text-green-500">Add Items</h3>

        <form method="POST" action="{{ route('cash.shop.addItems') }}" class="space-y-4">
            @csrf

            <!-- Aba -->
            <div>
                <label class="text-sm text-gray-300 font-semibold">Select Tab:</label>
                <select name="tab" class="w-full mt-1 bg-gray-800 border border-gray-700 rounded-lg p-2 text-white">
                    @foreach($tabs as $tab)
                        <option value="{{ $tab }}">{{ $tab }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Itens -->
            <div>
                <label class="text-sm text-gray-300 font-semibold">Items (ID:PRICE,ID:PRICE...)</label>
                <textarea name="bulk_items" rows="3" class="w-full mt-1 bg-gray-800 border border-gray-700 rounded-lg p-2 text-white placeholder-gray-500" placeholder="1234:500,2345:300"></textarea>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-2">
                <button type="button" @click="addItemModal = false" class="text-sm text-gray-300 hover:text-white">Cancel</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 text-sm rounded-lg text-white font-semibold">Add</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function cashShop() {
    return {
        tabs: @json($tabs),
        activeTab: @json($tabs[0]),
        items: [],
        currentPage: 1,
        totalPages: 1,
        addItemModal: false,

        init() {
            this.fetchItems();
        },

        fetchItems() {
            fetch(`/cash-shop/items?tab=${encodeURIComponent(this.activeTab)}&page=${this.currentPage}`)
                .then(res => res.json())
                .then(data => {
                    this.items = data.items ?? [];
                    this.totalPages = data.totalPages ?? 1;
                    this.currentPage = data.currentPage ?? 1;
                })
                .catch(() => {
                    this.items = [];
                    alert('Failed to load items.');
                });
        },

        changeTab(tab) {
            if (this.activeTab !== tab) {
                this.activeTab = tab;
                this.currentPage = 1;
                this.fetchItems();
            }
        },

        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.fetchItems();
            }
        },

        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.fetchItems();
            }
        }
    }
}
</script>
@endsection
