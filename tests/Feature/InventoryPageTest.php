<?php

namespace Tests\Feature;

use Tests\TestCase;

class InventoryPageTest extends TestCase
{
    public function test_the_inventory_page_includes_the_form_table_and_toaster(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="product-form"', false)
            ->assertSee('id="product-rows"', false)
            ->assertSee('id="toasts"', false)
            ->assertSee('inventory.js', false)
            ->assertSee('inventory.css', false);
    }
}
