PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS appointment_services;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS work_schedule;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS masters;

-- Таблица: masters (Мастера)
-- Хранит информацию о сотрудниках СТО.
-- При увольнении мастера данные НЕ удаляются, а заполняется поле dismissal_date.
CREATE TABLE masters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL CHECK(length(full_name) >= 2),
    phone TEXT NOT NULL CHECK(length(phone) >= 10),
    hire_date DATE NOT NULL DEFAULT (date('now')),
    dismissal_date DATE DEFAULT NULL,
    commission_rate REAL NOT NULL DEFAULT 0.20 CHECK(commission_rate >= 0 AND commission_rate <= 1),
    
    -- Проверка: дата увольнения не может быть раньше даты приёма
    CHECK(dismissal_date IS NULL OR dismissal_date >= hire_date)
);


-- Таблица: services (Услуги)
-- Справочник предоставляемых услуг СТО.
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE CHECK(length(name) >= 2),
    duration_minutes INTEGER NOT NULL DEFAULT 30 CHECK(duration_minutes > 0),
    price REAL NOT NULL CHECK(price >= 0),
    is_active INTEGER NOT NULL DEFAULT 1 CHECK(is_active IN (0, 1))
);

-- Таблица: work_schedule (График работы)
-- Хранит индивидуальный график работы каждого мастера.
CREATE TABLE work_schedule (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    master_id INTEGER NOT NULL,
    work_date DATE NOT NULL,
    start_time TIME NOT NULL DEFAULT '09:00',
    end_time TIME NOT NULL DEFAULT '18:00',
    
    FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE(master_id, work_date),
    CHECK(end_time > start_time)
);

-- Таблица: appointments (Записи на обслуживание)
-- Предварительная запись клиентов к мастерам.
CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    master_id INTEGER NOT NULL,
    client_name TEXT NOT NULL CHECK(length(client_name) >= 2),
    client_phone TEXT NOT NULL CHECK(length(client_phone) >= 10),
    appointment_datetime DATETIME NOT NULL,
    status TEXT NOT NULL DEFAULT 'scheduled' 
        CHECK(status IN ('scheduled', 'completed', 'cancelled')),
    created_at DATETIME NOT NULL DEFAULT (datetime('now')),
    completed_at DATETIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    
    FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    
    CHECK(completed_at IS NULL OR completed_at >= appointment_datetime)
);

-- Таблица: appointment_services (Услуги в записи)
-- Связующая таблица между записями и услугами (многие-ко-многим).
-- Хранит фактическую цену на момент записи для корректного расчёта.
CREATE TABLE appointment_services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    appointment_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    actual_price REAL NOT NULL CHECK(actual_price >= 0),
    quantity INTEGER NOT NULL DEFAULT 1 CHECK(quantity > 0),
    
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT ON UPDATE CASCADE,
        UNIQUE(appointment_id, service_id)
);

-- Индексы для оптимизации запросов
CREATE INDEX idx_masters_dismissal ON masters(dismissal_date);
CREATE INDEX idx_appointments_master ON appointments(master_id);
CREATE INDEX idx_appointments_datetime ON appointments(appointment_datetime);
CREATE INDEX idx_appointments_status ON appointments(status);
CREATE INDEX idx_work_schedule_master_date ON work_schedule(master_id, work_date);

-- Представления (Views)
-- Представление: работающие мастера
CREATE VIEW active_masters AS
SELECT * FROM masters WHERE dismissal_date IS NULL;

-- Представление: уволенные мастера
CREATE VIEW dismissed_masters AS
SELECT * FROM masters WHERE dismissal_date IS NOT NULL;

-- Представление: выполненные работы с суммой
CREATE VIEW completed_works AS
SELECT 
    a.id AS appointment_id,
    a.master_id,
    m.full_name AS master_name,
    a.client_name,
    a.appointment_datetime,
    a.completed_at,
    SUM(aps.actual_price * aps.quantity) AS total_amount,
    m.commission_rate,
    SUM(aps.actual_price * aps.quantity) * m.commission_rate AS master_earnings
FROM appointments a
JOIN masters m ON a.master_id = m.id
JOIN appointment_services aps ON a.id = aps.appointment_id
WHERE a.status = 'completed'
GROUP BY a.id;


-- Заполнение таблиц начальными данными
-- Мастера (включая одного уволенного)
INSERT INTO masters (full_name, phone, hire_date, dismissal_date, commission_rate) VALUES
    ('Иванов Пётр Сергеевич', '+79001234567', '2022-03-15', NULL, 0.25),
    ('Сидоров Алексей Николаевич', '+79002345678', '2021-06-01', NULL, 0.30),
    ('Козлов Дмитрий Андреевич', '+79003456789', '2023-01-10', NULL, 0.20),
    ('Морозов Игорь Владимирович', '+79004567890', '2020-09-01', '2024-08-15', 0.25),
    ('Васильев Андрей Петрович', '+79005678901', '2024-02-01', NULL, 0.22);

-- Услуги
INSERT INTO services (name, duration_minutes, price, is_active) VALUES
    ('Замена масла', 30, 1500.00, 1),
    ('Замена тормозных колодок', 60, 3000.00, 1),
    ('Диагностика двигателя', 45, 2000.00, 1),
    ('Замена свечей зажигания', 30, 1200.00, 1),
    ('Балансировка колёс', 40, 800.00, 1),
    ('Шиномонтаж (4 колеса)', 60, 2000.00, 1),
    ('Замена воздушного фильтра', 15, 500.00, 1),
    ('Замена антифриза', 45, 1800.00, 1),
    ('Регулировка развал-схождения', 60, 2500.00, 1),
    ('ТО комплексное', 180, 8000.00, 1),
    ('Замена ремня ГРМ', 240, 12000.00, 1),
    ('Промывка инжектора', 60, 3500.00, 0);

-- График работы на текущую неделю
INSERT INTO work_schedule (master_id, work_date, start_time, end_time) VALUES
    (1, '2024-11-25', '09:00', '18:00'),
    (1, '2024-11-26', '09:00', '18:00'),
    (1, '2024-11-27', '09:00', '18:00'),
    (1, '2024-11-28', '09:00', '18:00'),
    (1, '2024-11-29', '09:00', '18:00'),
    (2, '2024-11-25', '10:00', '19:00'),
    (2, '2024-11-26', '10:00', '19:00'),
    (2, '2024-11-28', '10:00', '19:00'),
    (2, '2024-11-29', '10:00', '19:00'),
    (2, '2024-11-30', '09:00', '15:00'),
    (3, '2024-11-25', '08:00', '17:00'),
    (3, '2024-11-27', '08:00', '17:00'),
    (3, '2024-11-29', '08:00', '17:00'),
    (3, '2024-11-30', '08:00', '14:00'),
    (5, '2024-11-26', '09:00', '18:00'),
    (5, '2024-11-27', '09:00', '18:00'),
    (5, '2024-11-28', '09:00', '18:00'),
    (5, '2024-11-30', '10:00', '16:00');

-- Записи на обслуживание (разные статусы)
INSERT INTO appointments (master_id, client_name, client_phone, appointment_datetime, status, created_at, completed_at, notes) VALUES
    (1, 'Петров Иван Иванович', '+79101112233', '2024-11-20 10:00', 'completed', '2024-11-18 14:30', '2024-11-20 11:30', 'Автомобиль Toyota Camry'),
    (1, 'Смирнова Анна Петровна', '+79102223344', '2024-11-21 14:00', 'completed', '2024-11-19 09:00', '2024-11-21 15:45', 'Volkswagen Polo'),
    (2, 'Кузнецов Сергей Михайлович', '+79103334455', '2024-11-19 11:00', 'completed', '2024-11-17 16:20', '2024-11-19 14:00', 'BMW X5, замена ГРМ'),
    (2, 'Новикова Елена Дмитриевна', '+79104445566', '2024-11-22 10:00', 'completed', '2024-11-20 11:00', '2024-11-22 10:45', NULL),
    (3, 'Волков Денис Александрович', '+79105556677', '2024-11-18 09:00', 'completed', '2024-11-15 10:30', '2024-11-18 12:00', 'Kia Rio, комплексное ТО'),
    (4, 'Соколов Артём Викторович', '+79106667788', '2024-07-10 10:00', 'completed', '2024-07-08 09:00', '2024-07-10 11:30', 'Работа уволенного мастера'),
    
    (1, 'Михайлов Олег Сергеевич', '+79107778899', '2024-11-27 10:00', 'scheduled', '2024-11-25 08:30', NULL, 'Mazda 6'),
    (2, 'Фёдорова Мария Ивановна', '+79108889900', '2024-11-28 14:00', 'scheduled', '2024-11-25 12:00', NULL, 'Hyundai Solaris'),
    (3, 'Егоров Павел Андреевич', '+79109990011', '2024-11-29 09:00', 'scheduled', '2024-11-26 14:45', NULL, NULL),
    (5, '  Алексеева Ирина Николаевна', '+79100001122', '2024-11-27 11:00', 'scheduled', '2024-11-25 16:00', NULL, 'Lada Vesta'),
    
    (1, 'Григорьев Максим Юрьевич', '+79111112233', '2024-11-23 15:00', 'cancelled', '2024-11-21 10:00', NULL, 'Клиент отменил');

-- Услуги в записях
INSERT INTO appointment_services (appointment_id, service_id, actual_price, quantity) VALUES
    (1, 1, 1500.00, 1),
    (1, 7, 500.00, 1),

    (2, 2, 3000.00, 1),
    
    (3, 11, 12000.00, 1),
    (3, 8, 1800.00, 1),
    
    (4, 1, 1500.00, 1),
    
    (5, 10, 8000.00, 1),
    (5, 5, 800.00, 1),
    
    (6, 3, 2000.00, 1),
    (6, 4, 1200.00, 1),
    
    (7, 6, 2000.00, 1),
    (7, 5, 800.00, 1),
    
    (8, 9, 2500.00, 1),
    
    (9, 3, 2000.00, 1),
    
    (10, 1, 1500.00, 1),
    (10, 7, 500.00, 1),
    
    (11, 2, 3000.00, 1);
