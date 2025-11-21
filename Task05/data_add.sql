
PRAGMA foreign_keys = ON;
-- 1. Добавление пяти новых пользователей

-- Маклаков Сергей
INSERT INTO users (name, email, gender, register_date, occupation)
VALUES ('Маклаков Сергей', 'maklakov.sergey@student.ru', 'male', datetime('now', 'localtime'), 'student');

-- Логунов Илья
INSERT INTO users (name, email, gender, register_date, occupation)
VALUES ('Логунов Илья', 'logunov.ilya@student.ru', 'male', datetime('now', 'localtime'), 'student');

-- Макарова Юлия
INSERT INTO users (name, email, gender, register_date, occupation)
VALUES ('Макарова Юлия', 'makarova.yulia@student.ru', 'female', datetime('now', 'localtime'), 'student');

-- Маскинскова Наталья
INSERT INTO users (name, email, gender, register_date, occupation)
VALUES ('Маскинскова Наталья', 'maskinskova.natalia@student.ru', 'female', datetime('now', 'localtime'), 'student');

-- Мукасеев Дмитрий
INSERT INTO users (name, email, gender, register_date, occupation)
VALUES ('Мукасеев Дмитрий', 'mukaseev.dmitry@student.ru', 'male', datetime('now', 'localtime'), 'student');

-- 2. Добавление трех новых фильмов разных жанров

-- Фильм 1: Интерстеллар (2014) - Sci-Fi, Drama, Adventure
INSERT INTO movies (title, year)
VALUES ('Interstellar (2014)', 2014);

-- Связываем фильм с жанрами (используем last_insert_rowid() для получения ID только что добавленного фильма)
INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'Interstellar (2014)' AND year = 2014),
    (SELECT id FROM genres WHERE name = 'Sci-Fi')
);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'Interstellar (2014)' AND year = 2014),
    (SELECT id FROM genres WHERE name = 'Drama')
);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'Interstellar (2014)' AND year = 2014),
    (SELECT id FROM genres WHERE name = 'Adventure')
);

-- Фильм 2: Кошмар на улице Вязов (1984) - Horror, Thriller
INSERT INTO movies (title, year)
VALUES ('A Nightmare on Elm Street (1984)', 1984);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'A Nightmare on Elm Street (1984)' AND year = 1984),
    (SELECT id FROM genres WHERE name = 'Horror')
);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'A Nightmare on Elm Street (1984)' AND year = 1984),
    (SELECT id FROM genres WHERE name = 'Thriller')
);

-- Фильм 3: Главный герой (2021) - Action, Comedy, Adventure
INSERT INTO movies (title, year)
VALUES ('Free Guy (2021)', 2021);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'Free Guy (2021)' AND year = 2021),
    (SELECT id FROM genres WHERE name = 'Action')
);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'Free Guy (2021)' AND year = 2021),
    (SELECT id FROM genres WHERE name = 'Comedy')
);

INSERT INTO movie_genres (movie_id, genre_id)
VALUES (
    (SELECT id FROM movies WHERE title = 'Free Guy (2021)' AND year = 2021),
    (SELECT id FROM genres WHERE name = 'Adventure')
);

-- 3. Добавление трех отзывов

-- Отзыв на Интерстеллар (рейтинг 5/5)
INSERT INTO reviews (user_id, movie_id, rating, timestamp)
VALUES (
    (SELECT id FROM users WHERE email = 'maklakov.sergey@student.ru'),
    (SELECT id FROM movies WHERE title = 'Interstellar (2014)' AND year = 2014),
    5.0,
    strftime('%s', 'now')
);

-- Отзыв на Кошмар на улице Вязов (рейтинг 4/5)
INSERT INTO reviews (user_id, movie_id, rating, timestamp)
VALUES (
    (SELECT id FROM users WHERE email = 'maklakov.sergey@student.ru'),
    (SELECT id FROM movies WHERE title = 'A Nightmare on Elm Street (1984)' AND year = 1984),
    4.0,
    strftime('%s', 'now')
);

-- Отзыв на Главный герой (рейтинг 4.5/5)
INSERT INTO reviews (user_id, movie_id, rating, timestamp)
VALUES (
    (SELECT id FROM users WHERE email = 'maklakov.sergey@student.ru'),
    (SELECT id FROM movies WHERE title = 'Free Guy (2021)' AND year = 2021),
    4.5,
    strftime('%s', 'now')
);
