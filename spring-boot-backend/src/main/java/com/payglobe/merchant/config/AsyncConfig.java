package com.payglobe.merchant.config;

import org.springframework.context.annotation.Configuration;
import org.springframework.scheduling.annotation.EnableAsync;

/**
 * Configurazione per abilitare elaborazione asincrona
 */
@Configuration
@EnableAsync
public class AsyncConfig {
    // @EnableAsync abilita i metodi @Async in tutta l'applicazione
}
