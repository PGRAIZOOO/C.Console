DROP DATABASE IF EXISTS `C.Console`;

CREATE DATABASE `C.Console`;
USE `C.Console`;

DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS orderline;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stock;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Customer') NOT NULL DEFAULT 'Customer',
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20),
    address TEXT,
    photo VARCHAR(100),
    is_active TINYINT DEFAULT 1
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    game_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    game_studio VARCHAR(100),
    product_type ENUM('PlayStation 5 Game') NOT NULL DEFAULT 'PlayStation 5 Game',
    genre ENUM('Action', 'Adventure', 'Survival', 'Casual', 'Violence'),
    unit_cost_price DECIMAL(10,2) NOT NULL,
    unit_selling_price DECIMAL(10,2) NOT NULL,
    min_stock_threshold INT DEFAULT 5,
    is_active TINYINT DEFAULT 1
);

CREATE TABLE product_images (
    image_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    file_name VARCHAR(100) NOT NULL,
    uploaded_at DATETIME DEFAULT NOW(),
    INDEX(product_id),
    CONSTRAINT fk_image_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE stock (
    stock_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT DEFAULT 0,
    INDEX(product_id),
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE stock_movements (
    movement_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    movement_code VARCHAR(50) UNIQUE NOT NULL,
    quantity_change INT NOT NULL,
    movement_date DATETIME DEFAULT NOW(),
    INDEX(product_id),
    CONSTRAINT fk_movement_product FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT NOT NULL,
    order_date DATETIME DEFAULT NOW(),
    date_needed DATE,
    status ENUM('Pending', 'Confirmed', 'Voided', 'Cancelled') DEFAULT 'Pending',
    discount_type ENUM('Percentage', 'Fixed', 'None') DEFAULT 'None',
    discount_value DECIMAL(10,2) DEFAULT 0,
    cash_tendered DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    change_amount DECIMAL(10,2) DEFAULT 0,
    INDEX(user_id),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE orderline (
    orderline_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    INDEX(order_id),
    INDEX(product_id),
    CONSTRAINT fk_orderline_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_orderline_product FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE product_reviews (
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    review TEXT NOT NULL,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW(),
    PRIMARY KEY (product_id, user_id),
    INDEX(user_id),
    CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
);

INSERT INTO users (username, email, password, role, full_name, contact_number, address, is_active) VALUES
('cconsole', 'cconsole@cconsole.test', 'admin123', 'Admin', 'C.Console Administrator', '09171234567', 'Taguig City', 1),
('jdelacruz', 'jdelacruz@cconsole.test', 'hello123', 'Customer', 'Juan Dela Cruz', '09170001234', 'Quezon City', 1),
('msantos', 'msantos@cconsole.test', 'HelloWorld', 'Customer', 'Maria Santos', '09170002345', 'Manila', 1),
('jreyes', 'jreyes@cconsole.test', 'guesspassword', 'Customer', 'Jose Reyes', '09170003456', 'Caloocan', 1),
('alim', 'alim@cconsole.test', 'pass_is_word', 'Customer', 'Ana Lim', '09170004567', 'Pasig', 1),
('mdizon', 'mdizon@cconsole.test', 'realpass1109', 'Customer', 'Mark Dizon', '09170005678', 'Makati', 1),
('lgarcia', 'lgarcia@cconsole.test', 'Hardpassword', 'Customer', 'Liza Garcia', '09170006789', 'Parañaque', 1),
('jcruz', 'jcruz@cconsole.test', 'CedricJuly', 'Customer', 'John Cruz', '09170007890', 'Laguna', 1),
('ebautista', 'ebautista@cconsole.test', 'Domingo11', 'Customer', 'Ella Bautista', '09170008901', 'Cavite', 1),
('mtorres', 'mtorres@cconsole.test', 'Nunez2006', 'Customer', 'Miguel Torres', '09170009012', 'Bulacan', 1),
('pmendoza', 'pmendoza@cconsole.test', 'mendopass', 'Customer', 'Paula Mendoza', '09170010123', 'Rizal', 1),
('kramos', 'kramos@cconsole.test', 'easypeazy', 'Customer', 'Kevin Ramos', '09170011234', 'Taguig', 1);

INSERT INTO products (game_code, name, game_studio, product_type, genre, unit_cost_price, unit_selling_price, min_stock_threshold, is_active) VALUES
('PS5001', 'God of War Ragnarok', 'Santa Monica Studio', 'PlayStation 5 Game', 'Action', 2200.00, 2899.00, 5, 1),
('PS5002', 'Marvel''s Spider-Man 2', 'Insomniac Games', 'PlayStation 5 Game', 'Action', 2300.00, 2999.00, 5, 1),
('PS5003', 'Demon''s Souls', 'Bluepoint Games', 'PlayStation 5 Game', 'Action', 1800.00, 2399.00, 5, 1),
('PS5004', 'Stellar Blade', 'Shift Up', 'PlayStation 5 Game', 'Action', 2000.00, 2699.00, 5, 1),
('PS5005', 'Devil May Cry 5 Special Edition', 'Capcom', 'PlayStation 5 Game', 'Action', 1400.00, 1899.00, 5, 1),
('PS5006', 'Final Fantasy XVI', 'Square Enix', 'PlayStation 5 Game', 'Action', 2200.00, 2899.00, 5, 1),
('PS5007', 'Armored Core VI: Fires of Rubicon', 'FromSoftware', 'PlayStation 5 Game', 'Action', 1800.00, 2399.00, 5, 1),
('PS5008', 'Ninja Gaiden 4', 'Team Ninja', 'PlayStation 5 Game', 'Action', 2200.00, 2899.00, 5, 1),
('PS5009', 'Returnal', 'Housemarque', 'PlayStation 5 Game', 'Action', 1500.00, 1999.00, 5, 1),
('PS5010', 'Sifu', 'Sloclap', 'PlayStation 5 Game', 'Action', 1100.00, 1499.00, 5, 1),
('PS5011', 'Rise of the Ronin', 'Team Ninja', 'PlayStation 5 Game', 'Action', 2000.00, 2699.00, 5, 1),
('PS5012', 'Wo Long: Fallen Dynasty', 'Team Ninja', 'PlayStation 5 Game', 'Action', 1400.00, 1899.00, 5, 1),
('PS5013', 'Ghostrunner 2', 'One More Level', 'PlayStation 5 Game', 'Action', 1300.00, 1799.00, 5, 1),
('PS5014', 'The First Berserker: Khazan', 'Neople', 'PlayStation 5 Game', 'Action', 2000.00, 2699.00, 5, 1),
('PS5015', 'Warhammer 40,000: Space Marine 2', 'Saber Interactive', 'PlayStation 5 Game', 'Action', 1900.00, 2599.00, 5, 1),

('PS5016', 'Astro Bot', 'Team Asobi', 'PlayStation 5 Game', 'Adventure', 2000.00, 2699.00, 5, 1),
('PS5017', 'Uncharted: Legacy of Thieves Collection', 'Naughty Dog', 'PlayStation 5 Game', 'Adventure', 1400.00, 1899.00, 5, 1),
('PS5018', 'Ratchet & Clank: Rift Apart', 'Insomniac Games', 'PlayStation 5 Game', 'Adventure', 1800.00, 2399.00, 5, 1),
('PS5019', 'Kena: Bridge of Spirits', 'Ember Lab', 'PlayStation 5 Game', 'Adventure', 1200.00, 1699.00, 5, 1),
('PS5020', 'Sackboy: A Big Adventure', 'Sumo Digital', 'PlayStation 5 Game', 'Adventure', 1300.00, 1799.00, 5, 1),
('PS5021', 'Prince of Persia: The Lost Crown', 'Ubisoft', 'PlayStation 5 Game', 'Adventure', 1400.00, 1899.00, 5, 1),
('PS5022', 'Hogwarts Legacy', 'Avalanche Software', 'PlayStation 5 Game', 'Adventure', 1800.00, 2399.00, 5, 1),
('PS5023', 'Star Wars Jedi: Survivor', 'Respawn Entertainment', 'PlayStation 5 Game', 'Adventure', 1700.00, 2299.00, 5, 1),
('PS5024', 'Indiana Jones and the Great Circle', 'MachineGames', 'PlayStation 5 Game', 'Adventure', 2100.00, 2799.00, 5, 1),
('PS5025', 'Tchia', 'Awaceb', 'PlayStation 5 Game', 'Adventure', 1000.00, 1399.00, 5, 1),
('PS5026', 'Tomb Raider I-III Remastered', 'Aspyr', 'PlayStation 5 Game', 'Adventure', 900.00, 1299.00, 5, 1),
('PS5027', 'Life Is Strange: Double Exposure', 'Deck Nine', 'PlayStation 5 Game', 'Adventure', 1600.00, 2199.00, 5, 1),
('PS5028', 'The Plucky Squire', 'All Possible Futures', 'PlayStation 5 Game', 'Adventure', 1100.00, 1499.00, 5, 1),
('PS5029', 'Stray', 'BlueTwelve Studio', 'PlayStation 5 Game', 'Adventure', 1000.00, 1399.00, 5, 1),
('PS5030', 'Lost Records: Bloom & Rage', 'DON''T NOD', 'PlayStation 5 Game', 'Adventure', 1500.00, 1999.00, 5, 1),

('PS5031', 'The Last of Us Part I', 'Naughty Dog', 'PlayStation 5 Game', 'Survival', 1800.00, 2399.00, 5, 1),
('PS5032', 'Resident Evil 4', 'Capcom', 'PlayStation 5 Game', 'Survival', 1700.00, 2299.00, 5, 1),
('PS5033', 'Resident Evil Village', 'Capcom', 'PlayStation 5 Game', 'Survival', 1400.00, 1899.00, 5, 1),
('PS5034', 'Dead Space', 'Motive Studio', 'PlayStation 5 Game', 'Survival', 1500.00, 1999.00, 5, 1),
('PS5035', 'Alan Wake 2', 'Remedy Entertainment', 'PlayStation 5 Game', 'Survival', 1800.00, 2399.00, 5, 1),
('PS5036', 'Silent Hill 2', 'Bloober Team', 'PlayStation 5 Game', 'Survival', 1900.00, 2599.00, 5, 1),
('PS5037', 'The Callisto Protocol', 'Striking Distance Studios', 'PlayStation 5 Game', 'Survival', 1200.00, 1699.00, 5, 1),
('PS5038', 'Days Gone Remastered', 'Bend Studio', 'PlayStation 5 Game', 'Survival', 1500.00, 1999.00, 5, 1),
('PS5039', 'Dying Light 2: Stay Human', 'Techland', 'PlayStation 5 Game', 'Survival', 1400.00, 1899.00, 5, 1),
('PS5040', 'The Forest', 'Endnight Games', 'PlayStation 5 Game', 'Survival', 1000.00, 1399.00, 5, 1),
('PS5041', 'Green Hell', 'Creepy Jar', 'PlayStation 5 Game', 'Survival', 900.00, 1299.00, 5, 1),
('PS5042', '7 Days to Die', 'The Fun Pimps', 'PlayStation 5 Game', 'Survival', 1100.00, 1499.00, 5, 1),
('PS5043', 'The Long Dark', 'Hinterland Studio', 'PlayStation 5 Game', 'Survival', 900.00, 1299.00, 5, 1),
('PS5044', 'Subnautica: Below Zero', 'Unknown Worlds Entertainment', 'PlayStation 5 Game', 'Survival', 1000.00, 1399.00, 5, 1),
('PS5045', 'Pacific Drive', 'Ironwood Studios', 'PlayStation 5 Game', 'Survival', 1400.00, 1899.00, 5, 1),

('PS5046', 'Overcooked! All You Can Eat', 'Team17', 'PlayStation 5 Game', 'Casual', 1100.00, 1499.00, 5, 1),
('PS5047', 'PowerWash Simulator', 'FuturLab', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 1),
('PS5048', 'Unpacking', 'Witch Beam', 'PlayStation 5 Game', 'Casual', 700.00, 999.00, 5, 1),
('PS5049', 'Moving Out 2', 'SMG Studio', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 1),
('PS5050', 'Human: Fall Flat', 'No Brakes Games', 'PlayStation 5 Game', 'Casual', 800.00, 1099.00, 5, 1),
('PS5051', 'Untitled Goose Game', 'House House', 'PlayStation 5 Game', 'Casual', 700.00, 999.00, 5, 1),
('PS5052', 'The Sims 4', 'Maxis', 'PlayStation 5 Game', 'Casual', 800.00, 1099.00, 5, 1),
('PS5053', 'Tetris Effect: Connected', 'Monstars', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 1),
('PS5054', 'Disney Dreamlight Valley', 'Gameloft', 'PlayStation 5 Game', 'Casual', 1200.00, 1699.00, 5, 1),
('PS5055', 'Stardew Valley', 'ConcernedApe', 'PlayStation 5 Game', 'Casual', 700.00, 999.00, 5, 1),
('PS5056', 'Minecraft', 'Mojang Studios', 'PlayStation 5 Game', 'Casual', 1000.00, 1399.00, 5, 1),
('PS5057', 'Fall Guys', 'Mediatonic', 'PlayStation 5 Game', 'Casual', 500.00, 799.00, 5, 1),
('PS5058', 'Humanity', 'tha ltd.', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 1),
('PS5059', 'Puyo Puyo Tetris 2', 'Sega', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 1),
('PS5060', 'Cat Quest III', 'The Gentlebros', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 1),

('PS5061', 'Mortal Kombat 1', 'NetherRealm Studios', 'PlayStation 5 Game', 'Violence', 1800.00, 2399.00, 5, 1),
('PS5062', 'Mortal Kombat 11 Ultimate', 'NetherRealm Studios', 'PlayStation 5 Game', 'Violence', 1300.00, 1799.00, 5, 1),
('PS5063', 'The Last of Us Part II Remastered', 'Naughty Dog', 'PlayStation 5 Game', 'Violence', 1800.00, 2399.00, 5, 1),
('PS5064', 'DOOM Eternal', 'id Software', 'PlayStation 5 Game', 'Violence', 1200.00, 1699.00, 5, 1),
('PS5065', 'Dead Island 2', 'Dambuster Studios', 'PlayStation 5 Game', 'Violence', 1400.00, 1899.00, 5, 1),
('PS5066', 'The Evil Within 2', 'Tango Gameworks', 'PlayStation 5 Game', 'Violence', 1100.00, 1499.00, 5, 1),
('PS5067', 'Wolfenstein II: The New Colossus', 'MachineGames', 'PlayStation 5 Game', 'Violence', 1000.00, 1399.00, 5, 1),
('PS5068', 'Sniper Elite 5', 'Rebellion Developments', 'PlayStation 5 Game', 'Violence', 1300.00, 1799.00, 5, 1),
('PS5069', 'The Quarry', 'Supermassive Games', 'PlayStation 5 Game', 'Violence', 1400.00, 1899.00, 5, 1),
('PS5070', 'Until Dawn', 'Ballistic Moon', 'PlayStation 5 Game', 'Violence', 1500.00, 1999.00, 5, 1),
('PS5071', 'Manhunt', 'Rockstar Games', 'PlayStation 5 Game', 'Violence', 900.00, 1299.00, 5, 1),
('PS5072', 'Grand Theft Auto V', 'Rockstar Games', 'PlayStation 5 Game', 'Violence', 1400.00, 1899.00, 5, 1),
('PS5073', 'Red Dead Redemption 2', 'Rockstar Games', 'PlayStation 5 Game', 'Violence', 1500.00, 1999.00, 5, 1),
('PS5074', 'Cyberpunk 2077: Ultimate Edition', 'CD Projekt Red', 'PlayStation 5 Game', 'Violence', 1800.00, 2399.00, 5, 1),
('PS5075', 'Call of Duty: Modern Warfare III', 'Activision', 'PlayStation 5 Game', 'Violence', 1900.00, 2599.00, 5, 1);

INSERT INTO stock (product_id, quantity) VALUES
(1, 25),
(2, 18),
(3, 14),
(4, 30),
(5, 12),
(6, 20),
(7, 16),
(8, 22),
(9, 11),
(10, 17);

INSERT INTO stock_movements (product_id, movement_code, quantity_change, movement_date) VALUES
(1, 'MOV0001', 20, '2026-06-01 10:00:00'),
(2, 'MOV0002', -3, '2026-06-02 12:15:00'),
(3, 'MOV0003', 10, '2026-06-03 09:30:00'),
(4, 'MOV0004', -5, '2026-06-03 14:10:00'),
(5, 'MOV0005', 8, '2026-06-04 11:45:00'),
(6, 'MOV0006', -2, '2026-06-05 16:20:00'),
(7, 'MOV0007', 12, '2026-06-06 08:00:00'),
(8, 'MOV0008', -4, '2026-06-06 13:30:00'),
(9, 'MOV0009', 6, '2026-06-07 10:25:00'),
(10, 'MOV0010', -3, '2026-06-07 17:40:00'),
(1, 'MOV0011', 5, '2026-06-08 09:10:00'),
(2, 'MOV0012', -2, '2026-06-08 15:00:00'),
(3, 'MOV0013', 7, '2026-06-09 10:50:00'),
(4, 'MOV0014', -3, '2026-06-09 18:20:00'),
(5, 'MOV0015', 5, '2026-06-10 11:05:00'),
(6, 'MOV0016', -4, '2026-06-10 19:30:00'),
(7, 'MOV0017', 8, '2026-06-11 09:45:00'),
(8, 'MOV0018', -2, '2026-06-11 14:55:00'),
(9, 'MOV0019', 4, '2026-06-12 10:15:00'),
(10, 'MOV0020', -5, '2026-06-12 16:40:00');

INSERT INTO orders (user_id, date_needed, status, discount_type, discount_value, cash_tendered, total_amount, change_amount) VALUES
(2, '2026-06-25', 'Confirmed', 'None', 0, 2899.00, 2899.00, 0),
(3, '2026-06-25', 'Pending', 'Percentage', 10, 2699.00, 2429.10, 269.90),
(4, '2026-06-26', 'Confirmed', 'Fixed', 50, 2449.00, 2399.00, 50.00),
(5, '2026-06-26', 'Cancelled', 'None', 0, 0, 0, 0),
(6, '2026-06-27', 'Confirmed', 'None', 0, 2999.00, 2999.00, 0),
(7, '2026-06-27', 'Pending', 'Percentage', 5, 2399.00, 2279.05, 119.95),
(8, '2026-06-28', 'Confirmed', 'None', 0, 1899.00, 1899.00, 0),
(9, '2026-06-28', 'Voided', 'None', 0, 0, 0, 0),
(10, '2026-06-29', 'Confirmed', 'Fixed', 100, 2699.00, 2499.00, 200.00),
(11, '2026-06-29', 'Pending', 'None', 0, 2599.00, 2599.00, 0);

INSERT INTO orderline (order_id, product_id, quantity, unit_price) VALUES
(1, 1, 1, 2899.00),
(2, 2, 1, 2699.00),
(3, 5, 1, 2399.00),
(4, 4, 1, 1499.00),
(5, 6, 1, 2999.00),
(6, 3, 1, 2399.00),
(7, 7, 1, 1899.00),
(8, 8, 1, 2299.00),
(9, 2, 1, 2699.00),
(10, 9, 1, 2599.00);

INSERT INTO product_reviews (product_id, user_id, rating, review, created_at, updated_at) VALUES
(1, 2, 5, 'Amazing story and combat. Kratos and Atreus are better than ever.', '2026-07-01 18:30:00', '2026-07-01 18:30:00'),
(5, 4, 4, 'Stylish and fast. The combo system is a blast once it clicks.', '2026-07-02 20:10:00', '2026-07-02 20:10:00'),
(6, 6, 3, 'Great visuals but the pacing drags in the middle chapters.', '2026-07-03 15:45:00', '2026-07-03 15:45:00');
