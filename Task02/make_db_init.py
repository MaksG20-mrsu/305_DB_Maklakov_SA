import pandas as pd
import os

DATASET_PATH = 'dataset'
DB_INIT_SCRIPT_PATH = 'db_init.sql'
DB_NAME = 'db_init.db'

def generate_sql_script():
    with open(DB_INIT_SCRIPT_PATH, 'w', encoding='utf-8') as f:
        f.write("DROP TABLE IF EXISTS movies;\n")
        f.write("DROP TABLE IF EXISTS ratings;\n")
        f.write("DROP TABLE IF EXISTS tags;\n")
        f.write("DROP TABLE IF EXISTS users;\n\n")

        f.write("""
CREATE TABLE movies (
    id INTEGER PRIMARY KEY,
    title TEXT,
    year INTEGER,
    genres TEXT
);
\n""")

        movies_df = pd.read_csv(os.path.join(DATASET_PATH, 'movies.csv'))
        for _, row in movies_df.iterrows():
            title = row['title'].replace("'", "''")
            f.write(f"INSERT INTO movies (id, title, year, genres) VALUES ({row['movieId']}, '{title}', {row.get('year', 'NULL')}, '{row['genres']}');\n")

        f.write("""
CREATE TABLE ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    movie_id INTEGER,
    rating REAL,
    timestamp INTEGER
);
\n""")

        ratings_df = pd.read_csv(os.path.join(DATASET_PATH, 'ratings.csv'))
        for _, row in ratings_df.iterrows():
            f.write(f"INSERT INTO ratings (user_id, movie_id, rating, timestamp) VALUES ({row['userId']}, {row['movieId']}, {row['rating']}, {row['timestamp']});\n")

        f.write("""
CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    movie_id INTEGER,
    tag TEXT,
    timestamp INTEGER
);
\n""")

        tags_df = pd.read_csv(os.path.join(DATASET_PATH, 'tags.csv'))
        for _, row in tags_df.iterrows():
            tag = str(row['tag']).replace("'", "''")
            f.write(f"INSERT INTO tags (user_id, movie_id, tag, timestamp) VALUES ({row['userId']}, {row['movieId']}, '{tag}', {row['timestamp']});\n")

        f.write("""
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT,
    email TEXT,
    gender TEXT,
    register_date TEXT,
    occupation TEXT
);
\n""")

        try:
            users_df = pd.read_csv(os.path.join(DATASET_PATH, 'users.dat'), sep='::', engine='python', names=['id', 'name', 'email', 'gender', 'register_date', 'occupation'])
            for _, row in users_df.iterrows():
                f.write(f"INSERT INTO users (id, name, email, gender, register_date, occupation) VALUES ({row['id']}, '{row['name']}', '{row['email']}', '{row['gender']}', '{row['register_date']}', '{row['occupation']}');\n")
        except FileNotFoundError:
            print(f"Файл 'users.dat' не найден в каталоге '{DATASET_PATH}'. Таблица 'users' будет пустой.")

if __name__ == '__main__':
    if not os.path.exists(DATASET_PATH):
        print(f"Ошибка: Каталог '{DATASET_PATH}' не найден. Пожалуйста, создайте его и поместите туда файлы с данными.")
    else:
        generate_sql_script()
        print(f"SQL-скрипт '{DB_INIT_SCRIPT_PATH}' успешно сгенерирован.")