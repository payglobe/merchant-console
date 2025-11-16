-- Tabella BIN (Bank Identification Number)
-- Contiene i range BIN per identificare le banche emittenti delle carte

CREATE TABLE IF NOT EXISTS bin_table (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,

    -- Range BIN
    start_bin BIGINT NOT NULL COMMENT 'BIN di inizio range',
    end_bin BIGINT NOT NULL COMMENT 'BIN di fine range',
    bin_length INT NOT NULL COMMENT 'Lunghezza BIN (es. 16)',

    -- Paese
    bin_country INT COMMENT 'Codice numerico paese (es. 840=USA)',
    bin_country_description VARCHAR(100) COMMENT 'Nome paese esteso',
    country_code VARCHAR(3) COMMENT 'Codice ISO paese (es. IT, US, FR)',

    -- Dettagli carta
    card_brand_description VARCHAR(100) COMMENT 'Tipo carta (es. Visa Traditional)',
    service_type_description VARCHAR(50) COMMENT 'Credit/Debit Card',
    card_organisation_description VARCHAR(50) COMMENT 'Circuito (Visa, Mastercard)',
    card_product VARCHAR(50) COMMENT 'consumer/corporate',

    -- BANCA EMITTENTE (campo più importante!)
    issuer_name VARCHAR(255) COMMENT 'Nome banca emittente',

    -- Campi extra
    tipo_carta VARCHAR(100) COMMENT 'Tipo carta (campo custom)',
    paese VARCHAR(100) COMMENT 'Paese (campo custom)',
    transcodifica VARCHAR(255) COMMENT 'Transcodifica (campo custom)',

    -- Metadata
    run_date DATE COMMENT 'Data aggiornamento dati',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indici per performance
    INDEX idx_bin_range (start_bin, end_bin),
    INDEX idx_country_code (country_code),
    INDEX idx_issuer_name (issuer_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tabella BIN per identificazione banche emittenti carte';
