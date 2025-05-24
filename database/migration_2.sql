CREATE TABLE IF NOT EXISTS users
(
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX idx_users_username ON users (username);

CREATE TABLE IF NOT EXISTS expenses
(
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL,
    date         DATETIME    NOT NULL,
    category     TEXT    NOT NULL,
    amount_cents INTEGER NOT NULL,
    description  TEXT,

    FOREIGN KEY (user_id)
    REFERENCES users (id)
    ON DELETE CASCADE
);
