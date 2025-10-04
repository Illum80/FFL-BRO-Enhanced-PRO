-- Migrate Lipseys from wp_fflbro_inventory to main_fflbro_products
-- Date: 2025-09-30
-- Purpose: Consolidate all distributor products into one table for unified search

DELETE FROM main_fflbro_products WHERE distributor='lipseys';

INSERT IGNORE INTO main_fflbro_products (
    distributor, distributor_sku, manufacturer, model, description,
    category, caliber, barrel_length, capacity, cost_price,
    quantity_available, last_updated, item_number
)
SELECT
    'lipseys',
    COALESCE(NULLIF(sku, ''), CONCAT('LP-', id)),
    manufacturer,
    model,
    description,
    category,
    caliber,
    barrel_length,
    capacity,
    COALESCE(cost_price, 0),
    COALESCE(quantity_on_hand, 0),
    updated_at,
    sku
FROM wp_fflbro_inventory
WHERE sku IS NOT NULL AND sku != '';
