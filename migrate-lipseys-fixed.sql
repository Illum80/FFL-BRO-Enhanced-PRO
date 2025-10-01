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
