<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Inventory</title>
        <link rel="stylesheet" href="{{ asset('css/inventory.css') }}">
    </head>
    <body>
        <div id="toasts" class="toasts" aria-live="assertive" aria-relevant="additions"></div>

        <main class="wrap">
            <header class="page-header">
                <p class="eyebrow">Warehouse</p>
                <h1>Inventory</h1>
                <p class="lede">Add a product, then sell from the stock on hand. Quantities update as soon as a sale succeeds.</p>
            </header>

            <section class="panel" aria-labelledby="create-heading">
                <h2 id="create-heading">New product</h2>
                <form id="product-form" novalidate>
                    <div class="form-grid">
                        <label>
                            Name
                            <input id="product-name" name="name" type="text" maxlength="255" autocomplete="off">
                        </label>
                        <label>
                            SKU
                            <input id="product-sku" name="sku" type="text" maxlength="255" autocomplete="off">
                        </label>
                        <label>
                            Price
                            <input id="product-price" name="price" type="text" inputmode="decimal" autocomplete="off">
                        </label>
                        <label>
                            Initial stock
                            <input id="product-stock" name="stock" type="text" inputmode="numeric" autocomplete="off">
                        </label>
                        <div class="form-actions">
                            <button id="product-submit" class="primary" type="submit">Add product</button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="panel" aria-labelledby="stock-heading">
                <div class="section-heading">
                    <h2 id="stock-heading">On hand</h2>
                    <p id="inventory-status" aria-live="polite">Loading products…</p>
                </div>
                <div class="table-wrap">
                    <table>
                        <caption class="sr-only">Products currently in stock</caption>
                        <thead>
                            <tr>
                                <th scope="col">Product</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Price</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Sell</th>
                            </tr>
                        </thead>
                        <tbody id="product-rows"></tbody>
                    </table>
                </div>
                <div id="pager" class="pager" hidden>
                    <button id="page-prev" type="button">Previous</button>
                    <span id="page-label"></span>
                    <button id="page-next" type="button">Next</button>
                </div>
            </section>
        </main>

        <script src="{{ asset('js/inventory.js') }}"></script>
    </body>
</html>
