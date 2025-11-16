package com.payglobe.merchant.service;

import com.payglobe.merchant.entity.Store;
import com.payglobe.merchant.entity.User;
import com.payglobe.merchant.repository.StoreRepository;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

/**
 * Service per gestione stores
 */
@Service
@Slf4j
@RequiredArgsConstructor
public class StoreService {

    private final StoreRepository storeRepository;

    /**
     * Trova store per Terminal ID con autorizzazione BU
     */
    @Transactional(readOnly = true)
    public Optional<Store> findByTerminalId(String terminalId, User currentUser) {
        Optional<Store> store = storeRepository.findByTerminalId(terminalId);

        // Se non admin, verifica BU
        if (store.isPresent() && !currentUser.isAdmin()) {
            if (!store.get().getBu().equals(currentUser.getBu())) {
                log.warn("User {} tried to access store {} from different BU",
                         currentUser.getEmail(), terminalId);
                return Optional.empty();
            }
        }

        return store;
    }

    /**
     * Trova stores con filtri e autorizzazione BU
     */
    @Transactional(readOnly = true)
    public Page<Store> findStores(
            String citta,
            String prov,
            String country,
            String keyword,
            User currentUser,
            Pageable pageable) {

        log.debug("Finding stores: citta={}, prov={}, country={}, keyword={}, bu={}",
                  citta, prov, country, keyword, currentUser.getBu());

        if (currentUser.isAdmin()) {
            // Admin: ricerca globale
            if (keyword != null && !keyword.isBlank()) {
                return storeRepository.searchStores(keyword, pageable);
            }
            // TODO: implementare filtri multipli per admin
            return storeRepository.findAll(pageable);
        } else {
            // Utente normale: solo la propria BU
            if (citta != null || prov != null || country != null) {
                return storeRepository.findByBuWithFilters(
                    currentUser.getBu(), citta, prov, country, pageable);
            }
            return storeRepository.findByBu(currentUser.getBu(), pageable);
        }
    }

    /**
     * Trova stores per BU (per dropdown filtri)
     */
    @Transactional(readOnly = true)
    public List<Store> findByBu(String bu) {
        return storeRepository.findByBu(bu);
    }

}
