-- =======================================================================
-- INSERCIÓN DE DATOS USANDO nextval() y gen_random_uuid()
-- =======================================================================

-- Variables para almacenar los IDs generados
DO $$
DECLARE
new_user_id BIGINT;
new_category_id BIGINT;
new_business_id BIGINT;
new_menu_id BIGINT;
new_food_cat1_id BIGINT;
new_food_cat2_id BIGINT;
new_food_cat3_id BIGINT;
new_drink_cat_id BIGINT;
BEGIN

-- Insertar Usuario (sin datos de dirección)
INSERT INTO "users" (
"id", "uuid", "name", "last_name", "username", "email", "email_verified_at", "password", "phone", "date_of_birth",
"gender", "profile_photo_path", "deleted_at", "remember_token", "created_at", "updated_at", "terms_and_conditions"
) VALUES (
nextval('users_id_seq'),
gen_random_uuid(),
'Hector Antonio',
'Waldman',
'hectorwaldmann',
'hwald@mailinator.com',
NULL,
'$2y$12$/43vO53dSPK4w2rDZnlfv.YZ3B0/FBPKxTG.8oyARdkNIFviJ5jGa',
'963490320',
'1990-01-01T00:00:00.000Z',
'male',
'https://foodly.s3.amazonaws.com/public/profile-photos/TsB693hYMJrNjMknMlXInw6csx96JfP1QSeHcMLg.jpg',
NULL,
NULL,
NOW(),
NOW(),
false
) RETURNING id INTO new_user_id;

-- Insertar dirección en user_addresses
INSERT INTO "user_addresses" (
"uuid", "user_id", "address", "city", "country", "zip_code", "latitude", "longitude", "address_label_id", "principal", "created_at", "updated_at"
) VALUES (
gen_random_uuid(),
new_user_id,
'Rua Visconde de Coriscada',
'Covilhã',
'Portugal',
'6200-154',
40.2808791,
-7.502642200000001,
1, -- label_id (ejemplo: Home)
true,
NOW(),
NOW()
);

-- Insertar Categoría
INSERT INTO "categories" ("id", "category_uuid", "category_name", "category_description", "category_image_path", "user_id", "created_at", "updated_at")
VALUES (
nextval('categories_id_seq'),
gen_random_uuid(),
'Cafés and Breakfasts',
'Descripción de Cafés y Desayunos',
'https://foodly.s3.amazonaws.com/public/categories_images/NC2D2vwB28ReT6E9HyBaq1rkJJLGCBodBLdNLvee.jpg',
new_user_id,
NOW(),
NOW()
) RETURNING id INTO new_category_id;

-- Insertar Negocio
INSERT INTO "businesses" ("id", "business_uuid", "business_logo", "business_name", "business_email", "business_phone", "business_address", "business_zipcode", "business_city", "business_country", "business_website", "business_about_us", "business_additional_info", "business_latitude", "business_longitude", "category_id", "user_id", "deleted_at", "created_at", "updated_at")
VALUES (
nextval('businesses_id_seq'),
gen_random_uuid(),
'https://foodly.s3.amazonaws.com/public/business_logos/yiWisIHflvi8cS4Z9bq9olP1EpDf5Xgf2KpQmsMc.jpg',
'HW Restaurant',
'long\*' || extract(epoch from now())::text || '@mailinator.com',
'925015154',
'11 de Septiembre de 1888',
'C1429',
'Buenos Aires',
'Argentina',
NULL,
'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry''s standard dummy text ever since the 1500s',
NULL,
-34.5414946,
-58.4645778,
new_category_id,
new_user_id,
NULL,
NOW(),
NOW()
) RETURNING id INTO new_business_id;

-- Insertar Menú
INSERT INTO "business_menus" ("id", "uuid", "business_id", "created_at", "updated_at")
VALUES (
nextval('business_menus_id_seq'),
gen_random_uuid(),
new_business_id,
NOW(),
NOW()
) RETURNING id INTO new_menu_id;

-- Insertar Categorías de Comida
INSERT INTO "business_food_categories" ("id", "uuid", "name", "business_menu_id", "created_at", "updated_at")
VALUES
(nextval('business_food_categories_id_seq'), gen_random_uuid(), 'Pizzas 4', new_menu_id, NOW(), NOW()),
(nextval('business_food_categories_id_seq'), gen_random_uuid(), 'Carnes', new_menu_id, NOW(), NOW()),
(nextval('business_food_categories_id_seq'), gen_random_uuid(), 'Sushi', new_menu_id, NOW(), NOW());

-- Obtener los IDs de las categorías de comida
SELECT id INTO new_food_cat1_id FROM "business_food_categories" WHERE "name" = 'Pizzas 4' AND "business_menu_id" = new_menu_id;
SELECT id INTO new_food_cat2_id FROM "business_food_categories" WHERE "name" = 'Carnes' AND "business_menu_id" = new_menu_id;
SELECT id INTO new_food_cat3_id FROM "business_food_categories" WHERE "name" = 'Sushi' AND "business_menu_id" = new_menu_id;

-- Insertar Categoría de Bebidas
INSERT INTO "business_drink_categories" ("id", "uuid", "name", "business_menu_id", "created_at", "updated_at")
VALUES (
nextval('business_drink_categories_id_seq'),
gen_random_uuid(),
'Cocktails',
new_menu_id,
NOW(),
NOW()
) RETURNING id INTO new_drink_cat_id;

-- Insertar Items de Comida
INSERT INTO "business_food_items" ("id", "uuid", "business_food_category_id", "name", "description", "versions", "prices", "favorites_count", "available", "created_at", "updated_at")
VALUES
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat2_id, 'Asado Argentino', 'descripcion de asado argento', '["regular", "medium", "big"]', '{"big": 7, "medium": 5, "regular": 3}', 0, true, NOW(), NOW()),
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat3_id, 'Sushi ? plates', 'description 5', '["regular", "big"]', '{"big": 9.35, "medium": 0, "regular": 7.5}', 0, true, NOW(), NOW()),
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat1_id, 'Pizza test name', 'pizza test description 123edited', '["regular", "big", "medium"]', '{"big": 8.55, "medium": 6.93, "regular": 3.88}', 1, true, NOW(), NOW()),
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat1_id, 'Pizza', 'det', '["medium", "big", "regular"]', '{"big": 2, "medium": 17, "regular": 14}', 0, true, NOW(), NOW()),
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat2_id, 'Barbecues', '654645665465645654654634645643645645645645645664564565465664564564565464565654645645646556546566546', '[]', '{"big": 0, "medium": 0, "regular": 65}', 0, false, NOW(), NOW()),
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat1_id, 'Pizza Americana', 'descripcion de prueba by dev', '["regular", "medium", "big"]', '{"big": 9, "medium": 6, "regular": 3}', 1, true, NOW(), NOW()),
(nextval('business_food_items_id_seq'), gen_random_uuid(), new_food_cat1_id, 'Margarita', 'desc pizza', '["regular"]', '{"big": 0, "medium": 0, "regular": 3}', 0, true, NOW(), NOW());

-- Insertar Items de Bebidas
INSERT INTO "business_drink_items" ("id", "uuid", "business_drink_category_id", "name", "description", "versions", "prices", "favorites_count", "available", "created_at", "updated_at")
VALUES
(nextval('business_drink_items_id_seq'), gen_random_uuid(), new_drink_cat_id, 'Bebida 1', 'Lorem Ipsum.', '["regular"]', '{"big": 0, "medium": 0, "regular": 9}', 1, true, NOW(), NOW()),
(nextval('business_drink_items_id_seq'), gen_random_uuid(), new_drink_cat_id, 'Bebida 2', 'natural', '["regular"]', '{"big": 0, "medium": 0, "regular": 0.33}', 1, true, NOW(), NOW()),
(nextval('business_drink_items_id_seq'), gen_random_uuid(), new_drink_cat_id, 'Bebida 3', 'rgaergrg', '["regular", "medium", "big"]', '{"big": 0.678768, "medium": 0.333, "regular": 0.567}', 1, true, NOW(), NOW());

-- Insertar Combos
INSERT INTO "business_combos" ("id", "uuid", "business_menu_id", "name", "description", "versions", "prices", "favorites_count", "available", "created_at", "updated_at")
VALUES
(nextval('business_combos_id_seq'), gen_random_uuid(), new_menu_id, '2 x 1 Pizzas', 'descripcion de prueba en combos', '["regular"]', '{"regular":5,"medium":0,"big":0}', 0, true, NOW(), NOW()),
(nextval('business_combos_id_seq'), gen_random_uuid(), new_menu_id, '3 x 2 Cafe & breakfast', 'desc testing by dev', '["regular","medium","big"]', '{"regular":4.3,"medium":5.6,"big":7}', 0, true, NOW(), NOW()),
(nextval('business_combos_id_seq'), gen_random_uuid(), new_menu_id, '30% descuento coffee', 'lorem ipsum 30% cf', '["regular","medium"]', '{"regular":6,"medium":8,"big":0}', 0, true, NOW(), NOW()),
(nextval('business_combos_id_seq'), gen_random_uuid(), new_menu_id, 'Cafe promo', 'cafe barato y sabroso', '["regular","medium","big"]', '{"regular":5,"medium":7,"big":9}', 0, true, NOW(), NOW());

-- Insertar Promociones
INSERT INTO "promotions" ("id", "uuid", "title", "sub_title", "description", "start_date", "expire_date", "versions", "prices", "favorites_count", "available", "media_link", "business_id", "created_at", "updated_at")
VALUES
(nextval('promotions_id_seq'), gen_random_uuid(), 'Descuento del 30% los lunes', '¡Hamburguesas rebajadas!', '¡Disfruta de un 30% de descuento en todas nuestras hamburguesas todos los lunes de febrero! Ven y aprovecha esta increíble oferta para satisfacer tus antojos de hamburguesas. ¡No te lo pierdas!', '2025-01-18T00:00:00.000', '2025-03-29T00:00:00.000', '[]', '{"regular":0,"medium":0,"big":0}', 0, true, 'https://www.youtube.com/watch?v=VyvdYxoy5Ds', new_business_id, NOW(), NOW()),
(nextval('promotions_id_seq'), gen_random_uuid(), 'Viernes de 3x2', 'Happy hour de cerveza', 'Disfruta de nuestra promoción de cervezas 3x2 los viernes de 8 a 11pm. ¡No te lo pierdas!', '2025-01-24T00:00:00.000', '2025-02-28T00:00:00.000', '[]', '{"regular":0,"medium":0,"big":0}', 0, true, NULL, new_business_id, NOW(), NOW()),
(nextval('promotions_id_seq'), gen_random_uuid(), 'Barbecue Beef 10% Off', 'Limited Time Offer', 'Get 10% off on our mouthwatering barbecue beef. Juicy, tender, and packed with flavor - a deal you won''t want to miss!', '2025-02-14T00:00:00.000', '2025-04-25T00:00:00.000', '[]', '{"regular":0,"medium":0,"big":0}', 0, true, NULL, new_business_id, NOW(), NOW()),
(nextval('promotions_id_seq'), gen_random_uuid(), 'Happy Hour 3x2 los jueves', 'DJ de electrónica amenizando', 'Disfruta de nuestra promoción de Happy Hour con cervezas Heineken. ¡Ven los jueves y aprovecha la oferta 3x2 en cervezas con la mejor música electrónica a cargo de nuestro DJ!', '2025-05-23T00:00:00.000', '2025-08-29T00:00:00.000', '[]', '{"regular":0,"medium":0,"big":0}', 0, true, NULL, new_business_id, NOW(), NOW()),
(nextval('promotions_id_seq'), gen_random_uuid(), 'Special Pizza Deal', '2 for the Price of 1', 'Get 2 delicious pizzas for the price of 1! Don''t miss out on this amazing offer and enjoy double the flavor today.', '2025-06-29T00:00:00.000', '2025-08-31T00:00:00.000', '[]', '{"regular":0,"medium":0,"big":0}', 0, true, NULL, new_business_id, NOW(), NOW()),
(nextval('promotions_id_seq'), gen_random_uuid(), 'Promo 3x2 Viernes', '¡Pizzas a mitad de precio!', 'Disfruta de la promo de 3x2 en pizzas todos los viernes. Compra dos pizzas y llévate la tercera gratis. ¡No te pierdas esta increíble oferta!', '2025-07-30T00:00:00.000', '2025-08-28T00:00:00.000', '[]', '{"regular":0,"medium":0,"big":0}', 0, true, NULL, new_business_id, NOW(), NOW());

-- Asignar rol al usuario (Spatie Laravel Permission)
-- Asumiendo que el rol con ID 2 existe (probablemente 'business_owner' o similar)
INSERT INTO "model_has_roles" ("role_id", "model_type", "model_id")
VALUES (2, 'App\\Models\\User', new_user_id);

-- Mensaje de confirmación con los IDs generados
RAISE NOTICE 'Datos insertados correctamente:';
RAISE NOTICE 'Usuario ID: %', new_user_id;
RAISE NOTICE 'Categoría ID: %', new_category_id;
RAISE NOTICE 'Negocio ID: %', new_business_id;
RAISE NOTICE 'Menú ID: %', new_menu_id;
RAISE NOTICE 'Rol asignado al usuario correctamente';

END $$;
