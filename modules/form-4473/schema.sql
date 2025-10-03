-- ATF Form 4473 Database Schema
CREATE TABLE IF NOT EXISTS main_fflbro_form4473 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    status ENUM('in_progress', 'completed', 'transferred', 'denied') DEFAULT 'in_progress',
    section_a_complete BOOLEAN DEFAULT FALSE,
    section_b_complete BOOLEAN DEFAULT FALSE,
    section_c_complete BOOLEAN DEFAULT FALSE,
    section_d_complete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Transferee (Buyer) Information - Section A
CREATE TABLE IF NOT EXISTS main_fflbro_form4473_transferee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    suffix VARCHAR(10),
    date_of_birth DATE NOT NULL,
    height_feet INT,
    height_inches INT,
    weight_lbs INT,
    gender ENUM('M', 'F', 'X'),
    birth_place_city VARCHAR(100),
    birth_place_state VARCHAR(2),
    birth_place_country VARCHAR(100),
    residence_address VARCHAR(255) NOT NULL,
    residence_city VARCHAR(100) NOT NULL,
    residence_state VARCHAR(2) NOT NULL,
    residence_zip VARCHAR(10) NOT NULL,
    residence_county VARCHAR(100),
    ssn_last4 VARCHAR(4),
    upin VARCHAR(20),
    ethnicity VARCHAR(50),
    race VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(20),
    signature_data TEXT,
    signature_date TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES main_fflbro_form4473(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Firearms Information - Section B  
CREATE TABLE IF NOT EXISTS main_fflbro_form4473_firearms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    firearm_type ENUM('handgun', 'long_gun', 'other', 'receiver', 'frame') NOT NULL,
    manufacturer VARCHAR(255),
    model VARCHAR(255),
    serial_number VARCHAR(255) NOT NULL,
    caliber_gauge VARCHAR(50),
    importer VARCHAR(255),
    country_of_manufacture VARCHAR(100),
    sale_type ENUM('sale', 'trade', 'redemption', 'loan', 'rental', 'repair_return') DEFAULT 'sale',
    price DECIMAL(10,2),
    FOREIGN KEY (form_id) REFERENCES main_fflbro_form4473(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Background Check Questions - Section C & D
CREATE TABLE IF NOT EXISTS main_fflbro_form4473_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    question_11a ENUM('yes', 'no'),
    question_11b ENUM('yes', 'no'),
    question_11c ENUM('yes', 'no'),
    question_11d ENUM('yes', 'no'),
    question_11e ENUM('yes', 'no'),
    question_11f ENUM('yes', 'no'),
    question_11g ENUM('yes', 'no'),
    question_11h ENUM('yes', 'no'),
    question_11i ENUM('yes', 'no', 'n/a'),
    question_11j ENUM('yes', 'no'),
    question_11k ENUM('yes', 'no'),
    question_11l ENUM('yes', 'no'),
    exception_11i_explanation TEXT,
    FOREIGN KEY (form_id) REFERENCES main_fflbro_form4473(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NICS Check Results
CREATE TABLE IF NOT EXISTS main_fflbro_form4473_nics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    nics_transaction_number VARCHAR(50),
    check_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_result ENUM('proceed', 'denied', 'delayed', 'cancelled') NOT NULL,
    result_date TIMESTAMP,
    examiner_name VARCHAR(255),
    notes TEXT,
    FOREIGN KEY (form_id) REFERENCES main_fflbro_form4473(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Trail
CREATE TABLE IF NOT EXISTS main_fflbro_form4473_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details JSON,
    FOREIGN KEY (form_id) REFERENCES main_fflbro_form4473(id) ON DELETE CASCADE,
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
