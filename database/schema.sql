mysqldump: [Warning] Using a password on the command line interface can be insecure.
-- MySQL dump 10.13  Distrib 5.7.42, for Linux (x86_64)
--
-- Host: 10.10.10.13    Database: payglobe
-- ------------------------------------------------------
-- Server version	8.0.42-0ubuntu0.24.10.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `payglobe`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `payglobe` /*!40100 DEFAULT CHARACTER SET latin1 */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `payglobe`;

--
-- Table structure for table `acquirer`
--

DROP TABLE IF EXISTS `acquirer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acquirer` (
  `codice` varchar(20) NOT NULL,
  `nome` varchar(45) NOT NULL,
  `contoaccredito` varchar(45) DEFAULT NULL,
  `tid` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`codice`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activated_terminals`
--

DROP TABLE IF EXISTS `activated_terminals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activated_terminals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `store_terminal_id` varchar(45) NOT NULL,
  `activation_code` varchar(50) NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `device_manufacturer` varchar(100) DEFAULT NULL,
  `app_version` varchar(20) DEFAULT NULL,
  `android_id` varchar(100) DEFAULT NULL,
  `status` enum('ACTIVE','SUSPENDED','DEACTIVATED') DEFAULT 'ACTIVE',
  `activated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_ping_at` timestamp NULL DEFAULT NULL,
  `config_version` varchar(10) DEFAULT '1.0',
  `created_by` varchar(50) NOT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_store_terminal_id` (`store_terminal_id`),
  KEY `idx_activation_code` (`activation_code`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_status` (`status`),
  KEY `idx_activated_at` (`activated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activation_audit_log`
--

DROP TABLE IF EXISTS `activation_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activation_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activation_code` varchar(20) NOT NULL,
  `action` varchar(50) NOT NULL,
  `user_agent` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `performed_by` varchar(50) DEFAULT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activation_code` (`activation_code`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activation_codes`
--

DROP TABLE IF EXISTS `activation_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activation_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `store_terminal_id` varchar(45) NOT NULL,
  `bu` varchar(45) NOT NULL,
  `status` enum('PENDING','USED','EXPIRED') DEFAULT 'PENDING',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `used_by` varchar(100) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `device_model` varchar(100) DEFAULT NULL,
  `device_manufacturer` varchar(100) DEFAULT NULL,
  `app_version` varchar(20) DEFAULT NULL,
  `android_id` varchar(100) DEFAULT NULL,
  `created_by` varchar(50) NOT NULL,
  `notes` text,
  `language` varchar(5) DEFAULT 'it' COMMENT 'Codice lingua: it, en, de, fr, es',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_store_terminal_id` (`store_terminal_id`),
  KEY `idx_bu` (`bu`),
  KEY `idx_status` (`status`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` int NOT NULL,
  `field_changed` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `changed_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_table_record` (`table_name`,`record_id`),
  KEY `idx_changed_at` (`changed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=323266 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bin_table`
--

DROP TABLE IF EXISTS `bin_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bin_table` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `bin_country` int DEFAULT NULL,
  `bin_country_description` varchar(100) DEFAULT NULL,
  `bin_length` int DEFAULT NULL,
  `card_brand_description` varchar(100) DEFAULT NULL,
  `card_organisation_description` varchar(50) DEFAULT NULL,
  `card_product` varchar(50) DEFAULT NULL,
  `country_code` varchar(3) DEFAULT NULL,
  `created_at` datetime(6) DEFAULT NULL,
  `end_bin` bigint DEFAULT NULL,
  `issuer_name` varchar(255) DEFAULT NULL,
  `paese` varchar(100) DEFAULT NULL,
  `run_date` date DEFAULT NULL,
  `service_type_description` varchar(50) DEFAULT NULL,
  `start_bin` bigint DEFAULT NULL,
  `tipo_carta` varchar(100) DEFAULT NULL,
  `transcodifica` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bin_range` (`start_bin`,`end_bin`),
  KEY `idx_country_code` (`country_code`),
  KEY `idx_issuer_name` (`issuer_name`)
) ENGINE=InnoDB AUTO_INCREMENT=2716674 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `binlist`
--

DROP TABLE IF EXISTS `binlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `binlist` (
  `PAN` text,
  `Circuito` text,
  `BancaEmettitrice` text,
  `LivelloCarta` text,
  `TipoCarta` text,
  `Nazione` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `circuit_codes`
--

DROP TABLE IF EXISTS `circuit_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `circuit_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Codice circuito dal loghost',
  `description` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Descrizione completa',
  `brand_group` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Gruppo brand (Visa, MasterCard, Bancomat, etc)',
  `card_type` enum('Credit','Debit','Commercial','PrePaid') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `region` enum('EEA','Extra-EEA','Domestic') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_code` (`code`),
  KEY `idx_brand_group` (`brand_group`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Codici circuito per trascodifica display';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config_audit_log`
--

DROP TABLE IF EXISTS `config_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL,
  `user_agent` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `performed_by` varchar(50) DEFAULT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `devices` (
  `BUID` int DEFAULT NULL,
  `MERCHANT` text,
  `SHOP_SIGN` text,
  `ADDRESS` text,
  `NOMERIF` text,
  `POSID` int DEFAULT NULL,
  `POS_VERSION` text,
  `ABI` text,
  `VENDOR_PRIV` text,
  `VENDOR_CODE` int DEFAULT NULL,
  `SHOP_CODE` text,
  `VAT_CODE` text,
  `STATE_PV` text,
  `STATE_POS` text,
  `STATE` text,
  `PROFILE` text,
  `CONN` text,
  `CONN_BKUP` text,
  `CONN_TLG` text,
  `POS_TYPE` text,
  `POS_TYPE_CODE` int DEFAULT NULL,
  `POS_MODEL` text,
  `POS_MATRICOLA` text,
  `POS_SWBASE` text,
  `POS_SWAPP` text,
  `PINPAD_MODEL` text,
  `CLESS_POS` text,
  `CLESSPB_PROFILOTEC` text,
  `CLESSCC_PROFILOTEC` text,
  `ACTIVATION_DATE` text,
  `DEACTIVATION_DATE` text,
  `CREATION_DATE` text,
  `CHECK APP` text,
  `CHECK SOFT` text,
  `CONFIGURATION_DATE` text,
  `LASTDLL_DATE` text,
  `LAST_TRX_DATE` text,
  `PRIV` text,
  `IDX` text,
  `BUSINESSUNITID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `file_processing_log`
--

DROP TABLE IF EXISTS `file_processing_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_processing_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nome file loghost processato',
  `file_date` date NOT NULL COMMENT 'Data file estratta da filename',
  `file_size` bigint DEFAULT NULL COMMENT 'Dimensione file in bytes',
  `records_count` int NOT NULL DEFAULT '0' COMMENT 'Numero record processati',
  `records_inserted` int NOT NULL DEFAULT '0' COMMENT 'Record inseriti con successo',
  `records_updated` int NOT NULL DEFAULT '0' COMMENT 'Record aggiornati',
  `records_errors` int NOT NULL DEFAULT '0' COMMENT 'Record con errori',
  `processing_start` timestamp NULL DEFAULT NULL COMMENT 'Inizio elaborazione',
  `processing_end` timestamp NULL DEFAULT NULL COMMENT 'Fine elaborazione',
  `processed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('processing','completed','error','skipped') COLLATE utf8mb4_unicode_ci DEFAULT 'processing',
  `error_message` text COLLATE utf8mb4_unicode_ci COMMENT 'Messaggi di errore',
  `cron_execution_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID esecuzione cron per tracking',
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`),
  KEY `idx_filename` (`filename`),
  KEY `idx_file_date` (`file_date`),
  KEY `idx_status` (`status`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3942 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Log processamento file loghost per tracciabilità';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ftfs_transactions`
--

DROP TABLE IF EXISTS `ftfs_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ftfs_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Trid` bigint DEFAULT NULL,
  `TermId` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `SiaCode` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DtTrans` datetime DEFAULT NULL,
  `ApprNum` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Acid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Acquirer` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Pan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `Currency` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DtIns` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PointOfService` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Cont` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NumOper` int DEFAULT NULL,
  `DtPos` datetime DEFAULT NULL,
  `PosReq` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PosStan` int DEFAULT NULL,
  `PfCode` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PMrc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PosAcq` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `GtResp` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NumTent` int DEFAULT NULL,
  `TP` varchar(1) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CatMer` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `VndId` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PvdId` int DEFAULT NULL,
  `Bin` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Tpc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `VaFl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `FvFl` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TrKey` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CSeq` bigint DEFAULT NULL,
  `Conf` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `AutTime` int DEFAULT NULL,
  `DBTime` int DEFAULT NULL,
  `TOTTime` int DEFAULT NULL,
  `DFN` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CED` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TTQ` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `FFI` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TCAP` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ISR` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `IST` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `IAutD` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CryptCurr` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CryptType` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CrypAmnt` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CryptTD` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `UN` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CVR` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `TVR` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `IAD` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CID` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `AId` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `HATC` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `AIP` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ACrypt` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PaymentId` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CCode` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `RespAcq` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `MeId` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `OperExpl` varchar(65) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `igfs_credentials`
--

DROP TABLE IF EXISTS `igfs_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `igfs_credentials` (
  `key_id` varchar(45) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `base_url` varchar(345) NOT NULL,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `environment` enum('TEST','PRODUCTION') DEFAULT 'TEST',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `merchants`
--

DROP TABLE IF EXISTS `merchants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `merchants` (
  `merchantID` varchar(40) NOT NULL,
  `insegna` varchar(45) DEFAULT NULL,
  `localita` varchar(45) DEFAULT NULL,
  `provincia` varchar(4) DEFAULT NULL,
  `ragionesociale` varchar(55) DEFAULT NULL,
  `indirizzo` varchar(60) DEFAULT NULL,
  `cap` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`merchantID`),
  UNIQUE KEY `merchantID_UNIQUE` (`merchantID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mid_acquirer`
--

DROP TABLE IF EXISTS `mid_acquirer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mid_acquirer` (
  `codice_mid` varchar(45) NOT NULL,
  `codice_sia` varchar(45) DEFAULT NULL,
  `istituto` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`codice_mid`),
  UNIQUE KEY `codice_UNIQUE` (`codice_mid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `network`
--

DROP TABLE IF EXISTS `network`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `network` (
  `TerminalID` varchar(10) NOT NULL,
  `TIPO_RETE` varchar(12) DEFAULT NULL,
  `IP` varchar(20) DEFAULT NULL,
  `MASK` varchar(20) DEFAULT NULL,
  `GW1` varchar(20) DEFAULT NULL,
  `DNS1` varchar(45) DEFAULT NULL,
  `NOME_RETE` varchar(45) DEFAULT NULL,
  `PASS_RETE` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`TerminalID`),
  UNIQUE KEY `TerminalID_UNIQUE` (`TerminalID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_app_igfs`
--

DROP TABLE IF EXISTS `payments_app_igfs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments_app_igfs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `txId` varchar(100) NOT NULL,
  `paymentId` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `reason` text,
  `status` enum('pending','success','failed') DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(75) DEFAULT NULL,
  `tml` varchar(45) DEFAULT NULL,
  `resultCode` varchar(45) DEFAULT NULL,
  `errDescription` varchar(345) DEFAULT NULL,
  `tranId` varchar(32) DEFAULT NULL,
  `authCode` varchar(16) DEFAULT NULL,
  `maskedPan` varchar(32) DEFAULT NULL,
  `cardBrand` varchar(32) DEFAULT NULL,
  `payerName` varchar(64) DEFAULT NULL,
  `payerLastName` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_txId` (`txId`)
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_app_igfs_user`
--

DROP TABLE IF EXISTS `payments_app_igfs_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments_app_igfs_user` (
  `keyId` varchar(45) NOT NULL,
  `secret` varchar(45) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `password` varchar(45) DEFAULT NULL,
  `baseUrl` varchar(345) DEFAULT NULL,
  PRIMARY KEY (`keyId`),
  UNIQUE KEY `keyId_UNIQUE` (`keyId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `payments_igfs`
--

DROP TABLE IF EXISTS `payments_igfs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments_igfs` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tx_id` varchar(100) NOT NULL,
  `payment_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` text,
  `status` enum('PENDING','SUCCESS','FAILED','EXPIRED') DEFAULT 'PENDING',
  `terminal_id` varchar(50) DEFAULT NULL,
  `tml` varchar(45) DEFAULT NULL,
  `email` varchar(75) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `result_code` varchar(45) DEFAULT NULL,
  `err_description` varchar(345) DEFAULT NULL,
  `tran_id` varchar(32) DEFAULT NULL,
  `auth_code` varchar(16) DEFAULT NULL,
  `masked_pan` varchar(32) DEFAULT NULL,
  `card_brand` varchar(32) DEFAULT NULL,
  `payer_name` varchar(64) DEFAULT NULL,
  `payer_last_name` varchar(64) DEFAULT NULL,
  `merchant_reference` varchar(100) DEFAULT NULL,
  `return_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tx_id` (`tx_id`),
  KEY `idx_tx_id` (`tx_id`),
  KEY `idx_status` (`status`),
  KEY `idx_terminal_id` (`terminal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `price_cents` int NOT NULL DEFAULT '0',
  `vat_percent` tinyint NOT NULL DEFAULT '22',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `terminal_id` varchar(12) NOT NULL DEFAULT '000000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_name` (`name`),
  UNIQUE KEY `uq_products_terminal_name` (`terminal_id`,`name`),
  KEY `ix_products_terminal` (`terminal_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scarti`
--

DROP TABLE IF EXISTS `scarti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scarti` (
  `pgID` varchar(50) NOT NULL,
  `codificaStab` varchar(50) DEFAULT NULL,
  `tipoRiep` varchar(50) DEFAULT NULL,
  `terminalID` varchar(90) DEFAULT NULL,
  `domestico` varchar(50) DEFAULT NULL,
  `pan` varchar(50) DEFAULT NULL,
  `dataOperazione` date NOT NULL,
  `oraOperazione` time NOT NULL,
  `importo` varchar(50) DEFAULT NULL,
  `codiceAutorizzativo` varchar(50) DEFAULT NULL,
  `tipoOperazione` varchar(100) DEFAULT NULL,
  `flagLog` varchar(50) DEFAULT NULL,
  `actinCode` varchar(50) DEFAULT NULL,
  `insegna` varchar(50) DEFAULT NULL,
  `cap` varchar(50) DEFAULT NULL,
  `localita` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `ragioneSociale` varchar(50) DEFAULT NULL,
  `indirizzo` varchar(50) DEFAULT NULL,
  `acquirer` varchar(50) DEFAULT NULL,
  `tag4f` varchar(50) DEFAULT NULL,
  `contoaccredito` varchar(50) DEFAULT NULL,
  `rrn` varchar(45) DEFAULT NULL,
  `operazione` varchar(45) DEFAULT NULL,
  `orderID` varchar(45) DEFAULT NULL,
  `cardholdername` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`pgID`),
  UNIQUE KEY `pgID_UNIQUE` (`pgID`),
  KEY `idx_tracciato_domestico` (`domestico`),
  KEY `acquirer` (`acquirer`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stores` (
  `TerminalID` varchar(45) NOT NULL,
  `Ragione_Sociale` varchar(45) DEFAULT NULL,
  `Insegna` varchar(55) DEFAULT NULL,
  `indirizzo` varchar(95) DEFAULT NULL,
  `citta` varchar(45) DEFAULT NULL,
  `cap` varchar(45) DEFAULT NULL,
  `prov` varchar(45) DEFAULT NULL,
  `sia_pagobancomat` varchar(45) DEFAULT NULL,
  `six` varchar(45) DEFAULT NULL,
  `amex` varchar(45) DEFAULT NULL,
  `Modello_pos` varchar(45) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'IT',
  `bu` varchar(45) DEFAULT NULL,
  `bu1` varchar(45) DEFAULT NULL,
  `bu2` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`TerminalID`),
  UNIQUE KEY `TerminalID_UNIQUE` (`TerminalID`),
  KEY `idx_stores_bu` (`bu`),
  KEY `idx_stores_bu_modello` (`bu`,`Modello_pos`),
  KEY `idx_stores_bu_insegna` (`bu`,`Insegna`),
  KEY `idx_terminalid` (`TerminalID`),
  KEY `idx_ragione_sociale` (`Ragione_Sociale`),
  KEY `idx_insegna` (`Insegna`),
  KEY `idx_bu` (`bu`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`PGDBUSER`@`%`*/ /*!50003 TRIGGER `trg_copy_on_insert` BEFORE INSERT ON `stores` FOR EACH ROW BEGIN
    SET NEW.bu2 = NEW.TerminalID;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`PGDBUSER`@`%`*/ /*!50003 TRIGGER `trg_copy_on_update` BEFORE UPDATE ON `stores` FOR EACH ROW BEGIN
    SET NEW.bu2 = NEW.TerminalID;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary table structure for view `stores_con_network`
--

DROP TABLE IF EXISTS `stores_con_network`;
/*!50001 DROP VIEW IF EXISTS `stores_con_network`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `stores_con_network` AS SELECT 
 1 AS `TerminalID`,
 1 AS `Modello_pos`,
 1 AS `insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `Rete`,
 1 AS `IP`,
 1 AS `MASK`,
 1 AS `GATEWAY`,
 1 AS `DNS`,
 1 AS `Nome_rete_wifi`,
 1 AS `Password_wifi`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `terminal_config`
--

DROP TABLE IF EXISTS `terminal_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `terminal_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` text,
  `terminal_id` varchar(15) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_config` (`config_key`,`terminal_id`),
  KEY `idx_config_key` (`config_key`),
  KEY `idx_terminal_id` (`terminal_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2053 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tid_profiles`
--

DROP TABLE IF EXISTS `tid_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tid_profiles` (
  `BUID` int DEFAULT NULL,
  `MERCHANT` int DEFAULT NULL,
  `SHOP_SIGN` int DEFAULT NULL,
  `ADDRESS` int DEFAULT NULL,
  `NOMERIF` int DEFAULT NULL,
  `POSID` int DEFAULT NULL,
  `POS_VERSION` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ABI` int DEFAULT NULL,
  `VENDOR_CODE` int DEFAULT NULL,
  `SHOP_CODE` int DEFAULT NULL,
  `VAT_CODE` int DEFAULT NULL,
  `STATE_PV` int DEFAULT NULL,
  `STATE_POS` int DEFAULT NULL,
  `STATE` int DEFAULT NULL,
  `PROFILE` int DEFAULT NULL,
  `CONN` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CONN_BKUP` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CONN_TLG` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `POS_TYPE` varchar(8) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `POS_TYPE_CODE` int DEFAULT NULL,
  `POS_MODEL` int DEFAULT NULL,
  `POS_MATRICOLA` int DEFAULT NULL,
  `POS_SWBASE` int DEFAULT NULL,
  `POS_SWAPP` varchar(6) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `PINPAD_MODEL` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CLESS_POS` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CLESSPB_PROFILOTEC` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CLESSCC_PROFILOTEC` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ACTIVATION_DATE` int DEFAULT NULL,
  `DEACTIVATION_DATE` int DEFAULT NULL,
  `CREATION_DATE` datetime DEFAULT NULL,
  `TCKSA_DS` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `TCKSB_DS` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `CONFIGURATION_DATE` datetime DEFAULT NULL,
  `LASTDLL_DATE` datetime DEFAULT NULL,
  `LAST_TRX_DATE` datetime DEFAULT NULL,
  `LAST_OP_DATE` datetime DEFAULT NULL,
  `IDX` int DEFAULT NULL,
  `BRAND` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ACQUIRER` varchar(14) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `MERCHANT_CODE` bigint DEFAULT NULL,
  `TERM_CODE` int DEFAULT NULL,
  `ABI_CODE` int DEFAULT NULL,
  `FABIL` bigint DEFAULT NULL,
  `CLESS_CONV` varchar(3) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `VENDOR_PRIV` int DEFAULT NULL,
  `PRIV` int DEFAULT NULL,
  `BUSINESSUNITID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tracciato`
--

DROP TABLE IF EXISTS `tracciato`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracciato` (
  `pgID` varchar(50) NOT NULL,
  `codificaStab` varchar(50) DEFAULT NULL,
  `tipoRiep` varchar(50) DEFAULT NULL,
  `terminalID` varchar(190) DEFAULT NULL,
  `domestico` varchar(50) DEFAULT NULL,
  `pan` varchar(50) DEFAULT NULL,
  `dataOperazione` date NOT NULL,
  `oraOperazione` time NOT NULL,
  `importo` varchar(50) DEFAULT NULL,
  `codiceAutorizzativo` varchar(50) DEFAULT NULL,
  `tipoOperazione` varchar(100) DEFAULT NULL,
  `flagLog` varchar(50) DEFAULT NULL,
  `actinCode` varchar(50) DEFAULT NULL,
  `insegna` varchar(50) DEFAULT NULL,
  `cap` varchar(50) DEFAULT NULL,
  `localita` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `ragioneSociale` varchar(50) DEFAULT NULL,
  `indirizzo` varchar(50) DEFAULT NULL,
  `acquirer` varchar(50) DEFAULT NULL,
  `tag4f` varchar(50) DEFAULT NULL,
  `contoaccredito` varchar(50) DEFAULT NULL,
  `rrn` varchar(45) DEFAULT NULL,
  `operazione` varchar(45) DEFAULT NULL,
  `orderID` varchar(45) DEFAULT NULL,
  `cardholdername` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`pgID`),
  UNIQUE KEY `pgID_UNIQUE` (`pgID`),
  KEY `idx_tracciato_domestico` (`domestico`),
  KEY `acquirer` (`acquirer`),
  KEY `dataoperazione_idx` (`dataOperazione`),
  KEY `idx_tracciato_codstab_date` (`codificaStab`,`dataOperazione`),
  KEY `idx_tracciato_date` (`dataOperazione`),
  KEY `idx_tracciato_terminalID` (`terminalID`),
  KEY `idx_tracciato_importo` (`importo`),
  KEY `idx_tracciato_acquirer` (`acquirer`),
  KEY `idx_tracciato_tag4f` (`tag4f`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `tracciato_con_modello_pos`
--

DROP TABLE IF EXISTS `tracciato_con_modello_pos`;
/*!50001 DROP VIEW IF EXISTS `tracciato_con_modello_pos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `tracciato_con_modello_pos` AS SELECT 
 1 AS `codificaStab`,
 1 AS `TerminalID`,
 1 AS `Modello_pos`,
 1 AS `domestico`,
 1 AS `pan`,
 1 AS `dataOperazione`,
 1 AS `oraOperazione`,
 1 AS `importo`,
 1 AS `codiceAutorizzativo`,
 1 AS `flagLog`,
 1 AS `actinCode`,
 1 AS `insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `citta`,
 1 AS `prov`,
 1 AS `cap`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `tracciato_pos`
--

DROP TABLE IF EXISTS `tracciato_pos`;
/*!50001 DROP VIEW IF EXISTS `tracciato_pos`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `tracciato_pos` AS SELECT 
 1 AS `codificaStab`,
 1 AS `terminalID`,
 1 AS `Modello_pos`,
 1 AS `domestico`,
 1 AS `pan`,
 1 AS `tag4f`,
 1 AS `dataOperazione`,
 1 AS `oraOperazione`,
 1 AS `importo`,
 1 AS `codiceAutorizzativo`,
 1 AS `acquirer`,
 1 AS `flagLog`,
 1 AS `actinCode`,
 1 AS `tipoOperazione`,
 1 AS `insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `localita`,
 1 AS `prov`,
 1 AS `cap`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `tracciato_pos_iva`
--

DROP TABLE IF EXISTS `tracciato_pos_iva`;
/*!50001 DROP VIEW IF EXISTS `tracciato_pos_iva`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `tracciato_pos_iva` AS SELECT 
 1 AS `piva`,
 1 AS `codificaStab`,
 1 AS `terminalID`,
 1 AS `Modello_pos`,
 1 AS `domestico`,
 1 AS `pan`,
 1 AS `tag4f`,
 1 AS `dataOperazione`,
 1 AS `oraOperazione`,
 1 AS `importo`,
 1 AS `codiceAutorizzativo`,
 1 AS `acquirer`,
 1 AS `flagLog`,
 1 AS `actinCode`,
 1 AS `insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `localita`,
 1 AS `prov`,
 1 AS `cap`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `tracciato_pos_nets`
--

DROP TABLE IF EXISTS `tracciato_pos_nets`;
/*!50001 DROP VIEW IF EXISTS `tracciato_pos_nets`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `tracciato_pos_nets` AS SELECT 
 1 AS `codificaStab`,
 1 AS `terminalID`,
 1 AS `Modello_pos`,
 1 AS `domestico`,
 1 AS `pan`,
 1 AS `tag4f`,
 1 AS `dataOperazione`,
 1 AS `oraOperazione`,
 1 AS `importo`,
 1 AS `codiceAutorizzativo`,
 1 AS `acquirer`,
 1 AS `flagLog`,
 1 AS `actinCode`,
 1 AS `insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `localita`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `tracciato_pos_online`
--

DROP TABLE IF EXISTS `tracciato_pos_online`;
/*!50001 DROP VIEW IF EXISTS `tracciato_pos_online`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `tracciato_pos_online` AS SELECT 
 1 AS `codificaStab`,
 1 AS `terminalID`,
 1 AS `Modello_pos`,
 1 AS `pan`,
 1 AS `dataOperazione`,
 1 AS `importo`,
 1 AS `codiceAutorizzativo`,
 1 AS `acquirer`,
 1 AS `PosAcq`,
 1 AS `AId`,
 1 AS `PosStan`,
 1 AS `Conf`,
 1 AS `NumOper`,
 1 AS `TP`,
 1 AS `TPC`,
 1 AS `insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `localita`,
 1 AS `prov`,
 1 AS `cap`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transactions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `transaction_type` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'DAACQU, CAACQU, etc',
  `posid` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Terminal ID (campo POSID dal loghost)',
  `termid` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Terminal ID acquirer',
  `store_code` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acid` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meid` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `amount_raw` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Original amount from loghost file',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT '978' COMMENT 'ISO currency code',
  `pan` varchar(19) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Masked PAN',
  `transaction_date` datetime NOT NULL,
  `transaction_number` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `acquirer_bank` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `card_read_mode` enum('M','B','C','L') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Manual, Swiped, Chip, Contactless',
  `approval_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_description` varchar(72) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pos_data_code` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cashback_amount` decimal(12,2) DEFAULT NULL,
  `amount_surcharge` decimal(5,2) DEFAULT NULL,
  `rte_rule` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Routing Transaction Engine rule',
  `transaction_status` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settlement_flag` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '1=settled, 0=not settled',
  `dcc_status` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dcc_curr` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dcc_ext` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'DCC Exchange rate',
  `dcc_amount` decimal(12,2) DEFAULT NULL,
  `ib_response_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Acquirer Response Code',
  `confirmation` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pos_acid` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Card Brand POS setting',
  `response_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Terminal Handler response',
  `reversal_trid` varchar(33) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Reversal Transaction ID',
  `merchant_id` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Merchant identification',
  `rrn` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Retrieval Reference Number',
  `term_model` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'PED Model',
  `transaction_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Transaction Unique Identifier DF8105',
  `df8102` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Private field DF8102',
  `df8103` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Private field DF8103',
  `df8104` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Private field DF8104',
  `df8106` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Private field DF8106',
  `df8107` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Private field DF8107',
  `df8108` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Private field DF8108',
  `card_brand` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Card Brand (ES: PA, PB, MC, VC, etc)',
  `card_settlement_type` enum('C','D') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Credit/Debit',
  `card_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Card type info',
  `card_abi_issuer` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Issuer Bank ID (ABI)',
  `card_country_alpha` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Card Country ISO Alpha',
  `card_country_num` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Card Country ISO Numeric',
  `file_source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Source loghost filename',
  `processed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `transaction_date_only` date GENERATED ALWAYS AS (cast(`transaction_date` as date)) STORED,
  `transaction_hour` tinyint GENERATED ALWAYS AS (hour(`transaction_date`)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_transaction` (`posid`,`transaction_number`,`transaction_date`),
  KEY `idx_posid` (`posid`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_amount` (`amount`),
  KEY `idx_settlement` (`settlement_flag`),
  KEY `idx_card_brand` (`card_brand`),
  KEY `idx_transaction_type` (`transaction_type`),
  KEY `idx_file_source` (`file_source`),
  KEY `idx_processed_at` (`processed_at`),
  KEY `idx_transactions_dashboard` (`transaction_date`,`settlement_flag`,`posid`),
  KEY `idx_transactions_posid_date` (`posid`,`transaction_date`),
  KEY `idx_transactions_volume` (`settlement_flag`,`transaction_type`,`amount`),
  KEY `idx_transactions_circuit` (`card_brand`,`settlement_flag`,`transaction_date`),
  KEY `idx_transactions_pan_prefix` (`pan`(6),`transaction_date`),
  KEY `idx_transactions_amount_date` (`amount`,`transaction_date`),
  KEY `idx_transactions_dashboard_optimized` (`transaction_date`,`settlement_flag`,`posid`,`amount`),
  KEY `idx_transactions_posid_settlement_date` (`posid`,`settlement_flag`,`transaction_date`),
  KEY `idx_transactions_type_settlement_amount` (`transaction_type`,`settlement_flag`,`amount`,`transaction_date`),
  KEY `idx_transactions_date_hour_generated` (`transaction_date_only`,`transaction_hour`,`settlement_flag`),
  KEY `idx_transactions_pan6_date_settlement` ((substr(`pan`,1,6)),`transaction_date`,`settlement_flag`)
) ENGINE=InnoDB AUTO_INCREMENT=206969636 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabella transazioni FTFS LogHost - dati parsing automatico';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`PGDBUSER`@`%`*/ /*!50003 TRIGGER `transactions_audit_trigger` AFTER UPDATE ON `transactions` FOR EACH ROW BEGIN
    IF OLD.amount != NEW.amount OR OLD.settlement_flag != NEW.settlement_flag THEN
        INSERT INTO audit_log (table_name, record_id, field_changed, old_value, new_value, changed_by, changed_at)
        VALUES ('transactions', NEW.id, 'amount_or_settlement', 
                CONCAT(OLD.amount, '|', OLD.settlement_flag), 
                CONCAT(NEW.amount, '|', NEW.settlement_flag),
                USER(), NOW());
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET characmysqldump: Couldn't execute 'SHOW FIELDS FROM `v_active_terminals`': View 'payglobe.v_active_terminals' references invalid table(s) or column(s) or function(s) or definer/invoker of view lack rights to use them (1356)
ter_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_token_expiry` datetime DEFAULT NULL,
  `password_last_changed` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `bu` varchar(45) DEFAULT NULL,
  `ragione_sociale` varchar(255) DEFAULT NULL,
  `force_password_change` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `v_activation_codes_full`
--

DROP TABLE IF EXISTS `v_activation_codes_full`;
/*!50001 DROP VIEW IF EXISTS `v_activation_codes_full`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `v_activation_codes_full` AS SELECT 
 1 AS `id`,
 1 AS `code`,
 1 AS `store_terminal_id`,
 1 AS `bu`,
 1 AS `status`,
 1 AS `created_at`,
 1 AS `expires_at`,
 1 AS `used_at`,
 1 AS `device_id`,
 1 AS `device_model`,
 1 AS `device_manufacturer`,
 1 AS `app_version`,
 1 AS `android_id`,
 1 AS `created_by`,
 1 AS `notes`,
 1 AS `Insegna`,
 1 AS `Ragione_Sociale`,
 1 AS `indirizzo`,
 1 AS `citta`,
 1 AS `prov`,
 1 AS `cap`,
 1 AS `current_status`,
 1 AS `days_until_expiry`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary table structure for view `v_active_terminals`
--

DROP TABLE IF EXISTS `v_active_terminals`;
