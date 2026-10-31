DROP DATABASE IF EXISTS `C.Console`;

CREATE DATABASE `C.Console` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `C.Console`;

DROP VIEW IF EXISTS low_stock_report;
DROP VIEW IF EXISTS order_summary;
DROP VIEW IF EXISTS order_transaction_details;

DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS review_images;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS orderline;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stock;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS user_sessions;
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

CREATE TABLE user_sessions (
    session_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX(user_id),
    CONSTRAINT fk_session_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    game_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    game_studio VARCHAR(100),
    product_type ENUM('PlayStation 5 Game') NOT NULL DEFAULT 'PlayStation 5 Game',
    genre ENUM('Action', 'Adventure', 'Survival', 'Casual', 'Violence'),
    description TEXT,
    unit_cost_price DECIMAL(10,2) NOT NULL,
    unit_selling_price DECIMAL(10,2) NOT NULL,
    min_stock_threshold INT DEFAULT 5,
    is_active TINYINT DEFAULT 1,
    is_archived TINYINT NOT NULL DEFAULT 0,
    archived_at DATETIME NULL
);

CREATE TABLE product_images (
    image_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    file_name VARCHAR(100) NOT NULL,
    is_primary TINYINT NOT NULL DEFAULT 0,
    uploaded_at DATETIME DEFAULT NOW(),
    INDEX(product_id),
    CONSTRAINT fk_image_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

CREATE TABLE stock (
    stock_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    CONSTRAINT uq_stock_product UNIQUE (product_id),
    CONSTRAINT chk_stock_quantity CHECK (quantity >= 0),
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE stock_movements (
    movement_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    movement_code VARCHAR(50) UNIQUE NOT NULL,
    movement_type ENUM('Stock-in', 'Adjustment', 'Sale', 'Restore') NOT NULL DEFAULT 'Adjustment',
    quantity_change INT NOT NULL,
    reason VARCHAR(255),
    created_by INT NULL,
    movement_date DATETIME DEFAULT NOW(),
    INDEX(product_id),
    INDEX(movement_type, movement_date),
    INDEX(created_by),
    CONSTRAINT fk_movement_product FOREIGN KEY (product_id) REFERENCES products(product_id),
    CONSTRAINT fk_movement_user FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
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
    INDEX idx_orders_order_date (order_date),
    INDEX idx_orders_status_date (status, order_date),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE orderline (
    orderline_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    INDEX(product_id),
    CONSTRAINT uq_orderline_order_product UNIQUE (order_id, product_id),
    CONSTRAINT chk_orderline_quantity CHECK (quantity > 0),
    CONSTRAINT fk_orderline_order FOREIGN KEY (order_id) REFERENCES orders(order_id),
    CONSTRAINT fk_orderline_product FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE product_reviews (
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    title VARCHAR(100) NOT NULL DEFAULT '',
    review TEXT NOT NULL,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW(),
    PRIMARY KEY (product_id, user_id),
    INDEX(user_id),
    CONSTRAINT fk_review_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
);

CREATE TABLE review_images (
    image_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    file_name VARCHAR(100) NOT NULL,
    uploaded_at DATETIME DEFAULT NOW(),
    INDEX(product_id, user_id),
    CONSTRAINT fk_review_image_review FOREIGN KEY (product_id, user_id) REFERENCES product_reviews(product_id, user_id) ON DELETE CASCADE
);

CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(created_at)
);

INSERT INTO users (username, email, password, role, full_name, contact_number, address, is_active) VALUES
('cconsole', 'cconsole@cconsole.test', 'admin123', 'Admin', 'C.Console Administrator', '09171234567', 'Taguig City', 1),
('jdelacruz', 'juan.delacruz@gmail.com', 'hello123', 'Customer', 'Juan Dela Cruz', '09170001234', 'Quezon City', 1),
('msantos', 'maria.santos@gmail.com', 'HelloWorld', 'Customer', 'Maria Santos', '09170002345', 'Manila', 1),
('jreyes', 'jose.reyes@gmail.com', 'guesspassword', 'Customer', 'Jose Reyes', '09170003456', 'Caloocan', 1),
('alim', 'ana.lim@gmail.com', 'pass_is_word', 'Customer', 'Ana Lim', '09170004567', 'Pasig', 1),
('mdizon', 'mark.dizon@gmail.com', 'realpass1109', 'Customer', 'Mark Dizon', '09170005678', 'Makati', 1),
('lgarcia', 'liza.garcia@gmail.com', 'Hardpassword', 'Customer', 'Liza Garcia', '09170006789', 'Parañaque', 1),
('jcruz', 'john.cruz@gmail.com', 'CedricJuly', 'Customer', 'John Cruz', '09170007890', 'Laguna', 1),
('ebautista', 'ella.bautista@gmail.com', 'Domingo11', 'Customer', 'Ella Bautista', '09170008901', 'Cavite', 1),
('mtorres', 'miguel.torres@gmail.com', 'Nunez2006', 'Customer', 'Miguel Torres', '09170009012', 'Bulacan', 1),
('pmendoza', 'paula.mendoza@gmail.com', 'mendopass', 'Customer', 'Paula Mendoza', '09170010123', 'Rizal', 1),
('kramos', 'kevin.ramos@gmail.com', 'easypeazy', 'Customer', 'Kevin Ramos', '09170011234', 'Taguig', 1);

INSERT INTO products (game_code, name, game_studio, product_type, genre, unit_cost_price, unit_selling_price, min_stock_threshold, description, is_active) VALUES
('PS5001', 'God of War Ragnarok', 'Santa Monica Studio', 'PlayStation 5 Game', 'Action', 2200.00, 2899.00, 5, 'An epic Norse mythology adventure following Kratos and Atreus across the Nine Realms as they confront gods, monsters, and their own destinies in the build-up to the battle of Ragnarök.', 1),
('PS5002', 'Marvel''s Spider-Man 2', 'Insomniac Games', 'PlayStation 5 Game', 'Action', 2300.00, 2999.00, 5, 'Swing through Marvel''s New York as both Peter Parker and Miles Morales, utilizing new symbiote abilities and web wings to face formidable villains like Kraven the Hunter and Venom.', 1),
('PS5003', 'Demon''s Souls', 'Bluepoint Games', 'PlayStation 5 Game', 'Action', 1800.00, 2399.00, 5, 'A breathtaking remake of the dark fantasy action classic. Venture into the fog-shrouded kingdom of Boletaria to slay terrifying demons and master demanding melee and magical combat.', 1),
('PS5004', 'Stellar Blade', 'Shift Up', 'PlayStation 5 Game', 'Action', 2000.00, 2699.00, 5, 'A stylish sci-fi action game following warrior Eve as she battles mysterious alien creatures called Naytibas on a desolate Earth with high-speed sword combos and counters.', 1),
('PS5005', 'Devil May Cry 5 Special Edition', 'Capcom', 'PlayStation 5 Game', 'Action', 1400.00, 1899.00, 5, 'Stylish, fast-paced hack-and-slash action featuring Nero, Dante, V, and Vergil as they unleash high-combo attacks, demonic transformations, and flashy swordplay against invading demonic forces.', 1),
('PS5006', 'Final Fantasy XVI', 'Square Enix', 'PlayStation 5 Game', 'Action', 2200.00, 2899.00, 5, 'A dark fantasy action RPG telling the tragic story of Clive Rosfield, featuring fast-paced real-time combat, devastating Eikonic powers, and colossal battles between giant summoned beasts.', 1),
('PS5007', 'Armored Core VI: Fires of Rubicon', 'FromSoftware', 'PlayStation 5 Game', 'Action', 1800.00, 2399.00, 5, 'High-octane mech action game where players assemble and customize powerful armored units to engage in fast-paced, omnidirectional combat across massive, sci-fi battlefields on the remote planet Rubicon 3.', 1),
('PS5008', 'Ninja Gaiden 4', 'Team Ninja', 'PlayStation 5 Game', 'Action', 2200.00, 2899.00, 5, 'A modern iteration of high-difficulty ninja action combat, featuring lightning-fast swordplay, lethal Ninpo magic, acrobatic movement, and brutal enemy encounters.', 1),
('PS5009', 'Returnal', 'Housemarque', 'PlayStation 5 Game', 'Action', 1500.00, 1999.00, 5, 'A challenging sci-fi roguelike third-person shooter set on a changing alien planet, where astronaut Selene revives after every death to piece together her memories and escape the cycle.', 1),
('PS5010', 'Sifu', 'Sloclap', 'PlayStation 5 Game', 'Action', 1100.00, 1499.00, 5, 'A martial arts action game following a Kung Fu student seeking revenge, featuring authentic tactical combat and a unique magical aging mechanic upon every death.', 1),
('PS5011', 'Rise of the Ronin', 'Team Ninja', 'PlayStation 5 Game', 'Action', 2000.00, 2699.00, 5, 'An open-world action RPG set in late 19th-century Bakumatsu Japan, featuring deep sword combat, gliders, grappling hooks, and player choices that shape historical outcomes.', 1),
('PS5012', 'Wo Long: Fallen Dynasty', 'Team Ninja', 'PlayStation 5 Game', 'Action', 1400.00, 1899.00, 5, 'A dark fantasy action RPG set in the Later Han Dynasty where a nameless militia soldier uses Chinese martial arts and swordplay to combat demons and warlords.', 1),
('PS5013', 'Ghostrunner 2', 'One More Level', 'PlayStation 5 Game', 'Action', 1300.00, 1799.00, 5, 'A blistering cyber-ninja action game where one hit means death. Master lightning-fast parkour, motorcycle segments, and lethal katana combat in a dystopian cyberpunk landscape.', 1),
('PS5014', 'The First Berserker: Khazan', 'Neople', 'PlayStation 5 Game', 'Action', 2000.00, 2699.00, 5, 'A dark fantasy action RPG telling the story of general Khazan, featuring deep, punishing combat, brutal execution moves, and cell-shaded dark artwork.', 1),
('PS5015', 'Warhammer 40,000: Space Marine 2', 'Saber Interactive', 'PlayStation 5 Game', 'Action', 1900.00, 2599.00, 5, 'Step into the power armor of Captain Titus and obliterate relentless Tyranid swarms in brutal third-person shooter and hack-and-slash action across distant planets.', 1),

('PS5016', 'Astro Bot', 'Team Asobi', 'PlayStation 5 Game', 'Adventure', 2000.00, 2699.00, 5, 'A vibrant, joy-filled 3D platformer where players guide Astro across dozens of creative planets, utilizing unique power-ups and rescuing iconic PlayStation characters in a celebration of gaming history.', 1),
('PS5017', 'Uncharted: Legacy of Thieves Collection', 'Naughty Dog', 'PlayStation 5 Game', 'Adventure', 1400.00, 1899.00, 5, 'Includes Uncharted 4: A Thief''s End and Uncharted: The Lost Legacy, offering cinematic treasure-hunting adventures packed with gunplay, climbing, and epic set pieces.', 1),
('PS5018', 'Ratchet & Clank: Rift Apart', 'Insomniac Games', 'PlayStation 5 Game', 'Adventure', 1800.00, 2399.00, 5, 'An interdimensional action-adventure platformer featuring wild futuristic weaponry, seamless portal jumps, and spectacular sci-fi worlds as Ratchet and Rivet save the multiverse.', 1),
('PS5019', 'Kena: Bridge of Spirits', 'Ember Lab', 'PlayStation 5 Game', 'Adventure', 1200.00, 1699.00, 5, 'A charming action-adventure combining fast-paced combat with exploration, as Spirit Guide Kena restores a corrupted forest with the help of tiny, cute magical companions called the Rot.', 1),
('PS5020', 'Sackboy: A Big Adventure', 'Sumo Digital', 'PlayStation 5 Game', 'Adventure', 1300.00, 1799.00, 5, 'A colorful 3D platforming adventure full of charm, cooperative challenges, expressive customization, and rhythm-based levels set in Craftworld.', 1),
('PS5021', 'Prince of Persia: The Lost Crown', 'Ubisoft', 'PlayStation 5 Game', 'Adventure', 1400.00, 1899.00, 5, 'A stylish 2.5D action-adventure Metroidvania featuring time powers, fluid platforming, sword-based aerial combos, and mythological puzzles set on Mount Qaf.', 1),
('PS5022', 'Hogwarts Legacy', 'Avalanche Software', 'PlayStation 5 Game', 'Adventure', 1800.00, 2399.00, 5, 'An immersive open-world action RPG set in the 1800s Wizarding World. Attend classes, brew potions, master spellcasting, customize your character, and uncover ancient magical secrets at Hogwarts.', 1),
('PS5023', 'Star Wars Jedi: Survivor', 'Respawn Entertainment', 'PlayStation 5 Game', 'Adventure', 1700.00, 2299.00, 5, 'Cal Kestis continues his journey as a powerful Jedi Knight, mastering new Lightsaber combat stances, Force abilities, and exploring vast, uncharted planets while fleeing the Empire.', 1),
('PS5024', 'Indiana Jones and the Great Circle', 'MachineGames', 'PlayStation 5 Game', 'Adventure', 2100.00, 2799.00, 5, 'A cinematic first-person action-adventure following the iconic archaeologist on a globe-trotting journey filled with whip navigation, puzzle-solving, stealth, and hand-to-hand combat against rival forces.', 1),
('PS5025', 'Tchia', 'Awaceb', 'PlayStation 5 Game', 'Adventure', 1000.00, 1399.00, 5, 'A tropical open-world adventure inspired by New Caledonia, where players soul-jump into animals and physics objects, play ukulele, and explore beautiful islands.', 1),
('PS5026', 'Tomb Raider I-III Remastered', 'Aspyr', 'PlayStation 5 Game', 'Adventure', 900.00, 1299.00, 5, 'The definitive remaster of Lara Croft''s original three globe-trotting adventures, complete with updated graphics, original classic controls, and all expansion packs.', 1),
('PS5027', 'Life Is Strange: Double Exposure', 'Deck Nine', 'PlayStation 5 Game', 'Adventure', 1600.00, 2199.00, 5, 'A narrative adventure starring Max Caulfield, who discovers the ability to shift between two parallel timelines to solve and prevent a tragic murder mystery.', 1),
('PS5028', 'The Plucky Squire', 'All Possible Futures', 'PlayStation 5 Game', 'Adventure', 1100.00, 1499.00, 5, 'A charming action-adventure game following storybook hero Jot as he switches between 2D illustrated book pages and a full 3D room world to solve puzzles and fight foes.', 1),
('PS5029', 'Stray', 'BlueTwelve Studio', 'PlayStation 5 Game', 'Adventure', 1000.00, 1399.00, 5, 'A third-person cat adventure set inside the detailed, neon-lit alleys and decaying underbelly of a forgotten cybercity populated by quirky androids.', 1),
('PS5030', 'Lost Records: Bloom & Rage', 'DON''T NOD', 'PlayStation 5 Game', 'Adventure', 1500.00, 1999.00, 5, 'A story-driven adventure about high school friends in the 1990s reunited decades later to confront a mysterious supernatural secret they swore never to speak of again.', 1),

('PS5031', 'The Last of Us Part I', 'Naughty Dog', 'PlayStation 5 Game', 'Survival', 1800.00, 2399.00, 5, 'A definitive remake of the acclaimed post-apocalyptic narrative following Joel and teenager Ellie on a dangerous journey across a infected and ruthless America.', 1),
('PS5032', 'Resident Evil 4', 'Capcom', 'PlayStation 5 Game', 'Survival', 1700.00, 2299.00, 5, 'A modernized remake of the survival horror masterpiece. Agent Leon S. Kennedy travels to a secluded European village to rescue the President''s daughter from a terrifying cult.', 1),
('PS5033', 'Resident Evil Village', 'Capcom', 'PlayStation 5 Game', 'Survival', 1400.00, 1899.00, 5, 'First-person survival horror following Ethan Winters into a mysterious European village ruled by gothic lords, monstrous lycans, and terrifying supernatural forces.', 1),
('PS5034', 'Dead Space', 'Motive Studio', 'PlayStation 5 Game', 'Survival', 1500.00, 1999.00, 5, 'A ground-up remake of the sci-fi survival horror classic. Play as engineer Isaac Clarke as he explores the nightmare-ridden USG Ishimura, using mining tools to dismember terrifying Necromorph creatures.', 1),
('PS5035', 'Alan Wake 2', 'Remedy Entertainment', 'PlayStation 5 Game', 'Survival', 1800.00, 2399.00, 5, 'An intense psychological horror game following FBI agent Saga Anderson and trapped writer Alan Wake as they navigate interconnected nightmares, uncover cult mysteries, and fight dark forces lurking in the Pacific Northwest.', 1),
('PS5036', 'Silent Hill 2', 'Bloober Team', 'PlayStation 5 Game', 'Survival', 1900.00, 2599.00, 5, 'A psychological horror remake following James Sunderland as he explores the fog-choked town of Silent Hill after receiving a mysterious letter from his deceased wife.', 1),
('PS5037', 'The Callisto Protocol', 'Striking Distance Studios', 'PlayStation 5 Game', 'Survival', 1200.00, 1699.00, 5, 'A story-driven sci-fi survival horror game set on Jupiter''s moon Callisto, where inmate Jacob Lee uses visceral melee combat and gravity weapons against mutated human monstrosities.', 1),
('PS5038', 'Days Gone Remastered', 'Bend Studio', 'PlayStation 5 Game', 'Survival', 1500.00, 1999.00, 5, 'Navigate a desolate post-apocalyptic Pacific Northwest as outlaw biker Deacon St. John, fighting off massive hordes of relentless Freakers and hostile human factions while searching for hope on your customizable motorcycle.', 1),
('PS5039', 'Dying Light 2: Stay Human', 'Techland', 'PlayStation 5 Game', 'Survival', 1400.00, 1899.00, 5, 'An open-world action RPG combining fluid parkour movement with visceral melee combat in a city overrun by infected, where choices alter the city''s power structures and narrative outcome.', 1),
('PS5040', 'The Forest', 'Endnight Games', 'PlayStation 5 Game', 'Survival', 1000.00, 1399.00, 5, 'A harrowing survival horror game where a plane crash survivor must chop trees, build shelters, manage hunger, and fight off cannibalistic mutants in a dense forest island.', 1),
('PS5041', 'Green Hell', 'Creepy Jar', 'PlayStation 5 Game', 'Survival', 900.00, 1299.00, 5, 'A grueling Amazonian rainforest survival simulator requiring real-world survival techniques, psychological management, shelter building, foraging, and fighting off wild animals and tropical illnesses.', 1),
('PS5042', '7 Days to Die', 'The Fun Pimps', 'PlayStation 5 Game', 'Survival', 1100.00, 1499.00, 5, 'Set in a brutal post-apocalyptic world, this open-world sandbox game combines first-person shooter action, survival horror, tower defense, and role-playing elements as you scavenge, craft, build, and fight to stay alive against relentless zombie hordes.', 1),
('PS5043', 'The Long Dark', 'Hinterland Studio', 'PlayStation 5 Game', 'Survival', 900.00, 1299.00, 5, 'A thoughtful, quiet survival game where players navigate a frozen Canadian wilderness after a geomagnetic disaster without zombies—just cold, wolves, and natural threats.', 1),
('PS5044', 'Subnautica: Below Zero', 'Unknown Worlds Entertainment', 'PlayStation 5 Game', 'Survival', 1000.00, 1399.00, 5, 'Dive into an icy underwater adventure on planet 4546B, crafting equipment, building bases, and surviving frostbite and alien marine predators to uncover research secrets.', 1),
('PS5045', 'Pacific Drive', 'Ironwood Studios', 'PlayStation 5 Game', 'Survival', 1400.00, 1899.00, 5, 'A unique run-based driving survival game where your station wagon is your only lifeline while navigating surreal, hazardous anomaly zones in the Olympic Exclusion Zone.', 1),

('PS5046', 'Overcooked! All You Can Eat', 'Team17', 'PlayStation 5 Game', 'Casual', 1100.00, 1499.00, 5, 'The ultimate edition of chaotic culinary co-op gaming, bringing together every level from Overcooked! and Overcooked! 2 with remastered 4K graphics and cross-platform multiplayer.', 1),
('PS5047', 'PowerWash Simulator', 'FuturLab', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 'A relaxing simulation game where players clean dirty vehicles, houses, and public spaces using high-pressure water streams, unlocking tools and satisfying clean transformations.', 1),
('PS5048', 'Unpacking', 'Witch Beam', 'PlayStation 5 Game', 'Casual', 700.00, 999.00, 5, 'A zen puzzle game about the familiar experience of pulling possessions out of boxes and fitting them into a new room while discovering a character''s life story through context.', 1),
('PS5049', 'Moving Out 2', 'SMG Studio', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 'A wacky physics-based moving simulator where up to four players work as furniture arrangement technicians to navigate absurd obstacles and pack moving trucks efficiently.', 1),
('PS5050', 'Human: Fall Flat', 'No Brakes Games', 'PlayStation 5 Game', 'Casual', 800.00, 1099.00, 5, 'A hilarious, lighthearted physics platformer set in floating dreamscapes where wobbly characters work together or solo to solve environmental puzzles and reach the exit.', 1),
('PS5051', 'Untitled Goose Game', 'House House', 'PlayStation 5 Game', 'Casual', 700.00, 999.00, 5, 'A slapstick stealth sandbox game where you play as a horrible goose unleashed upon an unsuspecting village, ruining everyone''s day with honks and mischievous pranks.', 1),
('PS5052', 'The Sims 4', 'Maxis', 'PlayStation 5 Game', 'Casual', 800.00, 1099.00, 5, 'The iconic life simulation game that lets you create unique Sims, build their dream homes, guide their careers, build relationships, and experiment with endless life stories.', 1),
('PS5053', 'Tetris Effect: Connected', 'Monstars', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 'An extraordinary reinvented version of Tetris featuring stunning visual effects, dynamic music tied to grid rotations, and cooperative cross-platform multiplayer modes.', 1),
('PS5054', 'Disney Dreamlight Valley', 'Gameloft', 'PlayStation 5 Game', 'Casual', 1200.00, 1699.00, 5, 'A cozy life-simulation game where players restore a magical valley alongside beloved Disney and Pixar characters, completing quests, farming, crafting, decorating, and building lasting friendships.', 1),
('PS5055', 'Stardew Valley', 'ConcernedApe', 'PlayStation 5 Game', 'Casual', 700.00, 999.00, 5, 'An enchanting farming RPG where you inherit your grandfather''s old farm plot, raise crops and animals, mine, fish, craft, and become part of a welcoming rural community.', 1),
('PS5056', 'Minecraft', 'Mojang Studios', 'PlayStation 5 Game', 'Casual', 1000.00, 1399.00, 5, 'The ultimate open-world block-building sandbox game where players can mine resources, build elaborate structures, craft tools, and survive or create infinitely in single-player or multiplayer modes.', 1),
('PS5057', 'Fall Guys', 'Mediatonic', 'PlayStation 5 Game', 'Casual', 500.00, 799.00, 5, 'A chaotic, free-for-all party game where colorful jellybean competitors navigate physics-based obstacle courses, survival arenas, and mini-games to claim the ultimate winner''s crown.', 1),
('PS5058', 'Humanity', 'tha ltd.', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 'A visual puzzle game where you control a glowing Shiba Inu guiding massive crowds of people through complex stages, directing them to turn, jump, float, and fight in mind-bending tactical trials.', 1),
('PS5059', 'Puyo Puyo Tetris 2', 'Sega', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 'A vibrant crossover puzzle game combining Puyo Puyo color-matching and classic Tetris line-clearing with competitive skill battles and story mode adventures.', 1),
('PS5060', 'Cat Quest III', 'The Gentlebros', 'PlayStation 5 Game', 'Casual', 900.00, 1299.00, 5, 'A lighthearted 2.5D open-world action RPG set in a pirate-themed fantasy world where you sail the seas, battle dangerous Purribean rats, and search for legendary treasure alone or in co-op.', 1),

('PS5061', 'Mortal Kombat 1', 'NetherRealm Studios', 'PlayStation 5 Game', 'Violence', 1800.00, 2399.00, 5, 'A rebooted fighting game universe created by Fire God Liu Kang, featuring redesigned iconic fighters, brutal Fatality finishers, and the innovative Kameo Partner fighting mechanic.', 1),
('PS5062', 'Mortal Kombat 11 Ultimate', 'NetherRealm Studios', 'PlayStation 5 Game', 'Violence', 1300.00, 1799.00, 5, 'The definitive fighting experience with custom character variations, cinematic story modes, a massive roster of iconic combatants, and devastating Fatal Blow strikes.', 1),
('PS5063', 'The Last of Us Part II Remastered', 'Naughty Dog', 'PlayStation 5 Game', 'Violence', 1800.00, 2399.00, 5, 'An intense emotional tale of vengeance and consequence following Ellie and Abby, featuring refined gameplay mechanics, dynamic stealth, and the No Return roguelike mode.', 1),
('PS5064', 'DOOM Eternal', 'id Software', 'PlayStation 5 Game', 'Violence', 1200.00, 1699.00, 5, 'Unstoppable, fast-paced first-person shooter action where you step into the boots of the Doom Slayer to rip and tear through demon armies across Earth, Mars, and dimensional realms with a massive arsenal.', 1),
('PS5065', 'Dead Island 2', 'Dambuster Studios', 'PlayStation 5 Game', 'Violence', 1400.00, 1899.00, 5, 'A stylish, vibrant, and gore-filled first-person action RPG that lets players hack, slice, and shoot their way through a zombie-infested Los Angeles, utilizing an array of weapons and visceral elemental attacks.', 1),
('PS5066', 'The Evil Within 2', 'Tango Gameworks', 'PlayStation 5 Game', 'Violence', 1100.00, 1499.00, 5, 'Sebastian Castellanos must descend into a nightmarish, shifting world created by STEM technology to find his lost daughter, utilizing stealth, weapons, and resourcefulness.', 1),
('PS5067', 'Wolfenstein II: The New Colossus', 'MachineGames', 'PlayStation 5 Game', 'Violence', 1000.00, 1399.00, 5, 'Fight for freedom as BJ Blazkowicz in an alternate-history 1960s America occupied by oppressive regime forces, unleashing high-tech firearms and dual-wielding firepower.', 1),
('PS5068', 'Sniper Elite 5', 'Rebellion Developments', 'PlayStation 5 Game', 'Violence', 1300.00, 1799.00, 5, 'Tactical third-person marksman combat featuring expansive real-world WWII locations, enhanced kill-cam mechanics, advanced gun customization, and stealth gameplay.', 1),
('PS5069', 'The Quarry', 'Supermassive Games', 'PlayStation 5 Game', 'Violence', 1400.00, 1899.00, 5, 'A cinematic teen horror game where every decision shapes the survival or death of nine camp counselors hunted by terrifying creatures and hostile locals across one chaotic night.', 1),
('PS5070', 'Until Dawn', 'Ballistic Moon', 'PlayStation 5 Game', 'Violence', 1500.00, 1999.00, 5, 'A rebuilt horror classic where eight friends are trapped on a remote mountain lodge, and every choice can alter dynamic butterfly-effect storylines determining who survives.', 1),
('PS5071', 'Manhunt', 'Rockstar Games', 'PlayStation 5 Game', 'Violence', 900.00, 1299.00, 5, 'A dark, gritty psychological stealth-horror title where death row prisoner James Earl Cash must survive brutal execution trials directed by a shadowy snuff-film director.', 1),
('PS5072', 'Grand Theft Auto V', 'Rockstar Games', 'PlayStation 5 Game', 'Violence', 1400.00, 1899.00, 5, 'An expansive open-world action game featuring three interconnected criminals—Michael, Trevor, and Franklin—pulling off daring heists across the sprawling metropolis of Los Santos and Blaine County.', 1),
('PS5073', 'Red Dead Redemption 2', 'Rockstar Games', 'PlayStation 5 Game', 'Violence', 1500.00, 1999.00, 5, 'An epic tale of life in America’s unforgiving heartland, detailing outlaw Arthur Morgan and the Van der Linde gang as they flee lawmen and bounty hunters across a living open world.', 1),
('PS5074', 'Cyberpunk 2077: Ultimate Edition', 'CD Projekt Red', 'PlayStation 5 Game', 'Violence', 1800.00, 2399.00, 5, 'An immersive open-world action-adventure RPG set in the glittering, dangerous metropolis of Night City, complete with cybernetic augmentations, thrilling story quests, and the Phantom Liberty expansion.', 1),
('PS5075', 'Call of Duty: Modern Warfare III', 'Activision', 'PlayStation 5 Game', 'Violence', 1900.00, 2599.00, 5, 'A cinematic campaign and fast-paced multiplayer shooter where Captain Price and Task Force 141 face off against the ultimate threat, ultranationalist Vladimir Makarov, in global high-stakes combat operations.', 1);

INSERT INTO stock (product_id, quantity) VALUES
(1, 25),
(2, 25),
(3, 2),
(4, 25),
(5, 25),
(6, 25),
(7, 25),
(8, 25),
(9, 1),
(10, 25),
(11, 25),
(12, 25),
(13, 25),
(14, 25),
(15, 25),
(16, 0),
(17, 25),
(18, 25),
(19, 25),
(20, 25),
(21, 25),
(22, 4),
(23, 25),
(24, 25),
(25, 25),
(26, 25),
(27, 25),
(28, 25),
(29, 25),
(30, 25),
(31, 25),
(32, 25),
(33, 25),
(34, 25),
(35, 0),
(36, 25),
(37, 25),
(38, 0),
(39, 25),
(40, 25),
(41, 25),
(42, 25),
(43, 25),
(44, 25),
(45, 25),
(46, 25),
(47, 25),
(48, 25),
(49, 25),
(50, 25),
(51, 25),
(52, 5),
(53, 25),
(54, 25),
(55, 25),
(56, 25),
(57, 0),
(58, 25),
(59, 25),
(60, 0),
(61, 25),
(62, 25),
(63, 25),
(64, 3),
(65, 25),
(66, 25),
(67, 25),
(68, 25),
(69, 25),
(70, 25),
(71, 25),
(72, 25),
(73, 25),
(74, 0),
(75, 0);

INSERT INTO stock_movements (product_id, movement_code, movement_type, quantity_change, movement_date) VALUES
(1, 'MOV0001', 'Stock-in', 20, '2026-06-01 10:00:00'),
(2, 'MOV0002', 'Adjustment', -3, '2026-06-02 12:15:00'),
(3, 'MOV0003', 'Stock-in', 10, '2026-06-03 09:30:00'),
(4, 'MOV0004', 'Adjustment', -5, '2026-06-03 14:10:00'),
(5, 'MOV0005', 'Stock-in', 8, '2026-06-04 11:45:00'),
(6, 'MOV0006', 'Adjustment', -2, '2026-06-05 16:20:00'),
(7, 'MOV0007', 'Stock-in', 12, '2026-06-06 08:00:00'),
(8, 'MOV0008', 'Adjustment', -4, '2026-06-06 13:30:00'),
(9, 'MOV0009', 'Stock-in', 6, '2026-06-07 10:25:00'),
(10, 'MOV0010', 'Adjustment', -3, '2026-06-07 17:40:00'),
(1, 'MOV0011', 'Stock-in', 5, '2026-06-08 09:10:00'),
(2, 'MOV0012', 'Adjustment', -2, '2026-06-08 15:00:00'),
(3, 'MOV0013', 'Stock-in', 7, '2026-06-09 10:50:00'),
(4, 'MOV0014', 'Adjustment', -3, '2026-06-09 18:20:00'),
(5, 'MOV0015', 'Stock-in', 5, '2026-06-10 11:05:00'),
(6, 'MOV0016', 'Adjustment', -4, '2026-06-10 19:30:00'),
(7, 'MOV0017', 'Stock-in', 8, '2026-06-11 09:45:00'),
(8, 'MOV0018', 'Adjustment', -2, '2026-06-11 14:55:00'),
(9, 'MOV0019', 'Stock-in', 4, '2026-06-12 10:15:00'),
(10, 'MOV0020', 'Adjustment', -5, '2026-06-12 16:40:00');

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

INSERT INTO product_reviews (product_id, user_id, rating, title, review, created_at, updated_at) VALUES
(1, 2, 5, 'Better than ever', 'Amazing story and combat. Kratos and Atreus are better than ever.', '2026-07-01 18:30:00', '2026-07-01 18:30:00'),
(5, 4, 4, 'Fast and stylish', 'Stylish and fast. The combo system is a blast once it clicks.', '2026-07-02 20:10:00', '2026-07-02 20:10:00'),
(6, 6, 3, 'Pretty but slow', 'Great visuals but the pacing drags in the middle chapters.', '2026-07-03 15:45:00', '2026-07-03 15:45:00');


-- ---------------------------------------------------------------------------
-- VIEWS (order transaction details)
-- ---------------------------------------------------------------------------

-- One row per game on an order: order + customer + game + line amounts.
CREATE OR REPLACE VIEW order_transaction_details AS
SELECT
    o.order_id,
    o.order_date,
    o.date_needed,
    o.status,
    u.user_id,
    u.full_name AS customer_name,
    u.username,
    u.email,
    ol.orderline_id,
    p.product_id,
    p.game_code,
    p.name AS game_name,
    p.game_studio,
    ol.quantity,
    ol.unit_price,
    p.unit_cost_price,
    ol.quantity * ol.unit_price AS line_subtotal,
    o.discount_type,
    o.discount_value,
    o.total_amount,
    o.cash_tendered,
    o.change_amount
FROM orders o
INNER JOIN users u ON u.user_id = o.user_id
INNER JOIN orderline ol ON ol.order_id = o.order_id
INNER JOIN products p ON p.product_id = ol.product_id;

-- One row per order: totals worked out from its order lines.
CREATE OR REPLACE VIEW order_summary AS
SELECT
    o.order_id,
    o.order_date,
    o.date_needed,
    o.status,
    u.user_id,
    u.full_name AS customer_name,
    u.username,
    u.email,
    COALESCE(SUM(ol.quantity), 0) AS items,
    COALESCE(SUM(ol.quantity * ol.unit_price), 0) AS subtotal,
    o.discount_type,
    o.discount_value,
    o.total_amount
FROM orders o
INNER JOIN users u ON u.user_id = o.user_id
LEFT JOIN orderline ol ON ol.order_id = o.order_id
GROUP BY o.order_id, o.order_date, o.date_needed, o.status, u.user_id, u.full_name, u.username, u.email,
         o.discount_type, o.discount_value, o.total_amount;

CREATE OR REPLACE VIEW low_stock_report AS
SELECT
    p.product_id,
    p.game_code,
    p.name,
    p.game_studio,
    p.genre,
    s.quantity,
    COALESCE(p.min_stock_threshold, 5) AS threshold
FROM products p
INNER JOIN stock s ON s.product_id = p.product_id
WHERE p.is_active = 1
  AND p.is_archived = 0
  AND s.quantity > 0
  AND s.quantity <= COALESCE(p.min_stock_threshold, 5);
