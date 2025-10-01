INSERT INTO main_fflbro_products (
    distributor,
    distributor_sku,
    manufacturer,
    model,
    description,
    category,
    caliber,
    barrel_length,
    capacity,
    cost_price,
    quantity_available,
    last_updated
)
SELECT 
    'lipseys' as distributor,
    sku as distributor_sku,
    manufacturer,
    model,
    description,
    category,
    caliber,
    barrel_length,
    capacity,
    cost_price,
    quantity_on_hand as quantity_available,
    updated_at as last_updated
FROM wp_fflbro_inventory
ON DUPLICATE KEY UPDATE
    quantity_available = VALUES(quantity_available),
    cost_price = VALUES(cost_price),
    last_updated = VALUES(last_updated);
