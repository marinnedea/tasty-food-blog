-- ── Users ─────────────────────────────────────────────────────────
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(80)  NOT NULL UNIQUE,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    role         ENUM('admin','editor','reader') NOT NULL DEFAULT 'reader',
    display_name VARCHAR(120) NOT NULL DEFAULT '',
    nickname     VARCHAR(80)  NOT NULL DEFAULT '',
    avatar       VARCHAR(255) NULL,
    bio          TEXT         NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Categories ────────────────────────────────────────────────────
CREATE TABLE categories (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(120) NOT NULL,
    slug  VARCHAR(120) NOT NULL UNIQUE,
    color VARCHAR(7)   NOT NULL DEFAULT '#6FB241'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Posts ─────────────────────────────────────────────────────────
CREATE TABLE posts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    content        LONGTEXT     NOT NULL,
    featured_image VARCHAR(255) NULL,
    category_id    INT          NULL,
    author_id      INT          NOT NULL,
    status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id)   REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Recipes ───────────────────────────────────────────────────────
CREATE TABLE recipes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    featured_image VARCHAR(255) NULL,
    description    TEXT         NULL,
    prep_time      SMALLINT UNSIGNED NULL COMMENT 'minutes',
    cook_time      SMALLINT UNSIGNED NULL COMMENT 'minutes',
    servings       TINYINT UNSIGNED  NULL,
    difficulty     ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
    ingredients    TEXT NOT NULL,
    steps          TEXT NOT NULL,
    category_id    INT  NULL,
    author_id      INT  NOT NULL,
    status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id)   REFERENCES users(id)      ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Guides ────────────────────────────────────────────────────────
CREATE TABLE guides (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(255) NOT NULL,
    slug           VARCHAR(255) NOT NULL UNIQUE,
    place_name     VARCHAR(255) NOT NULL,
    location       VARCHAR(255) NOT NULL,
    dish           VARCHAR(255) NOT NULL,
    price_range    ENUM('€','€€','€€€') NOT NULL DEFAULT '€€',
    score          TINYINT UNSIGNED NULL,
    excerpt        TEXT         NULL,
    content        LONGTEXT     NOT NULL,
    featured_image VARCHAR(255) NULL,
    author_id      INT          NOT NULL,
    status         ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── User favourites ───────────────────────────────────────────────
CREATE TABLE user_favorites (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    item_type  ENUM('post','recipe','guide') NOT NULL,
    item_id    INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (user_id, item_type, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ratings ───────────────────────────────────────────────────────
CREATE TABLE ratings (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    item_type ENUM('post','recipe','guide') NOT NULL,
    item_id   INT NOT NULL,
    rating    TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY unique_rating (user_id, item_type, item_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Contact messages ─────────────────────────────────────────────
CREATE TABLE contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    email      VARCHAR(255) NOT NULL,
    subject    VARCHAR(255),
    message    TEXT         NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed categories ───────────────────────────────────────────────
INSERT INTO categories (name, slug, color) VALUES
    ('Food',       'food',       '#6FB241'),
    ('Encounters', 'encounters', '#F08C1E'),
    ('Recipes',    'recipes',    '#E52B72'),
    ('Guides',     'guides',     '#2196A8');
