package com.payglobe.merchant;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.data.jpa.repository.config.EnableJpaRepositories;

/**
 * PayGlobe Merchant API - Spring Boot Application
 *
 * REST API per dashboard merchant con coesistenza graduale con sistema PHP esistente.
 *
 * Porta: 8986
 * Database: MySQL 10.10.10.13 (condiviso con PHP, zero modifiche)
 *
 * @author PayGlobe Team
 * @version 1.0.0
 */
@SpringBootApplication
@EnableJpaRepositories
public class MerchantApiApplication {

    public static void main(String[] args) {
        System.out.println("=".repeat(80));
        System.out.println("PayGlobe Merchant API - Starting...");
        System.out.println("Port: 8986");
        System.out.println("Database: 10.10.10.13:3306/payglobe");
        System.out.println("=".repeat(80));

        SpringApplication.run(MerchantApiApplication.class, args);
    }

}
