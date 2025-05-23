@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto p-6">

    <!-- Botão Update DB -->
    <div class="bg-white/10 backdrop-blur-md text-white p-6 rounded-xl shadow-lg border border-white/20 mb-8">
        <h2 class="text-lg font-semibold mb-4 text-center text-yellow-400">Update Cash Shop DB</h2>

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

    </div>

    <!-- Cash Shop Tabs e Itens -->
    <div x-data="cashShop()" class="text-white">

        <!-- Tabs -->
        <div class="flex space-x-2 mb-4">
            <template x-for="tab in tabs" :key="tab">
                <button
                    :class="{'bg-yellow-500 text-black': tab === activeTab, 'bg-white/20': tab !== activeTab}"
                    class="px-4 py-2 rounded-md font-semibold transition"
                    @click="changeTab(tab)"
                    x-text="tab"
                ></button>
            </template>
        </div>

        <!-- Items Grid -->
        <div class="grid grid-cols-4 gap-4">
            <template x-if="items.length === 0">
                <div class="col-span-4 text-center text-gray-300">No items found.</div>
            </template>

            <template x-for="item in items" :key="item.id">
                <div class="bg-white/10 p-4 rounded-lg text-center">
                    <div class="font-bold mb-1" x-text="item.aegisname"></div>
                    <div class="text-sm">ID: <span x-text="item.id"></span></div>
                    <div class="text-sm">Price: <span x-text="item.price"></span></div>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center items-center space-x-4 mt-6" x-show="totalPages > 1">
            <button
                class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                @click="prevPage()"
                :disabled="currentPage === 1"
            >
                Prev
            </button>
            <span>Page <span x-text="currentPage"></span> of <span x-text="totalPages"></span></span>
            <button
                class="bg-yellow-500 hover:bg-yellow-600 px-3 py-1 rounded disabled:opacity-50 disabled:cursor-not-allowed"
                @click="nextPage()"
                :disabled="currentPage === totalPages"
            >
                Next
            </button>
        </div>

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

        fetchItems() {
            fetch(`/cash-shop/items?tab=${encodeURIComponent(this.activeTab)}&page=${this.currentPage}`)
                .then(res => res.json())
                .then(data => {
                    if (!data.error) {
                        this.items = data.items;
                        this.totalPages = data.totalPages;
                        this.currentPage = data.currentPage;
                    } else {
                        this.items = [];
                        this.totalPages = 1;
                        this.currentPage = 1;
                        alert(data.error);
                    }
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
        },

        init() {
            this.fetchItems();
        }
    }
}
</script>
@endsection

