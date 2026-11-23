const state = {
    page: 1,
    perPage: 100,
    total: 0,
    products: [],
};

let loadRequest = 0;

const productForm = document.querySelector('#product-form');
const productRows = document.querySelector('#product-rows');
const inventoryStatus = document.querySelector('#inventory-status');
const pager = document.querySelector('#pager');
const pageLabel = document.querySelector('#page-label');
const pagePrev = document.querySelector('#page-prev');
const pageNext = document.querySelector('#page-next');
const toastRegion = document.querySelector('#toasts');

productForm.addEventListener('submit', createProduct);
productRows.addEventListener('click', onRowClick);
productRows.addEventListener('keydown', onRowKeydown);
pagePrev.addEventListener('click', () => changePage(state.page - 1));
pageNext.addEventListener('click', () => changePage(state.page + 1));

loadProducts();

async function createProduct(event) {
    event.preventDefault();

    const fields = {
        name: productForm.elements.name.value,
        sku: productForm.elements.sku.value,
        price: productForm.elements.price.value,
        stock: productForm.elements.stock.value,
    };
    const errors = productErrors(fields);

    if (errors.length > 0) {
        errors.forEach(toast);
        return;
    }

    const submit = document.querySelector('#product-submit');
    submit.disabled = true;

    try {
        const payload = await request('/api/products', {
            method: 'POST',
            body: JSON.stringify({
                name: fields.name.trim(),
                sku: fields.sku.trim(),
                price: Number(fields.price),
                stock: Number(fields.stock),
            }),
        });

        productForm.reset();
        showCreatedProduct(resource(payload));
    } catch (error) {
        report(error);
    } finally {
        submit.disabled = false;
    }
}

function onRowClick(event) {
    const button = event.target.closest('[data-sell]');

    if (!button) {
        return;
    }

    sellProduct(button.closest('tr'));
}

function onRowKeydown(event) {
    if (event.key !== 'Enter' || !event.target.matches('[data-quantity]')) {
        return;
    }

    event.preventDefault();
    sellProduct(event.target.closest('tr'));
}

async function sellProduct(row) {
    if (row.dataset.selling === '1') {
        return;
    }

    const input = row.querySelector('[data-quantity]');
    const button = row.querySelector('[data-sell]');
    const quantity = input.value.trim();

    if (!/^[1-9]\d*$/.test(quantity)) {
        toast('Quantity must be a positive whole number.');
        input.focus();
        return;
    }

    row.dataset.selling = '1';
    button.disabled = true;
    input.disabled = true;

    try {
        const payload = await request(`/api/products/${row.dataset.id}/sell`, {
            method: 'POST',
            body: JSON.stringify({ quantity: Number(quantity) }),
        });

        const product = resource(payload);

        if (product.stock === undefined || product.stock === null) {
            await loadProducts();
            return;
        }

        applyStock(row.dataset.id, Number(product.stock));
        input.value = '';
    } catch (error) {
        report(error);
    } finally {
        delete row.dataset.selling;
        button.disabled = false;
        input.disabled = false;
    }
}

async function loadProducts() {
    const requestId = ++loadRequest;
    inventoryStatus.textContent = 'Loading products…';

    try {
        const payload = await request(`/api/products?page=${state.page}&limit=${state.perPage}`);

        if (requestId !== loadRequest) {
            return;
        }

        const meta = payload.meta ?? payload;

        state.products = Array.isArray(payload.data) ? payload.data : [];
        state.total = Number(meta.total) || 0;
        state.page = Number(meta.current_page) || state.page;
        state.perPage = Number(meta.per_page) || state.perPage;
        renderProducts();
    } catch (error) {
        if (requestId !== loadRequest) {
            return;
        }

        inventoryStatus.textContent = 'Products could not be loaded.';
        report(error);
    }
}

function showCreatedProduct(product) {
    const nextTotal = state.total + 1;
    const currentLastPage = Math.max(1, Math.ceil(state.total / state.perPage));
    const nextLastPage = Math.max(1, Math.ceil(nextTotal / state.perPage));
    const onLastPage = state.total === 0 || state.page === currentLastPage;
    const hasRoom = state.products.length < state.perPage;

    if (onLastPage && hasRoom && state.page === nextLastPage) {
        loadRequest += 1;
        state.products.push(product);
        state.total = nextTotal;
        renderProducts();
        return;
    }

    state.page = nextLastPage;
    loadProducts();
}

function changePage(page) {
    const lastPage = Math.max(1, Math.ceil(state.total / state.perPage));

    if (page < 1 || page > lastPage || page === state.page) {
        return;
    }

    state.page = page;
    loadProducts();
}

function renderProducts() {
    productRows.replaceChildren();

    if (state.products.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 5;
        cell.className = 'empty';
        cell.textContent = 'No products yet. Add one to start tracking stock.';
        row.append(cell);
        productRows.append(row);
    } else {
        state.products.forEach((product) => productRows.append(renderRow(product)));
    }

    const lastPage = Math.max(1, Math.ceil(state.total / state.perPage));
    inventoryStatus.textContent = state.total === 1 ? '1 product' : `${state.total} products`;
    pager.hidden = lastPage <= 1;
    pageLabel.textContent = `Page ${state.page} of ${lastPage}`;
    pagePrev.disabled = state.page <= 1;
    pageNext.disabled = state.page >= lastPage;
}

function renderRow(product) {
    const row = document.createElement('tr');
    row.dataset.id = String(product.id);

    row.append(
        cell('Product', product.name),
        cell('SKU', product.sku, 'sku'),
        cell('Price', formatPrice(product.price)),
        stockCell(product),
        sellCell(product),
    );

    return row;
}

function cell(label, value, className) {
    const element = document.createElement('td');
    element.dataset.label = label;
    element.textContent = value ?? '';

    if (className) {
        element.className = className;
    }

    return element;
}

function stockCell(product) {
    const element = cell('Stock', String(product.stock ?? ''), 'stock');
    element.dataset.stock = String(product.id);
    element.classList.toggle('is-empty', Number(product.stock) === 0);

    return element;
}

function sellCell(product) {
    const element = document.createElement('td');
    element.dataset.label = 'Sell';

    const controls = document.createElement('div');
    controls.className = 'sell-controls';

    const input = document.createElement('input');
    input.type = 'number';
    input.min = '1';
    input.step = '1';
    input.inputMode = 'numeric';
    input.dataset.quantity = '';
    input.setAttribute('aria-label', `Quantity to sell for ${product.name}`);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'sell';
    button.dataset.sell = '';
    button.textContent = 'Sell';

    controls.append(input, button);
    element.append(controls);

    return element;
}

function applyStock(id, stock) {
    const product = state.products.find((item) => String(item.id) === String(id));

    if (product) {
        product.stock = stock;
    }

    const cellElement = productRows.querySelector(`[data-stock="${CSS.escape(String(id))}"]`);

    if (!cellElement) {
        return;
    }

    cellElement.textContent = String(stock);
    cellElement.classList.toggle('is-empty', stock === 0);
    cellElement.classList.remove('is-updated');
    void cellElement.offsetWidth;
    cellElement.classList.add('is-updated');
}

function productErrors(fields) {
    const errors = [];
    const name = fields.name.trim();
    const sku = fields.sku.trim();

    if (name === '') {
        errors.push('Name is required.');
    } else if (name.length > 255) {
        errors.push('Name must be 255 characters or fewer.');
    }

    if (sku === '') {
        errors.push('SKU is required.');
    } else if (sku.length > 255) {
        errors.push('SKU must be 255 characters or fewer.');
    }

    if (fields.price.trim() === '') {
        errors.push('Price is required.');
    } else if (!isNonNegativeNumber(fields.price)) {
        errors.push('Price must be a number zero or greater.');
    }

    if (fields.stock.trim() === '') {
        errors.push('Initial stock is required.');
    } else if (!/^\d+$/.test(fields.stock.trim())) {
        errors.push('Initial stock must be a whole number zero or greater.');
    }

    return errors;
}

function isNonNegativeNumber(value) {
    const amount = Number(value);

    return value.trim() !== '' && Number.isFinite(amount) && amount >= 0;
}

function resource(payload) {
    if (payload && payload.data && !Array.isArray(payload.data)) {
        return payload.data;
    }

    return payload ?? {};
}

async function request(url, options = {}) {
    let response;

    try {
        response = await fetch(url, {
            ...options,
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        });
    } catch {
        throw new Error('Could not reach the server. Check your connection and try again.');
    }

    const body = await readBody(response);

    if (!response.ok) {
        const error = new Error('Request failed.');
        error.messages = messagesFrom(body, response.status);
        throw error;
    }

    return body ?? {};
}

async function readBody(response) {
    const text = await response.text();

    if (text === '') {
        return null;
    }

    try {
        return JSON.parse(text);
    } catch {
        return { message: 'The server returned an unexpected response.' };
    }
}

function messagesFrom(body, status) {
    const messages = [];

    if (body && typeof body.errors === 'object' && body.errors) {
        Object.values(body.errors).forEach((value) => {
            const items = Array.isArray(value) ? value : [value];
            items.forEach((item) => {
                if (item) {
                    messages.push(String(item));
                }
            });
        });
    }

    if (messages.length === 0 && body && body.message) {
        messages.push(String(body.message));
    }

    if (messages.length === 0) {
        messages.push(`Request failed (${status}).`);
    }

    return [...new Set(messages)];
}

function report(error) {
    const messages = Array.isArray(error.messages) && error.messages.length > 0
        ? error.messages
        : [error.message || 'Something went wrong.'];

    messages.forEach(toast);
}

function toast(message) {
    const item = document.createElement('div');
    item.className = 'toast';
    item.role = 'alert';
    item.textContent = message;
    toastRegion.append(item);

    while (toastRegion.children.length > 5) {
        toastRegion.firstElementChild.remove();
    }

    const remove = () => {
        if (!item.isConnected) {
            return;
        }

        item.classList.add('toast-out');
        item.addEventListener('animationend', () => item.remove(), { once: true });
        window.setTimeout(() => item.remove(), 220);
    };

    item.addEventListener('click', remove);
    window.setTimeout(remove, 5000);
}

function formatPrice(price) {
    const amount = Number(price);

    if (!Number.isFinite(amount)) {
        return '';
    }

    return amount.toFixed(2);
}
