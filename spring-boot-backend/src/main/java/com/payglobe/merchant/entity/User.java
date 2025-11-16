package com.payglobe.merchant.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import org.hibernate.annotations.CreationTimestamp;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.authority.SimpleGrantedAuthority;
import org.springframework.security.core.userdetails.UserDetails;

import java.time.LocalDateTime;
import java.util.Collection;
import java.util.Collections;

/**
 * Entity User - Mappa tabella "users" esistente (PHP)
 *
 * IMPORTANTE: Usa nomi colonne ESATTI come nel database
 * PhysicalNamingStrategyStandardImpl mantiene nomi originali
 */
@Entity
@Table(name = "users")
@Data
@NoArgsConstructor
@AllArgsConstructor
public class User implements UserDetails {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "email", unique = true, nullable = false, length = 255)
    private String email;

    @Column(name = "password", nullable = false, length = 255)
    private String password;  // BCrypt hash

    @Column(name = "bu", nullable = false, length = 50)
    private String bu;  // Business Unit

    @Column(name = "ragione_sociale", length = 255)
    private String ragioneSociale;

    @Column(name = "active")
    private Boolean active = true;

    @Column(name = "force_password_change")
    private Boolean forcePasswordChange = false;

    @Column(name = "password_last_changed")
    private LocalDateTime passwordLastChanged;

    @Column(name = "last_login")
    private LocalDateTime lastLogin;

    @Column(name = "created_at", updatable = false)
    @CreationTimestamp
    private LocalDateTime createdAt;

    // Helper methods

    public boolean isAdmin() {
        return "9999".equals(bu);
    }

    public boolean isPasswordExpired() {
        if (passwordLastChanged == null) {
            return true;
        }
        return passwordLastChanged.plusDays(45).isBefore(LocalDateTime.now());
    }

    // ========== UserDetails implementation ==========

    @Override
    public Collection<? extends GrantedAuthority> getAuthorities() {
        // Gli utenti admin hanno ruolo ADMIN, gli altri USER
        if (isAdmin()) {
            return Collections.singletonList(new SimpleGrantedAuthority("ROLE_ADMIN"));
        }
        return Collections.singletonList(new SimpleGrantedAuthority("ROLE_USER"));
    }

    @Override
    public String getUsername() {
        return email;  // Usiamo email come username
    }

    @Override
    public String getPassword() {
        return password;
    }

    @Override
    public boolean isAccountNonExpired() {
        return true;
    }

    @Override
    public boolean isAccountNonLocked() {
        return active != null && active;
    }

    @Override
    public boolean isCredentialsNonExpired() {
        return !isPasswordExpired();
    }

    @Override
    public boolean isEnabled() {
        return active != null && active;
    }

}
