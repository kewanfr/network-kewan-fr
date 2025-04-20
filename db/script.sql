CREATE DATABASE inventaire_machines;
USE inventaire_machines;

CREATE TABLE machines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  mac VARCHAR(45),
  description TEXT
);

CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  machine_id INT NOT NULL,
  nom VARCHAR(255) NOT NULL,
  port INT NOT NULL,
  protocole ENUM('TCP', 'UDP') DEFAULT 'TCP',
  FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE
);

CREATE TABLE domaines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  domaine VARCHAR(255) NOT NULL,
  ssl_enabled BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

CREATE TABLE hostnames (
  id INT AUTO_INCREMENT PRIMARY KEY,
  machine_id INT NOT NULL,
  hostname VARCHAR(255) NOT NULL,
  FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS hostnames;

ALTER TABLE machines ADD COLUMN hostnames TEXT DEFAULT NULL;
