<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Laravel - Cashier Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 h-screen flex flex-col overflow-hidden">

    <!-- Top Navigation Header -->
    <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between shadow-sm flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-indigo-100">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-900 leading-tight">PoS Nusantara Boilerplate</h1>
                <p class="text-xs text-slate-500 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                    {{ $outlet->name ?? 'Outlet Utama' }} &bull; Shift Aktif: <strong>{{ $cashier->name ?? 'Kasir Utama' }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <div class="bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs text-slate-600 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-indigo-500"></i>
                <span>Midtrans: <strong>{{ config('midtrans.enabled') && !str_contains(config('midtrans.server_key'), 'DEMO_TEST_KEY') ? 'Sandbox Aktif' : 'Demo / Halted' }}</strong></span>
            </div>
            <a href="/api/v1/orders" target="_blank" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition">
                <i class="fa-solid fa-code mr-1"></i> API Orders
            </a>
            <div class="text-xs font-mono text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                Port: 8000 | PHP 8.3
            </div>
        </div>
    </header>

    <!-- Main POS Screen -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Side: Catalog, Filter, Search -->
        <main class="flex-1 flex flex-col overflow-hidden p-6 border-r border-slate-200">
            <!-- Search and Category Tabs -->
            <div class="mb-5 space-y-4 flex-shrink-0">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-3.5 text-slate-400"></i>
                    <input type="text" id="searchInput" placeholder="Cari menu, produk, SKU, atau scan barcode..." 
                        class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm">
                </div>

                <!-- Category Filters -->
                <div class="flex items-center space-x-2 overflow-x-auto pb-1" id="categoryFilterContainer">
                    <button onclick="filterCategory(null)" class="category-tab active px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-indigo-600 text-white shadow-sm shadow-indigo-200">
                        <i class="fa-solid fa-border-all"></i> Semua Menu
                    </button>
                    @foreach($categories as $category)
                        <button onclick="filterCategory({{ $category->id }})" class="category-tab px-4 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center gap-2">
                            <i class="fa-solid fa-tag text-indigo-500"></i> {{ $category->name }} ({{ $category->products_count }})
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Product Grid -->
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="productGrid">
                    @foreach($products as $product)
                        <div class="product-card bg-white rounded-2xl p-4 border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition cursor-pointer flex flex-col justify-between"
                             data-id="{{ $product->id }}"
                             data-category="{{ $product->category_id }}"
                             data-name="{{ strtolower($product->name) }}"
                             data-sku="{{ strtolower($product->sku ?? '') }}"
                             data-barcode="{{ strtolower($product->barcode ?? '') }}"
                             onclick="addToCart({{ json_encode($product) }})">
                            <div>
                                <div class="flex items-center justify-between text-xs mb-2">
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 font-medium">{{ $product->category->name ?? 'Umum' }}</span>
                                    @if($product->track_stock)
                                        <span class="font-semibold {{ $product->current_stock <= 5 ? 'text-rose-500' : 'text-slate-500' }}">
                                            Stok: {{ $product->current_stock }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Jasa</span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-slate-900 text-sm mb-1 leading-snug">{{ $product->name }}</h3>
                                <p class="text-xs text-slate-500 line-clamp-2 mb-3">{{ $product->description ?? 'Tidak ada deskripsi' }}</p>
                            </div>
                            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                                <span class="font-extrabold text-indigo-600 text-sm">
                                    Rp {{ number_format((float)$product->base_price, 0, ',', '.') }}
                                </span>
                                <button class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition flex items-center justify-center text-xs">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </main>

        <!-- Right Side: Order Cart & Checkout -->
        <aside class="w-96 bg-white flex flex-col overflow-hidden shadow-lg border-l border-slate-200">
            <!-- Cart Header -->
            <div class="p-4 border-b border-slate-200 flex-shrink-0 bg-slate-50/50">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-indigo-600"></i> Keranjang Transaksi
                    </h2>
                    <button onclick="clearCart()" class="text-xs text-rose-600 hover:text-rose-700 font-medium">
                        <i class="fa-solid fa-trash-can mr-1"></i> Reset
                    </button>
                </div>

                <!-- Customer & Order Type Inputs -->
                <div class="space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" id="customerName" placeholder="Nama Pelanggan" value="Walk-in Customer" 
                            class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        <input type="text" id="tableNumber" placeholder="No Meja (Opsional)" 
                            class="px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="grid grid-cols-3 gap-1 bg-slate-200/60 p-1 rounded-lg text-xs font-semibold text-center">
                        <button type="button" class="order-type-btn active py-1.5 rounded-md bg-white text-indigo-600 shadow-sm" onclick="setOrderType('retail', this)">Retail</button>
                        <button type="button" class="order-type-btn py-1.5 rounded-md text-slate-600 hover:bg-white/60" onclick="setOrderType('dine_in', this)">Dine In</button>
                        <button type="button" class="order-type-btn py-1.5 rounded-md text-slate-600 hover:bg-white/60" onclick="setOrderType('take_away', this)">Take Away</button>
                    </div>
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="cartItemList">
                <div class="h-full flex flex-col items-center justify-center text-slate-400 text-center py-10" id="emptyCartMessage">
                    <i class="fa-solid fa-bag-shopping text-4xl mb-3 text-slate-300"></i>
                    <p class="text-xs font-medium">Keranjang masih kosong.<br>Klik produk di sebelah kiri untuk menambahkan.</p>
                </div>
            </div>

            <!-- Cart Summary & Calculations -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 space-y-2 flex-shrink-0 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span>
                    <span id="subtotalDisplay" class="font-semibold text-slate-800">Rp 0</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Pajak (PPN 11%)</span>
                    <span id="taxDisplay" class="font-semibold text-slate-800">Rp 0</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Service Charge (5%)</span>
                    <span id="serviceDisplay" class="font-semibold text-slate-800">Rp 0</span>
                </div>
                <div class="flex justify-between items-baseline pt-2 border-t border-slate-200 text-slate-900 font-extrabold text-base">
                    <span>Total Tagihan</span>
                    <span id="totalDisplay" class="text-indigo-600 text-lg">Rp 0</span>
                </div>

                <!-- Payment Method Selector -->
                <div class="pt-2">
                    <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="btnPayCash" onclick="selectPaymentMethod('cash')" 
                            class="pay-method-btn active py-2 px-3 border-2 border-indigo-600 bg-indigo-50 text-indigo-700 font-bold rounded-xl text-xs flex items-center justify-center gap-2">
                            <i class="fa-solid fa-money-bill-wave"></i> Tunai (Cash)
                        </button>
                        <button type="button" id="btnPayMidtrans" onclick="selectPaymentMethod('midtrans_snap')" 
                            class="pay-method-btn py-2 px-3 border border-slate-200 bg-white text-slate-700 font-semibold rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-slate-50">
                            <i class="fa-solid fa-qrcode text-emerald-600"></i> QRIS / Midtrans
                        </button>
                    </div>
                </div>

                <!-- Cash Tender Input Section -->
                <div id="cashInputSection" class="pt-1">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[11px] font-bold text-slate-600 uppercase">Uang Diterima</label>
                        <span id="changeDisplay" class="text-xs font-bold text-emerald-600">Kembalian: Rp 0</span>
                    </div>
                    <input type="number" id="cashReceivedInput" placeholder="Masukkan jumlah uang..." 
                        class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        oninput="calculateChange()">
                    <div class="flex gap-1 mt-1">
                        <button type="button" onclick="setExactCash()" class="flex-1 py-1 bg-slate-200 text-[10px] font-bold rounded hover:bg-slate-300">Uang Pas</button>
                        <button type="button" onclick="addCash(50000)" class="flex-1 py-1 bg-slate-200 text-[10px] font-bold rounded hover:bg-slate-300">+50rb</button>
                        <button type="button" onclick="addCash(100000)" class="flex-1 py-1 bg-slate-200 text-[10px] font-bold rounded hover:bg-slate-300">+100rb</button>
                    </div>
                </div>

                <!-- Checkout Button -->
                <button type="button" id="checkoutButton" onclick="processCheckout()" 
                    class="w-full mt-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md shadow-indigo-200 text-sm transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-check-circle"></i> Selesaikan Transaksi
                </button>
            </div>
        </aside>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl text-center space-y-4 animate-scaleUp">
            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <h3 class="text-xl font-extrabold text-slate-900">Transaksi Berhasil!</h3>
                <p class="text-xs text-slate-500 mt-1" id="modalInvoiceText">Invoice #INV-XXXX</p>
            </div>

            <div class="bg-slate-50 rounded-xl p-4 text-xs space-y-2 text-left border border-slate-200">
                <div class="flex justify-between">
                    <span class="text-slate-500">Total Tagihan:</span>
                    <span class="font-bold text-slate-900" id="modalTotalText">Rp 0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Metode Bayar:</span>
                    <span class="font-bold uppercase text-slate-900" id="modalMethodText">CASH</span>
                </div>
                <div class="flex justify-between" id="modalChangeRow">
                    <span class="text-slate-500">Kembalian:</span>
                    <span class="font-bold text-emerald-600 text-sm" id="modalChangeText">Rp 0</span>
                </div>
            </div>

            <div class="space-y-2 pt-2">
                <a id="btnThermalPdf" href="#" target="_blank" 
                   class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i class="fa-solid fa-print"></i> Cetak Struk Kasir (Thermal 58mm PDF)
                </a>
                <a id="btnInvoicePdf" href="#" target="_blank" 
                   class="w-full py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i class="fa-solid fa-file-pdf"></i> Cetak Faktur Resmi A4 (PDF)
                </a>
                <button type="button" onclick="closeSuccessModal()" 
                    class="w-full py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold rounded-xl text-xs transition">
                    Transaksi Baru
                </button>
            </div>
        </div>
    </div>

    <!-- POS Javascript Logic -->
    <script>
        const outletId = {{ $outlet->id ?? 1 }};
        const taxRate = {{ (float)($outlet->tax_percentage ?? 11) }} / 100;
        const serviceRate = {{ (float)($outlet->service_charge_percentage ?? 5) }} / 100;

        let cart = [];
        let selectedOrderType = 'retail';
        let selectedPaymentMethod = 'cash';
        let totalCalculated = 0;

        function filterCategory(categoryId) {
            document.querySelectorAll('.category-tab').forEach(el => {
                el.classList.remove('bg-indigo-600', 'text-white', 'shadow-sm', 'shadow-indigo-200');
                el.classList.add('bg-white', 'text-slate-600');
            });
            event.currentTarget.classList.add('bg-indigo-600', 'text-white', 'shadow-sm', 'shadow-indigo-200');
            event.currentTarget.classList.remove('bg-white', 'text-slate-600');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (!categoryId || card.dataset.category == categoryId) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                const name = card.dataset.name;
                const sku = card.dataset.sku;
                const barcode = card.dataset.barcode;
                if (name.includes(query) || sku.includes(query) || barcode.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        function setOrderType(type, btn) {
            selectedOrderType = type;
            document.querySelectorAll('.order-type-btn').forEach(b => {
                b.classList.remove('active', 'bg-white', 'text-indigo-600', 'shadow-sm');
                b.classList.add('text-slate-600');
            });
            btn.classList.add('active', 'bg-white', 'text-indigo-600', 'shadow-sm');
            btn.classList.remove('text-slate-600');
        }

        function addToCart(product) {
            const existingIndex = cart.findIndex(item => item.product_id === product.id && !item.product_variant_id);
            if (existingIndex > -1) {
                cart[existingIndex].quantity += 1;
            } else {
                cart.push({
                    product_id: product.id,
                    product_variant_id: null,
                    name: product.name,
                    price: parseFloat(product.base_price),
                    quantity: 1,
                    discount_amount: 0,
                    notes: ''
                });
            }
            renderCart();
        }

        function changeQty(index, delta) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            renderCart();
        }

        function clearCart() {
            cart = [];
            renderCart();
        }

        function renderCart() {
            const container = document.getElementById('cartItemList');
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="h-full flex flex-col items-center justify-center text-slate-400 text-center py-10" id="emptyCartMessage">
                        <i class="fa-solid fa-bag-shopping text-4xl mb-3 text-slate-300"></i>
                        <p class="text-xs font-medium">Keranjang masih kosong.<br>Klik produk di sebelah kiri untuk menambahkan.</p>
                    </div>`;
                updateSummary(0);
                return;
            }

            let html = '';
            let subtotal = 0;

            cart.forEach((item, index) => {
                const itemTotal = (item.price * item.quantity) - item.discount_amount;
                subtotal += itemTotal;

                html += `
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h4 class="font-bold text-slate-900 text-xs">${item.name}</h4>
                            <span class="text-[11px] text-indigo-600 font-semibold">Rp ${item.price.toLocaleString('id-ID')}</span>
                        </div>
                        <button onclick="changeQty(${index}, -${item.quantity})" class="text-slate-400 hover:text-rose-500 text-xs">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between pt-1">
                        <div class="flex items-center space-x-2 bg-white border border-slate-200 rounded-lg p-0.5">
                            <button onclick="changeQty(${index}, -1)" class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xs font-bold">-</button>
                            <span class="text-xs font-bold w-6 text-center">${item.quantity}</span>
                            <button onclick="changeQty(${index}, 1)" class="w-6 h-6 rounded flex items-center justify-center text-slate-600 hover:bg-slate-100 text-xs font-bold">+</button>
                        </div>
                        <span class="font-extrabold text-slate-900 text-xs">Rp ${itemTotal.toLocaleString('id-ID')}</span>
                    </div>
                </div>`;
            });

            container.innerHTML = html;
            updateSummary(subtotal);
        }

        function updateSummary(subtotal) {
            const tax = subtotal * taxRate;
            const service = subtotal * serviceRate;
            totalCalculated = subtotal + tax + service;

            document.getElementById('subtotalDisplay').innerText = `Rp ${subtotal.toLocaleString('id-ID')}`;
            document.getElementById('taxDisplay').innerText = `Rp ${tax.toLocaleString('id-ID')}`;
            document.getElementById('serviceDisplay').innerText = `Rp ${service.toLocaleString('id-ID')}`;
            document.getElementById('totalDisplay').innerText = `Rp ${totalCalculated.toLocaleString('id-ID')}`;

            calculateChange();
        }

        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            const btnCash = document.getElementById('btnPayCash');
            const btnMidtrans = document.getElementById('btnPayMidtrans');
            const cashSection = document.getElementById('cashInputSection');

            if (method === 'cash') {
                btnCash.className = 'pay-method-btn active py-2 px-3 border-2 border-indigo-600 bg-indigo-50 text-indigo-700 font-bold rounded-xl text-xs flex items-center justify-center gap-2';
                btnMidtrans.className = 'pay-method-btn py-2 px-3 border border-slate-200 bg-white text-slate-700 font-semibold rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-slate-50';
                cashSection.style.display = 'block';
            } else {
                btnMidtrans.className = 'pay-method-btn active py-2 px-3 border-2 border-indigo-600 bg-indigo-50 text-indigo-700 font-bold rounded-xl text-xs flex items-center justify-center gap-2';
                btnCash.className = 'pay-method-btn py-2 px-3 border border-slate-200 bg-white text-slate-700 font-semibold rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-slate-50';
                cashSection.style.display = 'none';
            }
        }

        function calculateChange() {
            if (selectedPaymentMethod !== 'cash') return;
            const cashInput = parseFloat(document.getElementById('cashReceivedInput').value) || 0;
            const change = Math.max(0, cashInput - totalCalculated);
            document.getElementById('changeDisplay').innerText = `Kembalian: Rp ${change.toLocaleString('id-ID')}`;
        }

        function setExactCash() {
            document.getElementById('cashReceivedInput').value = totalCalculated;
            calculateChange();
        }

        function addCash(amount) {
            const current = parseFloat(document.getElementById('cashReceivedInput').value) || 0;
            document.getElementById('cashReceivedInput').value = current + amount;
            calculateChange();
        }

        async function processCheckout() {
            if (cart.length === 0) {
                alert('Pilih minimal 1 produk ke keranjang!');
                return;
            }

            const customerName = document.getElementById('customerName').value || 'Walk-in Customer';
            const tableNumber = document.getElementById('tableNumber').value || null;
            const cashReceived = parseFloat(document.getElementById('cashReceivedInput').value) || 0;

            if (selectedPaymentMethod === 'cash' && cashReceived < totalCalculated) {
                alert('Jumlah uang tunai yang diterima kurang dari total tagihan!');
                return;
            }

            const checkoutBtn = document.getElementById('checkoutButton');
            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Memproses...';

            const payload = {
                outlet_id: outletId,
                customer_name: customerName,
                order_type: selectedOrderType,
                table_number: tableNumber,
                payment_method: selectedPaymentMethod,
                cash_received: cashReceived,
                items: cart.map(i => ({
                    product_id: i.product_id,
                    product_variant_id: i.product_variant_id,
                    quantity: i.quantity,
                    discount_amount: i.discount_amount,
                    notes: i.notes
                }))
            };

            try {
                const response = await fetch('/api/v1/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();
                if (!result.success) {
                    alert('Gagal memproses transaksi: ' + (result.message || 'Error'));
                    return;
                }

                const order = result.data;
                showSuccessModal(order);
                clearCart();
            } catch (err) {
                alert('Terjadi kesalahan koneksi server: ' + err.message);
            } finally {
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = '<i class="fa-solid fa-check-circle"></i> Selesaikan Transaksi';
            }
        }

        function showSuccessModal(order) {
            document.getElementById('modalInvoiceText').innerText = `Invoice #${order.invoice_number}`;
            document.getElementById('modalTotalText').innerText = `Rp ${parseFloat(order.total_amount).toLocaleString('id-ID')}`;
            document.getElementById('modalMethodText').innerText = order.payment_method;

            const changeRow = document.getElementById('modalChangeRow');
            if (order.latest_payment && order.latest_payment.change_amount > 0) {
                changeRow.style.display = 'flex';
                document.getElementById('modalChangeText').innerText = `Rp ${parseFloat(order.latest_payment.change_amount).toLocaleString('id-ID')}`;
            } else {
                changeRow.style.display = 'none';
            }

            document.getElementById('btnThermalPdf').href = `/api/v1/orders/${order.id}/receipt-pdf?width=58`;
            document.getElementById('btnInvoicePdf').href = `/api/v1/orders/${order.id}/invoice-pdf`;

            document.getElementById('successModal').classList.remove('hidden');
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
            document.getElementById('cashReceivedInput').value = '';
            calculateChange();
        }
    </script>
</body>
</html>
