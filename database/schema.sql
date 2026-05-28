CREATE DATABASE IF NOT EXISTS db_spk_beasiswa;
USE db_spk_beasiswa;

DROP TABLE IF EXISTS penilaian;
DROP TABLE IF EXISTS alternatif;
DROP TABLE IF EXISTS sub_kriteria;
DROP TABLE IF EXISTS kriteria;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id_user   INT PRIMARY KEY AUTO_INCREMENT,
    username  VARCHAR(50) UNIQUE NOT NULL,
    password  VARCHAR(255) NOT NULL,
    role      ENUM('admin','tim_seleksi') DEFAULT 'tim_seleksi'
);

CREATE TABLE kriteria (
    id_kriteria   INT PRIMARY KEY AUTO_INCREMENT,
    nama_kriteria VARCHAR(100) NOT NULL,
    bobot_persen  FLOAT NOT NULL
);

CREATE TABLE sub_kriteria (
    id_sub        INT PRIMARY KEY AUTO_INCREMENT,
    id_kriteria   INT NOT NULL,
    nama_sub_kriteria      VARCHAR(100) NOT NULL,
    nilai_standar INT NOT NULL,
    jenis_faktor  ENUM('Core Factor','Secondary Factor') NOT NULL,
    FOREIGN KEY (id_kriteria) REFERENCES kriteria(id_kriteria) ON DELETE CASCADE
);

CREATE TABLE alternatif (
    id_alternatif   INT PRIMARY KEY AUTO_INCREMENT,
    kode_alternatif VARCHAR(10) UNIQUE,
    nama_pendaftar  VARCHAR(150) NOT NULL,
    asal_instansi   VARCHAR(150)
);

CREATE TABLE penilaian (
    id_penilaian  INT PRIMARY KEY AUTO_INCREMENT,
    id_alternatif INT NOT NULL,
    id_sub        INT NOT NULL,
    nilai_input   INT NOT NULL,
    FOREIGN KEY (id_alternatif) REFERENCES alternatif(id_alternatif) ON DELETE CASCADE,
    FOREIGN KEY (id_sub)        REFERENCES sub_kriteria(id_sub)        ON DELETE CASCADE
);

CREATE TABLE konversi_skala (
    id_konversi   INT PRIMARY KEY AUTO_INCREMENT,
    id_sub        INT NOT NULL,
    tipe          ENUM('range', 'kategori') NOT NULL,
    nilai_skala   INT NOT NULL,
    range_min     FLOAT DEFAULT NULL,
    range_max     FLOAT DEFAULT NULL,
    kategori_teks VARCHAR(100) DEFAULT NULL,
    FOREIGN KEY (id_sub) REFERENCES sub_kriteria(id_sub) ON DELETE CASCADE
);